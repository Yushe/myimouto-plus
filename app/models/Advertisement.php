<?php
class Advertisement extends Rails\ActiveRecord\Base
{
    protected function validations()
    {
        return [
            'ad_type' => [
                'inclusion' => ['in' => ['horizontal', 'vertical']]
            ],
            'ad_type' => [ 'presence' => true ],
            'status'  => [ 'presence' => true ],
            'validateType'
        ];
    }
    
    static public function random($type = 'vertical')
    {
        return self::where(['ad_type' => $type, 'status' => 'active'])->order('RAND()')->first();
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
    
    protected function validateType()
    {
        if ($this->html) {
            $this->image_url    = null;
            $this->referral_url = null;
            $this->width        = null;
            $this->height       = null;
        } else {
            $attr = '';
            
            if (!$this->image_url) {
                $attr = 'image_url';
            } elseif (!$this->referral_url) {
                $attr = 'referral_url';
            } elseif (!$this->width) {
                $attr = 'width';
            } elseif (!$this->height) {
                $attr = 'height';
            }
            
            if ($attr) {
                $this->errors()->add($attr, "can't be blank");
                return false;
            }
            
            $this->html = null;
        }
    }
}
