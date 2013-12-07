<?php
/**
 * These seeds are the minimum required data for proper
 * system funtionality.
 */

/**
 * Rows for table_data
 */
$queries = [
    'INSERT INTO `table_data` VALUES (\'posts\', 0)',
    'INSERT INTO `table_data` VALUES (\'users\', 1)',
    'INSERT INTO `table_data` VALUES (\'non-explicit_posts\', 0)'
];
foreach ($queries as $query) {
    Rails\ActiveRecord\ActiveRecord::connection()->executeSql($query);
}

/**
 * Job tasks rows
 */
JobTask::create([
    'task_type'    => 'external_data_search',
    'data_as_json' => '{}',
    'status'       => 'pending',
    'repeat_count' => -1
]);
JobTask::create([
    'task_type'    => "upload_batch_posts",
    'data_as_json' => '{}',
    'status'       => "pending",
    'repeat_count' => -1
]);
JobTask::create([
    'task_type'    => "periodic_maintenance",
    'data_as_json' => '{}',
    'status'       => "pending",
    'repeat_count' => -1
]);
