<?php
namespace Rails\ArrayHelper;

class GlobalVar extends Base
{
    protected
        $_var_name,
        $_key_name;
    
    public function __construct($values, $var_name, $key_name)
    {
        $this->_var_name = $var_name;
        $this->_key_name = $key_name;
        $this->merge($values);
    }
    
    public function offsetSet($offset, $value)
    {
        global ${$this->_var_name};
        if ($offset === null)
            ${$this->_var_name}[$this->_key_name][] = $value;
        else
            ${$this->_var_name}[$this->_key_name][$offset] = $value;
    }
    
    protected function _get_array()
    {
        global ${$this->_var_name};
        return ${$this->_var_name};
    }
}