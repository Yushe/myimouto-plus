<?php
class AddHtmlToAdvertisements extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->addColumn('advertisements', 'html', 'text', ['null' => true]);
        $this->changeColumn('advertisements', 'image_url', 'string', ['null' => true]);
        $this->changeColumn('advertisements', 'referral_url', 'string', ['null' => true]);
        $this->changeColumn('advertisements', 'width', 'integer', ['null' => true]);
        $this->changeColumn('advertisements', 'height', 'integer', ['null' => true]);
    }
}
