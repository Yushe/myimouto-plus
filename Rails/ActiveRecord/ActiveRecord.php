<?php
namespace Rails\ActiveRecord;

use PDOStatement;
use Rails;
use Rails\ActiveRecord\Connection;

abstract class ActiveRecord
{
    static private $_prev_connection = '';
    
    static private
        $_connection_data = [],
        
        /**
         * The different connections.
         *
         * $name => ActiveRecord\Connection
         */
        $_connections = [];
    
    /**
     * Name of the active connection.
     */
    static private $activeConnectionName;
    
    /**
     * If an error ocurred when calling execute_sql(),
     * it will be stored here.
     */
    static private $_last_error;
    
    static public function setLastError(array $error, $connection_name = null)
    {
        self::$_last_error = $error;
    }
    
    /**
     * Adds a connection configuration to the list of available connections.
     */
    static public function addConnection($config, $name)
    {
        self::$_connection_data[$name] = $config;
    }
    
    /**
     * Sets the default active connection.
     */
    static public function setConnection($name)
    {
        self::create_connection($name);
        self::$activeConnectionName = $name;
        return true;
    }
    
    /**
     * Stablishes a connection from the available connections list.
     */
    static protected function create_connection($name)
    {
        # If the connection is already created, return.
        if (isset(self::$_connections[$name]))
            return;
        elseif (self::connectionExists($name))
            self::$_connections[$name] = new Connection(self::$_connection_data[$name], $name);
        else
            throw new Exception\RuntimeException(
                sprintf("Connection '%s' does not exist", $name)
            );
    }
    
    static public function connectionExists($name)
    {
        return isset(self::$_connection_data[$name]);
    }
    
    static public function set_environment_connection($environment)
    {
        if (self::connectionExists($environment))
            self::setConnection($environment);
        elseif (self::connectionExists('default'))
            self::setConnection('default');
    }
    
    /**
     * Returns connection. By default, returns the
     * currenct active one, or the one matching the $name.
     *
     * @return ActiveRecord\Connection
     */
    static public function connection($name = null)
    {
        if ($name === null) {
            $name = self::$activeConnectionName;
            if (!$name)
                throw new Exception\RuntimeException("No database connection is active");
        } else {
            self::create_connection($name);
        }
        
        return self::$_connections[$name];
    }
    
    /**
     * Returns active connection's name.
     */
    static public function activeConnectionName()
    {
        return self::$activeConnectionName;
    }

    /**
     * Returns active connection's data.
     */
    static public function activeConnectionData()
    {
        if (!self::$activeConnectionName)
            throw new Exception\RuntimeException("No database connection is active");
        return array_merge(self::$_connection_data[self::$activeConnectionName], ['name' => self::$activeConnectionName]);
    }
    
    /**
     * Used by the system when creating schema files.
     */
    static public function connections()
    {
        return self::$_connection_data;
    }
    
    static public function lastError()
    {
        return self::$_last_error;
    }
    
    static private function _include_additional_connection($name)
    {
        $config = Rails::application()->config()->active_record;
        if (!empty($config['additional_connections']) && !empty($config['additional_connections'][$name])) {
            self::addConnection($config['additional_connections'][$name], $name);
            return true;
        }
        return false;
    }
    
    /**
     * This could be somewhere else.
     */
    static public function proper_adapter_name($adapter_name)
    {
        $adapter_name = strtolower($adapter_name);
        
        switch ($adapter_name) {
            case 'mysql':
                return 'MySql';
                break;
            
            case 'sqlite':
                return 'Sqlite';
                break;
            
            default:
                return $adapter_name;
                break;
        }
    }
    
    static public function restore_connection()
    {
        if (self::$_prev_connection) {
            self::setConnection(self::$_prev_connection);
            self::$_prev_connection = null;
        }
    }
}