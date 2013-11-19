<?php
class AddIpAddrToForumPosts extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->addColumn('forum_posts', 'ip_addr', 'string', ['length' => 46]);
        $this->addColumn('forum_posts', 'updater_ip_addr', 'string', ['length' => 46]);
    }
}
