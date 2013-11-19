<?php
class AddIpAddrToDmails extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->addColumn('dmails', 'ip_addr', 'string', ['length' => 46]);
    }
}
