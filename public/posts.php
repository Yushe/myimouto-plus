<?php
$dbname = 'myimouto_dev';
$host = '127.0.0.1';
$username = 'root';
$password = 'xamPP';
$total_posts = 200000;
$max_inserts = 5000; # Max inserts at once

set_time_limit(0);
$pdo = new Pdo('mysql:dbname=' . $dbname . ';host=' . $host . ';', $username, $password);
$time = strtotime('20 days ago');
$ratings = ['e', 'q', 'q', 's', 's', 's', 's'];
$max_tags = 20;
$total_queries = ceil($total_posts / $max_inserts);

$post_values = "(
    0,
    1,
    '127.0.0.1',
    %d,
    '%s',
    NULL,
    'jpg',
    NULL,
    NULL,
    %d,
    %d,
    '%s',
    '%s',
    150,
    150,
    300,
    300,
    0,
    1,
    0,
    0,
    'active',
    0,
    0,
    NULL,
    %d,
    %d,
    %d,
    '%s',
    NULL,
    NULL,
    NULL,
    %d,
    NULL
)";

$current_id = $pdo->query("SELECT id FROM posts ORDER BY id DESC LIMIT 1")->fetchAll();
if (!$current_id)
    $current_id = 0;
else
    $current_id = $current_id[0][0];

for ($i = 0; $i < $total_queries; $i++) {
    echo "Inserting chunk " . ($i+1) . " of " . $total_queries . "...\n";
    
    $values_clauses = [];
    $params = [];
    $posts_tags_sql = [];
    
    for ($c = 0; $c < $max_inserts; $c++) {
        $filesize = rand(2000, 3000);
        $md5 = md5($time);
        $width = rand(1500, 2000);
        $height = rand(1500, 2000);
        $created_at = date('Y-m-d H:i:s', $time);
        $rating = $ratings[array_rand($ratings)];
        $sample_width = rand(500, 1500);
        $sample_height = rand(500, 1500);
        $sample_size = rand(1000, 2000);
        $index_timestamp = $created_at;
        $random = rand(100, 9999999999);
        
        $values_clauses[] = sprintf($post_values, $filesize, $md5, $width, $height, $created_at, $rating, $sample_width, $sample_height, $sample_size, $index_timestamp, $random);
        
        $post_id = $pdo->lastInsertId();
        
        $tag_count = rand(1, $max_tags);
        
        $current_id++;
        
        foreach (range(1, $tag_count) as $ii) {
            $posts_tags_sql[] = '(' . $current_id . ', ' . rand(1, 32000) . ')';
        }
        $time++;
    }
    
    $sql  = "INSERT INTO posts VALUES ";
    $sql .= implode(', ', $values_clauses);
    $pdo->query($sql);
    
    $sql = "INSERT INTO posts_tags VALUES " . implode(', ', $posts_tags_sql);
    $pdo->query($sql);
}

echo "Done inserting " . $total_posts . " rows.\n";