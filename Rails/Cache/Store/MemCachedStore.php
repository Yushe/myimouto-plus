<?php
namespace Rails\Cache\Store;

use Memcached;
use Rails;

class MemCachedStore extends AbstractStore
{
    /**
     * Instance of Memcached.
     */
    private $connection;
    
    public function __construct(array $servers)
    {
        if (!$servers)
            $servers = [['localhost', 11211]];
        
        $this->connection = new Memcached('Rails.Application.' . Rails::application()->name());
        $this->connection->addServers($servers);
    }
    
    public function connection()
    {
        return $this->connection;
    }
    
    public function read($key, array $params = [])
    {
        $value = $this->connection->get($key);
        
        if (!$value) {
            if ($this->connection->getResultCode() == Memcached::RES_NOTFOUND) {
                return null;
            } else {
                # There was some kind of error.
            }
        } else {
            return unserialize($value);
        }
    }
    
    public function write($key, $val, array $params)
    {
        $val = serialize($val);
        
        if (isset($params['expires_in'])) {
            if (!ctype_digit((string)$params['expires_in']))
                $expires_in = strtotime('+' . $params['expires_in']);
        } else
            $expires_in = 0;
        
        $resp = $this->connection->add($key, $val, $expires_in);
    }
    
    public function delete($key, array $params)
    {
        $time = !empty($params['time']) ? $params['time'] : 0;
        return $this->connection->delete($key, $time);
    }
    
    public function exists($key, array $params)
    {
        if ($this->connection->get($key))
            return true;
        else {
            # An error could have occured.
            return false;
        }
    }
}