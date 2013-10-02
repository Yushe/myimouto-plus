<?php
namespace Rails\ActiveRecord\Adapter\Sqlite;

use PDO;
use Rails;
use Rails\ActiveRecord\Connection;
use Rails\ActiveRecord\Adapter\AbstractTable;

class Table/* extends AbstractTable*/
{
    static public function fetchSchema(Connection $connection, $table_name)
    {
        $stmt = $connection->executeSql("PRAGMA table_info(`".$table_name."`);");
        
        if (!$rows = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            return $stmt;
        }
        
        $table_data = $pri = $uni = [];
        
        $table_indexes = [
            'pri' => [],
            'uni' => []
        ];
        
        foreach ($rows as $row) {
            $data = ['type' => $row['type']];
            $table_data[$row['name']] = $data;
            
            if ($row['pk'])
                $table_indexes['pri'][] = $row['name'];
        }
        
        return [$table_data, $table_indexes];
    }
}