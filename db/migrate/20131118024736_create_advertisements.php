<?php
class CreateAdvertisements extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->createTable('advertisements', function($t) {
            $t->column('image_url', 'string', ['null' => false]);
            $t->column('referral_url', 'string', ['null' => false]);
            $t->column('ad_type', 'string', ['null' => false]);
            $t->column('status', 'string', ['null' => false]);
            $t->column('hit_count', 'integer', ['null' => false, 'default' => 0]);
            $t->column('width', 'integer', ['null' => false]);
            $t->column('height', 'integer', ['null' => false]);
        });
    }
}
