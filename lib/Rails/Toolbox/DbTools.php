<?php
namespace Rails\Toolbox;

use Rails\Exception;
use Rails\ActiveRecord\ActiveRecord;

class DbTools
{
    static public function generateSchemaFiles()
    {
        foreach (ActiveRecord::connections() as $connectionName => $cdata) {
            if (!isset($cdata['username']) || !isset($cdata['password']) || !isset($cdata['database'])) {
                continue;
            } else {
                try {
                    ActiveRecord::setConnection($connectionName);
                    $connection = ActiveRecord::connection();
                    
                    $dbname = $connection->selectValue("SELECT DATABASE()");
                    $tables = $connection->selectValues(sprintf('SHOW TABLES FROM `%s`', $dbname));
                } catch (\Exception $e) {
                    continue;
                }
                
                if (!$tables) {
                    throw new Exception\RuntimeException(
                        sprintf(
                            'Couldn\'t retrieve table information for connection %s',
                            $connectionName
                        )
                    );
                }

                foreach ($tables as $table_name) {
                    $class = 'Rails\ActiveRecord\Adapter\\' . $connection->adapterName() . '\Table';
                    $data = $class::fetchSchema($connection, $table_name);
                    
                    $path = \Rails::root() . '/db/table_schema/' . $connection->name();
                    if (!is_dir($path))
                        mkdir($path, 0777, true);
                    
                    $file = $path . '/' . $table_name . '.php';
                    
                    $contents  = "<?php\nreturn ";
                    $contents .= var_export($data, true);
                    $contents .= "\n;";
                    
                    file_put_contents($file, $contents);
                }
            }
        }
    }
}
