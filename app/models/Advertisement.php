<?php
class Advertisement extends Rails\ActiveRecord\Base
{
    # Valid positions for horizontal advertisements: any, top, bottom.
    static protected $POSITIONS = ['a', 't', 'b'];
    
    protected function validations()
    {
        return [
            'ad_type' => [
                'inclusion' => ['in' => ['horizontal', 'vertical']]
            ],
            'ad_type' => [ 'presence' => true ],
            'status'  => [ 'presence' => true ],
            'validateType',
            'validatePosition',
        ];
    }
    
    static public function random($type = 'vertical', $position = null)
    {
        $sql = self::where(['ad_type' => $type, 'status' => 'active'])->order('RAND()');
        if ($position) {
            $sql->where('position IN (?)', ['a', $position]);
        }
        return $sql->first();
    }
    
    static public function reset_hit_count($ids)
    {
        foreach (self::where('id IN (?)', $ids)->take() as $ad) {
            $ad->updateAttribute('hit_count', 0);
        }
    }

    # virtual method for resetting hit count in view
    public function setResetHitCount($is_reset)
    {
        if ($is_reset) {
            $this->hit_count = 0;
        }
    }
    
    # virtual method for no-reset default in view's form
    public function getResetHitCount()
    {
        return '0';
    }
    
    public function prettyPosition()
    {
        switch ($this->position) {
            case 'a':
                return 'Any';
            
            case 't':
                return 'Top';
            
            case 'b':
                return 'Bottom';
            
            default:
                return 'Unknown';
        }
    }
    
    protected function validatePosition()
    {
        if ($this->ad_type == 'vertical') {
            $this->position = null;
        } else {
            if (!in_array($this->position, self::$POSITIONS)) {
                $this->errors()->add('position', "is invalid");
                return false;
            }
        }
    }
    
    protected function validateType()
    {
        # Common needed attributes, width and height.
        $attr = null;
        if (!$this->width) {
            $attr = 'width';
        } elseif (!$this->height) {
            $attr = 'height';
        }
        
        if ($attr) {
            $this->errors()->add($attr, "can't be blank");
            return false;
        }

        if ($this->html) {
            $this->image_url    = null;
            $this->referral_url = null;
        } else {
            $attr = '';
            
            if (!$this->image_url) {
                $attr = 'image_url';
            } elseif (!$this->referral_url) {
                $attr = 'referral_url';
            }
            
            if ($attr) {
                $this->errors()->add($attr, "can't be blank");
                return false;
            }
            
            $this->html = null;
        }
    }
}
