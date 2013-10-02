<?php
namespace Rails\ActiveModel;

class Errors
{
    const BASE_ERRORS_INDEX = 'model_base_errors';
    
    private $errors = array();
    
    public function add($attribute, $msg = null)
    {
        if (!isset($this->errors[$attribute]))
            $this->errors[$attribute] = array();
        
        $this->errors[$attribute][] = $msg;
    }
    
    public function addToBase($msg)
    {
        $this->base($msg);
    }
    
    public function base($msg)
    {
        $this->add(self::BASE_ERRORS_INDEX, $msg);
    }
    
    public function on($attribute)
    {
        if (!isset($this->errors[$attribute]))
            return null;
        elseif (count($this->errors[$attribute]) == 1)
            return current($this->errors[$attribute]);
        else
            return $this->errors[$attribute];
    }
    
    public function onBase()
    {
        return $this->on(self::BASE_ERRORS_INDEX);
    }
    
    # $glue is a string that, if present, will be used to
    # return the messages imploded.
    public function fullMessages($glue = null)
    {
        $fullMessages = array();
        
        foreach ($this->errors as $attr => $errors) {
            foreach ($errors as $msg) {
                if ($attr == self::BASE_ERRORS_INDEX)
                    $fullMessages[] = $msg;
                else
                    $fullMessages[] = $this->_propper_attr($attr) . ' ' . $msg;
            }
        }
        
        if ($glue !== null)
            return implode($glue, $fullMessages);
        else
            return $fullMessages;
    }
    
    public function invalid($attribute)
    {
        return isset($this->errors[$attribute]);
    }
    
    # Deprecated in favor of none().
    public function blank()
    {
        return !(bool)$this->errors;
    }
    
    public function none()
    {
        return !(bool)$this->errors;
    }
    
    public function any()
    {
        return !$this->blank();
    }
    
    public function all()
    {
        return $this->errors;
    }
    
    public function count()
    {
        $i = 0;
        foreach ($this->errors as $errors) {
            $i += count($errors);
        }
        return $i;
    }
    
    private function _propper_attr($attr)
    {
        $attr = ucfirst(strtolower($attr));
        if (is_int(strpos($attr, '_'))) {
            $attr = str_replace('_', ' ', $attr);
        }
        return $attr;
    }
}