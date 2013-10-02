<?php
namespace Rails\Config;

class Config implements \ArrayAccess, \IteratorAggregate
{
    protected $container = [];
    
    function getIterator()
    {
        return new \ArrayIterator($this->container);
    }
    
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->container[] = $value;
        } else {
            if (is_array($value)) {
                $value = new self($value);
            }
            $this->container[$offset] = $value;
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
    
    public function __construct(array $config = [])
    {
        $this->add($config);
    }
    
    public function __set($prop, $value)
    {
        if (is_array($value))
            $this->offsetSet($prop, new self($value));
        else
            $this->offsetSet($prop, $value);
    }
    
    public function __get($prop)
    {
        if ($this->offsetExists($prop))
            return $this->offsetGet($prop);
    }
    
    public function __isset($prop)
    {
        return (bool)$this->__get($prop);
    }
    
    public function add(array $config)
    {
        foreach ($config as $name => $value) {
            $this->__set($name, $value);
        }
    }
    
    public function merge(array $config)
    {
        $this->container = array_merge($this->container, $config);
        return $this;
    }
    
    public function toArray()
    {
        return $this->container;
    }
    
    public function includes($value)
    {
        return in_array($value, $this->container);
    }
    
    public function keys()
    {
        return array_keys($this->container);
    }
}