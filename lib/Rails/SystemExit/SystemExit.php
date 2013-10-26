<?php
namespace Rails\SystemExit;

class SystemExit
{
    protected $callbacks = [];
    
    public function register(callable $callback, $name = '')
    {
        if ($name)
            $this->callbacks[$name] = $callback;
        else
            $this->callbacks[] = $callback;
    }
    
    public function unregister($name)
    {
        if (isset($this->callbacks[$name])) {
            unset($this->callbacks[$name]);
            return true;
        } else {
            return false;
        }
    }
    
    public function run()
    {
        foreach ($this->callbacks as $callback) {
            call_user_func($callback);
        }
    }
}
