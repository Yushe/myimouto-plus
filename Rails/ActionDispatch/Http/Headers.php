<?php
namespace Rails\ActionDispatch\Http;

use Rails;

class Headers
{
    private $headers = array();
    
    private $status = 200;
    
    private $status_sent = false;
    
    private $_content_type;
    
    // public function status()
    // {
        // return $this->status;
    // }
    
    public function status($status = null)
    {
        if (null === $status) {
            return $this->status;
        } else {
            if (ctype_digit((string)$status))
                $this->status = (int)$status;
            elseif (is_string($status))
                $this->status = $status;
            else
                throw new Exception\InvalidArgumentException(
                    sprintf("%s accepts string, %s passed.", __METHOD__, gettype($value))
                );
            return $this;
        }
    }
    
    public function location($url, $status = 302)
    {
        if (!is_string($url))
            throw new Exception\InvalidArgumentException(
                sprintf("%s accepts string as first parameter, %s passed.", __METHOD__, gettype($value))
            );
        elseif (!is_int($status) && !is_string($status))
            throw new Exception\InvalidArgumentException(
                sprintf("%s accepts string or int as second parameter, %s passed.", __METHOD__, gettype($status))
            );
        
        $this->status($status)->set('Location', $url);
        $this->status = $status;
        return $this;
    }
    
    public function set($name, $value = null)
    {
        return $this->add($name, $value);
    }
    
    public function add($name, $value = null)
    {
        if (!is_string($name))
            throw new Exception\InvalidArgumentException(
                sprintf("First argument for %s must be a string, %s passed.", __METHOD__, gettype($value))
            );
        elseif (!is_null($value) && !is_string($value) && !is_int($value))
            throw new Exception\InvalidArgumentException(
                sprintf("%s accepts null, string or int as second argument, %s passed.", __METHOD__, gettype($value))
            );
        
        if (strpos($name, 'Content-type') === 0) {
            if ($value !== null) {
                $name = $name . $value;
            }
            $this->contentType($name);
        } elseif ($name == 'status') {
            $this->status($value);
        } elseif (strpos($name, 'HTTP/') === 0) {
            $this->status($name);
        } else {
            if ($value === null) {
                if (count(explode(':', $name)) < 2)
                    throw new Exception\InvalidArgumentException(
                        sprintf("%s is not a valid header", $name)
                    );
                $this->headers[] = $name;
            } else {
                $this->headers[$name] = $value;
            }
        }
        return $this;
    }
    
    public function send()
    {
        if ($this->status_sent) {
            throw new Exception\LogicException("Headers have already been sent, can't send them twice");
        }
        
        if (!$this->_content_type) {
            $this->_set_default_content_type();
        }
        header($this->_content_type);
        // vpe($this->_content_type);
        foreach ($this->headers as $name => $value) {
            if (!is_int($name))
                $value = $name . ': ' . $value;
            header($value);
        }
        
        if (is_int($this->status))
            header('HTTP/1.1 ' . $this->status);
        else
            header($this->status);
        
        $this->status_sent = true;
    }
    
    public function contentType($content_type = null)
    {
        if (null === $content_type) {
            return $this->_content_type;
        } else {
            return $this->setContentType($content_type);
        }
    }
    
    public function setContentType($content_type)
    {
    // static $i = 0;
    
        if (!is_string($content_type)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Content type must be a string, %s passed", gettype($content_type))
            );
        }
        // if($content_type != "text/html; charset=utf-8")
        // if ($i)
            // vpe($content_type);
        switch ($content_type) {
            case 'html':
                $content_type = 'text/html';
                break;
            
            case 'json':
                $content_type = 'application/json';
                break;
            
            case 'xml':
                $content_type = 'application/xml';
                break;
        }
        
        if (strpos($content_type, 'Content-type:') !== 0)
            $content_type = 'Content-type: ' . $content_type;
        
        $this->_content_type = $content_type;
        // $i++;
        // vpe($content_type);
        return $this;
    }
    
    private function _set_default_content_type()
    {
        // $this->set_content_type('text/html; charset='.Rails::application()->config()->encoding);
    }
}