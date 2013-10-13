<?php
namespace Rails\ActiveRecord;

use Rails;

class ModelSchema
{
    protected $name;
    
    protected $schema;
    
    protected $adapter;
    
    protected $indexes;
    
    protected $connection;
    
    protected $primaryKey;
    
    protected $columnDefaults;
    
    public function __construct($name, Connection $connection)
    {
        $this->name = $name;
        $this->connection = $connection;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
        $this->reloadSchema();
    }
    
    public function schemaFile()
    {
        $path = Rails::root() . '/db/table_schema/' . $this->connection_name();
        $file = $path . '/' . $this->name . '.php';
        return $file;
    }
    
    public function reloadSchema()
    {
        $config = Rails::application()->config();
        
        if ($config->active_record->use_cached_schema) {
            $this->loadCachedSchema();
        } elseif (!$this->getSchema()) {
            throw new Exception\RuntimeException(
                sprintf("Couldn't find schema for %s: %s", $this->name, $this->error_stmt)
            );
        }
    }
    
    protected function loadCachedSchema()
    {
        $file = $this->schemaFile();
        if (!is_file($file)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Couldn't find schema file for table %s: %s",
                    $this->name,
                    $file
                )
            );
        }
        list($this->columns, $this->indexes) = require $file;
    }
    
    protected function getSchema()
    {
        $adapter = self::adapterClass();
        $data = $adapter::fetchSchema($this->connection, $this->name);
        
        list($this->columns, $this->indexes) = $data;
        
        return true;
    }
    
    protected function adapterClass()
    {
        $name = $this->connection->adapterName();
        return "Rails\ActiveRecord\Adapter\\" . $name . "\Table";
    }
    
    /**
     * This method can be overwritten to return an array
     * with the names of the columns.
     */
    public function columnNames()
    {
        return array_keys($this->columns);
    }
    
    public function columnType($column_name)
    {
        return $this->columns[$column_name]['type'];
    }
    
    public function columnExists($column_name)
    {
        return !empty($this->columns[$column_name]);
    }
    
    public function columnDefaults()
    {
        if ($this->columnDefaults === null) {
            $this->columnDefaults = [];
            foreach ($this->columns as $name => $data) {
                if ($data['default'] !== null) {
                    $this->columnDefaults[$name] = $data['default'];
                }
            }
        }
        return $this->columnDefaults;
    }
    
    public function enumValues($column_name)
    {
        if (!isset($this->columns[$column_name])) {
            throw new Exception\RuntimeException(
                sprintf("Column name %s not found for table %s", $column_name, $this->name)
            );
        }
        if (strpos($this->columns[$column_name]['type'], 'enum') !== 0) {
            throw new Exception\RuntimeException(
                sprintf("Column %s.%s is not of type enum", $this->name, $column_name)
            );
        }
        return $this->columns[$column_name]['enum_values'];
    }
    
    public function setPrimaryKey($key)
    {
        $this->primaryKey = $key;
    }
    
    public function primaryKey()
    {
        if ($this->primaryKey) {
            return $this->primaryKey;
        } else {
            $keys = $this->indexes('pri');
            if ($keys) {
                return $keys[0];
            }
        }
        return false;
    }
    
    public function indexes($type = null)
    {
        if (!$type) {
            return $this->indexes ? current($this->indexes) : array();
        } else {
            if (isset($this->indexes[$type]))
                return $this->indexes[$type];
        }
        return false;
    }
}