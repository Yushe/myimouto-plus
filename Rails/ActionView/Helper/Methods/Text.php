<?php
namespace Rails\ActionView\Helper\Methods;

trait Text
{
    private $_cycle_count = 0,
            $_cycle_vars  = [];
    
    public function cycle()
    {
        $args = func_get_args();
        
        # Clear vars if null was passed.
        if (count($args) == 1 && $args[0] === []) {
            $this->_cycle_vars = [];
            $this->_cycle_count = 0;
            return;
        # Reset cycle if new options were given.
        } elseif ($this->_cycle_vars && $this->_cycle_vars !== $args) {
            $this->_cycle_vars = [];
            $this->_cycle_count = 0;
        }
        
        if (empty($this->_cycle_vars))
            $this->_cycle_vars = $args;
        if ($this->_cycle_count > count($this->_cycle_vars) - 1)
            $this->_cycle_count = 0;
        
        $value = $this->_cycle_vars[$this->_cycle_count];
        $this->_cycle_count++;
        return $value;
    }
}