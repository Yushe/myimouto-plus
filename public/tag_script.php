<?php
require dirname(__FILE__) . '/../boot.php';

if (!Moebooru::application()->validate_safe_ips()) {
    require 'under_maintenance.html';
    exit;
}

set_time_limit(0);

# Change collation
ActiveRecord::connection()->exec("ALTER TABLE `tags` CHANGE `name` `name` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL");

# Non-utf8 connection.
$db_data = Rails::application()->config('activerecord');
$db_data['connection'] = trim(str_replace('charset=utf8', '', $db_data['connection']), ';');
ActiveRecord::add_connection($db_data, 'nonutf8');

$min = 1;
$max = 1000;

$tags = Tag::find_all(['conditions' => "id > $min AND id < $max"]);

while ($tags->any()) {
    ActiveRecord::set_connection('nonutf8');
    foreach ($tags as $tag) {
        $decoded = utf8_decode($tag->name);
        if (!preg_match('!!u', $decoded)) {
            try {
                $t = Tag::find($tag->id);
                ActiveRecord::set_connection('default');
                ActiveRecord::connection()->beginTransaction();
                $tag->update_attributes(['name' => $t->name, 'cached_related' => '', 'cached_related_expires_on' => null]);
            } catch (Exception $e) {
                $error = '<h3>Error</h3>';
                $error .= '<p>Type: ' .get_class($e). '</p>';
                $error .= '<p>Message: '.$e->getMessage().'</p>';
                $error .= '<p>Tag id: '.$t->id.'</p>';
                $error .= '<p>UTF8 name: '.$tag->name.'</p>';
                $error .= '<p>Non-UTF8 name: '.$t->name.'</p>';
                $error .= '<hr />';
                echo $error;
                ActiveRecord::connection()->rollBack();
                continue;
            }
            ActiveRecord::connection()->commit();
        }
    }
    
    $min += 1000;
    $max += 1000;
    $tags = Tag::find_all(['conditions' => "id > $min AND id < $max"]);
}

unlink(Rails::root() . '/public/under_maintenance.html');
unlink(Rails::root() . '/public/index.php');
rename(Rails::root() . '/public/index', Rails::root() . '/public/index.php');

header('Location: /');