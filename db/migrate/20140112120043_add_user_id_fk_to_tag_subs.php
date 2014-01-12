<?php
class AddUserIdFkToTagSubs extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->execute("ALTER TABLE tag_subscriptions ADD CONSTRAINT fk_tag_subs__user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    }
}
