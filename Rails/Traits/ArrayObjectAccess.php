<?php
namespace Rails\Traits;

/**
 * Provides methods to access attributes that are ArrayObjects,
 * that are accessed through a method that receives
 * two arguments: key name and a value.
 *
 * Say you have an object with a protected property called $headers (that is
 * an ArrayObject), you can do the following:
 *
 * $object->headers('foo', 'bar')   : Sets $headers['foo'] = 'bar'.
 * $object->headers('foo')          : Returns the value of $headers['foo'], null if not set.
 * $object->headers('foo', null)    : Unsets $headers['foo'].
 * $object->headers()               : Returns $headers.
 * $object->headers(['foo' => 'bar', 'baz' => true]) : Sets many values at once.
 *
 * The method to access the $headers property should look like this:
 * 
 * public function headers()
 * {
 *     return $this->_aoaccess('headers', func_get_args());
 * }
 */
trait ArrayObjectAccess
{
    private function _aoaccess($prop_name, $args)
    {
        $num_args = count($args);
        
        if ($num_args == 1) {
            $key = array_shift($args);
            if (is_array($key)) {
                foreach ($key as $prop => $val)
                    $this->_aoaccess_set($prop_name, $prop, $val);
                return $this;
            } elseif (isset($this->$prop_name[$key]))
                return $this->$prop_name[$key];
            else
                return null;
        } elseif ($num_args) {
            list($key, $value) = $args;
            $this->_aoaccess_set($prop_name, $key, $value);
            return $this;
        } else {
            return $this->$prop_name;
        }
    }
    
    /**
     * Helps automatize the creation of ArrayObjects.
     * Expected to be called in the constructor.
     *
     * @param array $props : Names of the properties that are ArrayObjects.
     */
    private function _aoaccess_init(array $props)
    {
        foreach ($props as $prop)
            $this->$prop = new \ArrayObject();
    }
    
    private function _aoaccess_set($prop_name, $key, $value)
    {
        if ($value === null)
            unset($this->$prop_name[$key]);
        else
            $this->$prop_name[$key] = $value;
    }
}