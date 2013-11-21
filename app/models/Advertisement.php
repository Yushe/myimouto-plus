<?php
class Advertisement extends Rails\ActiveRecord\Base
{
    protected function validations()
    {
        return [
            'ad_type' => [
                'inclusion' => ['in' => ['horizontal', 'vertical']]
            ],
            'image_url'    => [ 'presence' => true ],
            'referral_url' => [ 'presence' => true ],
            'ad_type'      => [ 'presence' => true ],
            'status'       => [ 'presence' => true ],
            'width'        => [ 'presence' => true ],
            'height'       => [ 'presence' => true ]
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
}
