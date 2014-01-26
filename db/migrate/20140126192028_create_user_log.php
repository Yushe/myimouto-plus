<?php
class CreateUserLog extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        $this->execute(<<<EOS
            CREATE TABLE `user_logs` (
             `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
             `user_id` int(11) NOT NULL,
             `ip_addr` varchar(46) NOT NULL,
             `created_at` datetime NOT NULL,
             PRIMARY KEY (`id`),
             KEY `created_at` (`created_at`),
             KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB 
EOS
);
        $this->execute(
            "ALTER TABLE `user_logs`
                ADD CONSTRAINT fk_user_logs__user_id FOREIGN KEY (user_id) REFERENCES `users`(id) ON DELETE CASCADE"
        );
    }
}
