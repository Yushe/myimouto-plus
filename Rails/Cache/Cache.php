<?php
namespace Rails\Cache;

use Closure;
use Rails;

class Cache
{
    private $store;
    
    public function __construct($config)
    {
        if (is_string($config))
            $config = [$config];
        else
            $config = $config->toArray();
        
        switch ($config[0]) {
            case 'file_store':
                $class = '\Rails\Cache\Store\FileStore';
                break;
            
            case 'mem_cached_store':
                $class = '\Rails\Cache\Store\MemCachedStore';
                break;
            
            default:
                $class = $config[0];
                break;
        }
        
        array_shift($config);
        
        $this->store = new $class($config);
    }
    
    public function read($key, array $params = [])
    {
        return $this->store->read($key, $params);
    }
    
    public function write($key, $value, array $options = [])
    {
        return $this->store->write($key, $value, $options);
    }
    
    public function delete($key, array $params = [])
    {
        return $this->store->delete($key, $params);
    }
    
    public function exists($key)
    {
        return $this->store->exists($key);
    }
    
    public function fetch($key, $options = null, Closure $block = null)
    {
        if ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        $value = $this->read($key, $options);
        
        if ($value === null) {
            $value = $block();
            $this->write($key, $value, $options);
        }
        return $value;
    }
    
    public function readMulti()
    {
        $names = func_get_args();
        if (is_array(end($names)))
            $options = array_pop($names);
        else
            $options = [];
        
        $results = [];
        foreach ($names as $name) {
            if (null !== ($value = $this->read($name)))
                $results[$name] = $value;
        }
        return $results;
    }
    
    public function store()
    {
        return $this->store;
    }
}
