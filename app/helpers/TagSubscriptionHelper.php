<?php
class TagSubscriptionHelper extends Rails\ActionView\Helper
{
    public function tag_subscription_listing($user)
    {
        $html = [];
        foreach ($user->tag_subscriptions as $tag_subscription) {
            $h = '<span class="group"><strong>' . $this->linkTo($tag_subscription->name, ["post#", 'tags' => "sub:" . $user->name . ":" . $tag_subscription->name]) . "</strong>:";
            $tags = preg_split('/\s+/', $tag_subscription->tag_query);
            sort($tags);
            $group = [];
            foreach ($tags as $tag) {
                $group[] = $this->linkTo($tag, ['post#', 'tags' => $tag]);
            }
            $h .= join(' ', $group);
            $html[] = $h;
        }
        return join(' ', $html);
    }
}
