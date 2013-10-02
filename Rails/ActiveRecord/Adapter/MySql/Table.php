<?php
namespace Rails\ActiveRecord\Adapter\MySql;

use Rails;
use Rails\ActiveRecord\Connection;
use Rails\ActiveRecord\Adapter\AbstractTable;
use Rails\ActiveRecord\Exception;

class Table/* extends AbstractTable*/
{
    static public function fetchSchema(Connection $connection, $table_name)
    {
        $stmt = $connection->executeSql("DESCRIBE `".$table_name."`");
        if (!$rows = $stmt->fetchAll()) {
            throw new Exception\RuntimeException(
                sprintf("Couldn't DESCRIBE %s:\n%s", $table_name, var_export($stmt->errorInfo(), true))
            );
        }
        
        $table_data = $table_indexes = $pri = $uni = [];
        
        foreach ($rows as $row) {
            $data = ['type' => $row['Type']];
            
            if (strpos($row['Type'], 'enum') === 0) {
                $enum_values = [];
                foreach (explode(',', substr($row['Type'], 5, -1)) as $opt)
                    $enum_values[] = substr($opt, 1, -1);
                $data['enum_values'] = $enum_values;
            }
            
            $table_data[$row['Field']] = $data;
        }
        
        $stmt = $connection->executeSql("SHOW INDEX FROM `".$table_name."`");
        $idxs = $stmt->fetchAll();
        
        if ($idxs) {
            foreach ($idxs as $idx) {
                if ($idx['Key_name'] == 'PRIMARY') {
                    $pri[] = $idx['Column_name'];
                } elseif ($idx['Non_unique'] === '0') {
                    $uni[] = $idx['Column_name'];
                }
            }
        }
        
        if ($pri)
            $table_indexes['pri'] = $pri;
        elseif ($uni)
            $table_indexes['uni'] = $uni;
        
        return [$table_data, $table_indexes];
    }
}