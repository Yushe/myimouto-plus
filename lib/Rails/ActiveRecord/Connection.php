<?php
namespace Rails\ActiveRecord;

use PDO;
use Rails;
use Rails\ActiveRecord\ActiveRecord;

class Connection
{
    protected $pdo;
    
    protected $adapter_name;
    
    protected $name;
    
    protected $config;
    
    public function __construct($config, $name)
    {
        if (is_array($config)) {
            $config = array_merge($this->default_connection_config(), $config);
            
            if (empty($config['dsn'])) {
                if ($dsn = $this->build_dsn($config))
                    $config['dsn'] = $dsn;
                else
                    throw new Exception\ErrorException(sprintf("Not enough info to create DSN string for connection '%s'", $name));
            }
            
            $this->pdo = $this->create_connection($config);
        } elseif ($config instanceof PDO) {
            $this->pdo = $config;
        } else {
            throw new Exception\InvalidArgumentException('Connection must be either array or instance of PDO.');
        }
        
        $this->adapter_name = ActiveRecord::proper_adapter_name($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        
        $this->name = $name;
    }
    
    protected function build_dsn($config)
    {
        if (!isset($config['adapter']))
            return;
        
        switch ($config['adapter']) {
            case 'mysql':
                $str = $config['adapter'];
                
                $params = [];
                if (isset($config['host']))
                    $params['host'] = $config['host'];
                if (isset($config['database']))
                    $params['dbname'] = $config['database'];
                if (isset($config['port']))
                    $params['port'] = $config['port'];
                if (isset($config['charset']))
                    $params['charset'] = $config['charset'];
                
                if (!isset($config['dsn_params'])) {
                    $config['dsn_params'] = [];
                }
                
                $params = array_merge($params, $config['dsn_params']);
                
                if ($params) {
                    $str .= ':';
                    foreach ($params as $key => $value)
                        $str .= $key . '=' . $value . ';';
                }
                
                return $str;
            
            case 'sqlite':
                $str = $config['adapter'];
                if (isset($config['database'])) {
                    $str .= ':' . Rails::root() . '/' . $config['database'] . '';
                }
                return $str;
        }
    }
    
    protected function default_connection_config()
    {
        return [
            'username'       => null,
            'password'       => null,
            'driver_options' => [],
            'pdo_attributes' => []
        ];
    }
    
    /**
     * Executes the sql and returns the statement.
     */
    public function executeSql()
    {
        $params = func_get_args();
        $sql = array_shift($params);
        
        if (!$sql)
            throw new Exception\LogicException("Can't execute SQL without SQL");
        elseif (is_array($sql)) {
            $params = $sql;
            $sql = array_shift($params);
        }
        $this->_parse_query_multimark($sql, $params);
        
        if (!$stmt = $this->pdo->prepare($sql)) {
            list($code, $drvrcode, $msg) = $this->pdo->errorInfo();
            $e = new Exception\QueryException(
                sprintf("[PDOStatement error] [SQLSTATE %s] (%s) %s", $code, $drvrcode, $msg)
            );
            throw $e;
        } elseif (!$stmt->execute($params) && Rails::env() == 'development') {
            list($code, $drvrcode, $msg) = $stmt->errorInfo();
            $e = new Exception\QueryException(
                sprintf("[PDOStatement error] [SQLSTATE %s] (%s) %s", $code, $drvrcode, $msg)
            );
            $e->setStatement($stmt, $params);
            throw $e;
        }
        
        $err = $stmt->errorInfo();
        
        if ($err[2]) {
            ActiveRecord::setLastError($err, $this->name);
            $e = new Exception\QueryException(
                sprintf("[SQLSTATE %s] (%s) %s", $err[0], (string)$err[1], $err[2])
            );
            $e->setStatement($stmt, $params);
            
            $msg  = "Error on database query execution\n";
            $msg .= $e->getMessage();
            Rails::log()->message($msg);
        }
        return $stmt;
    }
    
    /**
     * Executes the sql and returns the results.
     */
    public function query()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if (ActiveRecord::lastError())
            return false;
        return $stmt->fetchAll();
    }
    
    public function select()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if (ActiveRecord::lastError())
            return false;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function selectRow()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if (ActiveRecord::lastError())
            return false;
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    # Use only when retrieving ONE column from multiple rows.
    public function selectValues()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if (ActiveRecord::lastError())
            return false;
        
        $cols = array();
        if ($data = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            foreach ($data as $d)
                $cols[] = current($d);
        }
        return $cols;
    }
    
    public function selectValue()
    {
        $stmt = call_user_func_array([$this, 'executeSql'], func_get_args());
        if (ActiveRecord::lastError())
            return false;
        
        if ($data = $stmt->fetch()) {
            $data = array_shift($data);
        }
        return $data;
    }
    
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    public function transaction($params = [], \Closure $block = null)
    {
        if (!$block) {
            $block = $params;
        }
        $this->resource()->beginTransaction();
        $block();
        $this->resource()->commit();
    }
    
    public function resource()
    {
        return $this->pdo;
    }
    
    public function pdo()
    {
        return $this->pdo;
    }
    
    public function adapterName()
    {
        return $this->adapter_name;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    protected function _parse_query_multimark(&$query, array &$params)
    {
        # If the number of tokens isn't equal to parameters, ignore
        # it and return. PDOStatement will trigger a Warning.
        if (is_bool(strpos($query, '?')) || substr_count($query, '?') != count($params))
            return;
        
        $parts = explode('?', $query);
        $parsed_params = array();
        
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $k++;
                $count = count($v);
                $parts[$k] = ($count > 1 ? ', ' . implode(', ', array_fill(0, $count - 1, '?')) : '') . $parts[$k];
                $parsed_params = array_merge($parsed_params, $v);
            } else {
                $parsed_params[] = $v;
            }
        }
        
        $params = $parsed_params;
        $query = implode('?', $parts);
    }
    
    protected function create_connection($data)
    {
        $pdo = new PDO($data['dsn'], $data['username'], $data['password'], $data['driver_options']);
        
        if ($data['pdo_attributes']) {
            foreach ($data['pdo_attributes'] as $attr => $val)
                $pdo->setAttribute($attr, $val);
        }
        return $pdo;
    }
}