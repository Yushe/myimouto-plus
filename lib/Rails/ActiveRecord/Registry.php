<?php
namespace Rails\ActiveRecord;

use Rails;
use Rails\ActiveRecord\ActiveRecord;

/**
 * This class is supposed to store all the
 * models created.
 */
class Registry
{
    static private $_joining_tables = [];
    
    /**
     * This array's indexes will be the names
     * of the models.
     *
     * It will look something like this:
     *
        'ActiveRecord connection name' => [
            'Post' => array(
                'table_name' => 'posts',
                'table'      => ActiveRecord_ModelTable instance,
                'instances'  => array(
                    $model_1_id => $model_1,
                    $model_2_id => $model_2
                )
            );
        ]
     *
     * PriKey will be used for quick find.
     * If we need to find the model by an attribute
     * other than its PriKey... Maybe we could do
     * the query, but then search in the registry
     * for the model, now that we have the PK. If
     * it's there, retrieve the model! else, add it.
     */
    private $_reg = array();
    
    /**
     * Sets model to which methods will respond to.
     */
    private $_current_model;
    
    /**
     * @var string $model_name Model's class name
     */
    public function model($model_name)
    {
        $connection = $this->connection();
        
        if (!isset($this->_reg[$connection]))
            $this->_reg[$connection] = [];
        
        if (!isset($this->_reg[$connection][$model_name])) {
            $table_name = $model_name::tableName();
            $this->_reg[$connection][$model_name] = array(
                'table_name' => $table_name,
                'table'      => null,
                'instances'  => array()
            );
        }
        $this->_current_model = $model_name;
        return $this;
    }
    
    public function tableName()
    {
        $cn = $this->_current_model;
        return $cn::tableName();
    }
    
    public function table()
    {
        if (empty($this->_reg[$this->connection()][$this->_current_model]['table'])) {
            $cn = $this->get_table_class();
            $this->_reg[$this->connection()][$this->_current_model]['table'] = new $cn($this->tableName(), $this->_current_model);
        }
        return $this->_reg[$this->connection()][$this->_current_model]['table'];
    }
    
    public function search($id)
    {
        return;
        $id = (string)$id;
        if (isset($this->_reg[$this->connection()][$this->_current_model]) && isset($this->_reg[$this->connection()][$this->_current_model][$id]))
            return $this->_reg[$this->connection()][$this->_current_model][$id];
    }
    
    public function register($model)
    {
        return;
        $cn = get_class($model);
        
        if (!$cn::table()->primaryKey()) {
            return;
        } elseif (!$model->getAttribute('id')) {
            return;
        }
        
        if (!isset($this->_reg[$this->connection()][$this->_current_model]))
            $this->_reg[$this->connection()][$this->_current_model] = array();
        
        $id = (string)$model->getAttribute('id');
        
        $this->_reg[$this->connection()][$this->_current_model][$id] = $model;
        return true;
    }
    
    public function find_joining_table($table1, $table2)
    {
        if (isset(self::$_joining_tables[$table1]))
            return self::$_joining_tables[$table1];
        
        $tables = [
            $table1 . '_' . $table2,
            $table2 . '_' . $table1
        ];
        
        $sql = "SHOW TABLES LIKE ?";
        $stmt = ActiveRecord::connection()->prepare($sql);
        foreach ($tables as $table) {
            $stmt->execute([$table]);
            if ($stmt->fetch(PDO::FETCH_NUM)) {
                self::$_joining_tables[$table1] = $table;
                self::$_joining_tables[$table2] = $table;
                return $table;
            }
        }
        return false;
    }
    
    public function connection()
    {
        return ActiveRecord::activeConnectionName();
    }
    
    protected function get_table_class()
    {
        $model = $this->_current_model;
        $adapter = ActiveRecord::proper_adapter_name($model::connection()->adapterName());
        $cn = 'Rails\ActiveRecord\Adapter\\' . $adapter . '\Table';
        return $cn;
    }
}