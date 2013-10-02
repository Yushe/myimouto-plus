<?php
namespace Rails\Xml;

use Rails;

class Xml
{
    private
        $_root,

        $_buffer = '',

        $_attrs  = [],

        $_params = [];
    
    public function __construct($el = null, array $params = [])
    {
        if ($el instanceof \Rails\ActiveRecord\Base) {
            $attrs = $el->toXml();
        } elseif (is_array($el)) {
            $attrs = $el;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf("%s accepts either a child of ActiveRecord\Base or an array, %s passed",
                    __METHOD__, gettype($attrs))
            );
        }
        
        if (!isset($params['root']))
            throw new Exception\InvalidArgumentException(
                sprintf('InvalidArgumentException', "Missing 'root' parameter for %s", __METHOD__)
            );
        
        $this->_attrs  = $attrs;
        $this->_root   = $params['root'];
        unset($params['root']);
        
        $this->_params = $params;
    }
    
    public function instruct()
    {
        $this->_buffer .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    }
    
    public function create()
    {
        if (empty($this->_params['skip_instruct']))
            $this->instruct();
        
        $this->_buffer .= '<'.$this->_root;
        
        $attrs_str = [];
        
        # TODO: fix $val, it should accept any value.
        foreach ($this->_attrs as $name => $val) {
            if (is_bool($val))
                $val = $val ? 'true' : 'false';
            elseif (!is_scalar($val))
                $val = '';
            
            $attrs_str[] = $name . '="'.htmlspecialchars((string)$val).'"';
        }
        
        $this->_buffer .= ' ' . implode(' ', $attrs_str);
        
        $this->_buffer .= ' />';
    }
    
    public function output()
    {
        if (!$this->_buffer)
            $this->create();
        return $this->_buffer;
    }
}