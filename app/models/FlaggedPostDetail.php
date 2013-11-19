<?php
class FlaggedPostDetail extends Rails\ActiveRecord\Base
{
    # If this is set, the user who owns this record won't be included in the API.
    public $hide_user;
    
    protected function associations()
    {
        return [
            'belongs_to' => [
                'post',
                'user'
            ]
        ];
    }

    public function author()
    {
        return $this->flagged_by();
    }

    static public function new_deleted_posts($user)
    {
        if ($user->is_anonymous()) {
            return 0;
        }
        
        return Rails::cache()->fetch(
            'deleted_posts:'.$user->id.':'.$user->last_deleted_post_seen_at,
            ['expires_in' => '1 minute'],
            function() use ($user) {
                return self::connection()->selectValue(
                    "SELECT COUNT(*) FROM flagged_post_details fpd JOIN posts p ON (p.id = fpd.post_id) " .
                    "WHERE p.status = 'deleted' AND p.user_id = ? AND fpd.user_id <> ? AND fpd.created_at > ?",
                    $user->id, $user->id, $user->last_deleted_post_seen_at
                );
            }
        );
    }

    # XXX: author and flagged_by are redundant
    public function flagged_by()
    {
         if (!$this->user_id) {
            return "system";
        } else {
            return $this->user->name;
        }
    }

    public function api_attributes()
    {
        $ret = array(
            'post_id'    => $this->post_id,
            'reason'     => $this->reason,
            'created_at' => $this->created_at
        );

        if (!$this->hide_user) {
            $ret['user_id']    = $this->user_id;
            $ret['flagged_by'] = $this->flagged_by();
        }

        return $ret;
    }

    // public function asJson()
    // {(*args)
        // return; api_attributes.asJson(*args)
    // }

    // public function to_xml()
    // {(options = array())
        // return; api_attributes.to_xml(options.reverse_merge('root' => "flagged_post_detail"))
    // }
}
