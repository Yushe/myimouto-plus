<?php
class AddUpdaterIpAddrToComments extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->addColumn('comments', 'updater_ip_addr', 'string', ['limit' => 46]);
    }
}
