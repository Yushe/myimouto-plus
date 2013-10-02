<?php
namespace Rails\ActionView;

/**
 * This file could belong somewhere else.
 */
class Xml
{
    private $_buffer = '';
    
    public function __call($method, $params)
    {
        array_unshift($params, $method);
        call_user_func_array([$this, 'create'], $params);
    }
    
    public function instruct()
    {
        $this->_buffer .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    }
    
    /**
     * $content could be passed as second argument.
     */
    public function create($root, $attrs, $content = null)
    {
        $this->_buffer .= '<'.$root;
        
        if (!is_array($attrs)) {
            $content = $attrs;
            $attrs = [];
        }
        
        if ($attrs) {
            $attrs_str = [];
            foreach ($attrs as $name => $val)
                $attrs_str[] = $name . '="'.htmlspecialchars($val).'"';
            $this->_buffer .= ' ' . implode(' ', $attrs_str);
        }
        
        if (!$content) {
            $this->_buffer .= ' />';
        } else {
            $this->_buffer .= ">\n";
            
            if (is_string($content))
                $this->_buffer .= $content;
            elseif ($content instanceof Closure)
                $this->_buffer .= $content();
            else
                throw new Exception\InvalidArgumentError(
                    sprintf('Expecting Closure or string as third argument, %s passed.', gettype($content))
                );
            
            $this->_buffer .= '</'.$root.'>';
        }
    }
    
    public function build($el, array $params = [])
    {
        $this->_buffer .= (new \Rails\Xml\Xml($el, $params))->output() . "\n";
    }
    
    public function output()
    {
        !$this->_buffer && $this->create();
        return $this->_buffer;
    }
}