<?php
namespace Rails\Cache\Store;

abstract class AbstractStore
{
    abstract public function __construct(array $config);
    
    abstract public function read($key, array $params = []);
    
    abstract public function write($key, $val, array $params);
    
    abstract public function delete($key, array $params);
    
    abstract public function exists($key, array $params);
}