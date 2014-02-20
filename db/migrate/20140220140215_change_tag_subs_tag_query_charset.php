<?php
class ChangeTagSubsTagQueryCharset extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->execute("ALTER TABLE `tag_subscriptions` CHANGE `tag_query` `tag_query` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
    }
}
