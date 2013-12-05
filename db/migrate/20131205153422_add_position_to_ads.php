<?php
class AddPositionToAds extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->addColumn('advertisements', 'position', 'char', ['null' => true, 'limit' => 1]);
    }
}
