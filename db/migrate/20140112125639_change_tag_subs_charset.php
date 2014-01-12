<?php
class ChangeTagSubsCharset extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->execute("ALTER TABLE `tag_subscriptions` CHANGE `tag_query` `tag_query` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL, CHANGE `cached_post_ids` `cached_post_ids` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL, CHANGE `name` `name` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
    }
}
