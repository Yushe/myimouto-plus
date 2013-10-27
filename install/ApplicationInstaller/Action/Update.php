<?php
namespace ApplicationInstaller\Action;

use Rails\ActiveRecord\ActiveRecord;
use User;
use Post;
use History;
use HistoryChange;
use Tag;
use Pool;
use PoolPost;
use Moebooru;

class Update extends Base
{
    public function commit()
    {
        $this->executeQueries();
        $this->createHistory();
        $this->renameIndex();
    }
    
    private function executeQueries()
    {
        $queries = [
            "ALTER TABLE  `table_data` CHANGE  `name`  `name` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",
            "UPDATE `table_data` SET `name` = 'non-explicit-posts' WHERE `table_data`.`name` = 'non-explici' LIMIT 1",
            
            # Post tag histories table
            "CREATE TABLE `post_tag_histories` (
             `id` int(11) NOT NULL AUTO_INCREMENT,
             `post_id` int(11) NOT NULL,
             `tags` text NOT NULL,
             `user_id` int(11) NOT NULL,
             `ip_addr` varchar(39) NOT NULL,
             `created_at` datetime NOT NULL,
             PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            
            "CREATE TABLE `histories` (
             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
             `created_at` DATETIME NOT NULL,
             `user_id` int(11) DEFAULT NULL,
             `group_by_id` int(11) NOT NULL,
             `group_by_table` varchar(12) NOT NULL,
             `aux_as_json` text DEFAULT NULL,
             PRIMARY KEY (`id`)
            ) ENGINE=InnoDB",
            
            "CREATE TABLE `history_changes` (
             `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
             `column_name` char(24) NOT NULL,
             `remote_id` bigint(20) NOT NULL,
             `table_name` char(24) NOT NULL,
             `value` text,
             `history_id` bigint(20) unsigned NOT NULL,
             `previous_id` bigint(20) DEFAULT NULL,
             `value_index` text NOT NULL,
             PRIMARY KEY (`id`),
             KEY `previous_id` (`previous_id`)
            ) ENGINE=InnoDB",
            
            "ALTER TABLE history_changes ADD CONSTRAINT fk_history_changes__history_id FOREIGN KEY (history_id) REFERENCES histories(id) ON DELETE CASCADE",
            
            "ALTER TABLE  `pools_posts` ADD  `active` BOOLEAN NOT NULL DEFAULT  '1'",
            
            "ALTER TABLE post_tag_histories ADD CONSTRAINT fk_post_tag_histories__post_id FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE",
            
            "ALTER TABLE  `pools` CHANGE  `description`  `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL",
            
            'DROP TRIGGER IF EXISTS `pools_posts_update_trg`',
            'CREATE TRIGGER `pools_posts_update_trg` BEFORE UPDATE ON `pools_posts`
             FOR EACH ROW BEGIN
                IF (OLD.active <> NEW.active) THEN
                  IF (NEW.active) THEN
                    UPDATE pools SET post_count = post_count + 1 WHERE id = NEW.pool_id;
                  ELSE
                    UPDATE pools SET post_count = post_count - 1 WHERE id = NEW.pool_id;
                  END IF;
                END IF;
             END',

            # cached tags queries
            "ALTER TABLE `posts` ADD `cached_tags` TEXT NOT NULL DEFAULT '' AFTER `source`",
            
            "UPDATE posts SET cached_tags = (
              SELECT GROUP_CONCAT(t.name SEPARATOR ' ')
                FROM posts_tags pt
                JOIN tags t ON pt.tag_id = t.id
              WHERE
                pt.post_id = posts.id
              GROUP BY pt.post_id
            )",
            
            "UPDATE posts SET cached_tags = 'tagme' WHERE cached_tags = ''",
            
            # Reset note versions table, which might already be empty.
            "DELETE FROM note_versions",
            "ALTER TABLE note_versions AUTO_INCREMENT = 1",
            "INSERT INTO note_versions (SELECT 0, NOW(), NOW(), x, y, width, height, body, version, ip_addr, is_active, id, post_id, user_id FROM notes)"
        ];

        foreach ($queries as $query) {
            ActiveRecord::connection()->executeSql($query);
        }
    }
    
    private function createHistory()
    {
        $user = User::where('level = ?', CONFIG()->user_levels['Admin'])->first();
        if (!$user) {
            $user = User::order('id ASC')->first();
        }

        foreach (Tag::all() as $tag) {
            $history = History::create([
                'group_by_id' => $tag->id,
                'group_by_table' => 'tags',
                'user_id' => $user->id
            ]);
            
            $attrs = [
                'remote_id' => $tag->id,
                'table_name' => 'tags',
                'history_id' => $history->id
            ];
            
            HistoryChange::create(array_merge([
                'column_name' => 'tag_type',
                'value' => $tag->tag_type
            ], $attrs));
            
            if ($tag->is_ambiguous)
                HistoryChange::create(array_merge([
                    'column_name' => 'is_ambiguous',
                    'value' => 1,
                ], $attrs));
        }

        foreach (PoolPost::all() as $pp) {
            $history = History::create([
                'group_by_id' => $pp->pool->id,
                'group_by_table' => 'pools',
                'user_id' => $pp->post->user_id
            ]);
            
            $attrs = [
                'remote_id' => $pp->id,
                'table_name' => 'pools_posts',
                'history_id' => $history->id
            ];
            
            HistoryChange::create(array_merge([
                'column_name' => 'sequence',
                'value' => $pp->sequence
            ], $attrs));
            
            HistoryChange::create(array_merge([
                'column_name' => 'active',
                'value' => true
            ], $attrs));
        }

        foreach (Pool::all() as $pool) {
            $history = History::create([
                'group_by_id' => $pool->id,
                'group_by_table' => 'pools',
                'user_id' => $pool->user_id
            ]);
            
            $attrs = [
                'remote_id' => $pool->id,
                'table_name' => 'pools',
                'history_id' => $history->id
            ];
            
            HistoryChange::create(array_merge([
                'column_name' => 'name',
                'value' => $pool->name
            ], $attrs));
            
            if ($pool->description)
                HistoryChange::create(array_merge([
                    'column_name' => 'description',
                    'value' => $pool->description
                ], $attrs));
            
            if (!$pool->is_public)
                HistoryChange::create(array_merge([
                    'column_name' => 'is_public',
                    'value' => false
                ], $attrs));
            
            if (!$pool->is_active)
                HistoryChange::create(array_merge([
                    'column_name' => 'is_active',
                    'value' => false
                ], $attrs));
        }

        $n = new Moebooru\Versioning\Versioning();
        $n->import_note_history();

        foreach (Post::all() as $post) {
            $history = History::create([
                'group_by_id' => $post->id,
                'group_by_table' => 'posts',
                'user_id' => $post->user->id
            ]);
            
            $attrs = [
                'remote_id' => $post->id,
                'table_name' => 'posts',
                'history_id' => $history->id
            ];
            
            if ($post->source)
                HistoryChange::create(array_merge([
                    'column_name' => 'source',
                    'value' => $post->source,
                ], $attrs));
            
            HistoryChange::create(array_merge([
                'column_name' => 'cached_tags',
                'value' => $post->cached_tags
            ], $attrs));
            
            HistoryChange::create(array_merge([
                'column_name' => 'rating',
                'value' => $post->rating,
            ], $attrs));
            
            if (!$post->is_shown_in_index)
                HistoryChange::create(array_merge([
                    'column_name' => 'is_shown_in_index',
                    'value' => 0,
                ], $attrs));
            
            if ($post->is_rating_locked)
                HistoryChange::create(array_merge([
                    'column_name' => 'is_rating_locked',
                    'value' => true,
                ], $attrs));
            
            if ($post->is_note_locked)
                HistoryChange::create(array_merge([
                    'column_name' => 'is_note_locked',
                    'value' => true,
                ], $attrs));
            
            if ($post->parent_id)
                HistoryChange::create(array_merge([
                    'column_name' => 'parent_id',
                    'value' => $post->parent_id,
                ], $attrs));
        }
    }
}
