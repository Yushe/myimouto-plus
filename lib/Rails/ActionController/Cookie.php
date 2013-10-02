<?php
namespace Rails\ActionController;

use Rails;

/**
 * This class is expected to be used only by Rails_ActionDispatch_CookieJar
 */
class Cookie
{
    protected $name;
    
    protected $value;
    
    protected $expire;
    
    protected $path;
    
    protected $domain;
    
    protected $secure = false;
    
    protected $httponly = false;
    
    protected $raw = false;
    
    public function __construct($name, $value, $expire = null, $path = null, $domain = null, $secure = false, $httponly = false, $raw = false)
    {
        $this->setDefaultValues();
        
        $this->name     = (string)$name;
        $this->domain   = (string)$domain;
        $this->value    = $value === null ? '' : $value;
        $this->expire   = $expire;
        $this->path     = $path;
        $this->secure   = $secure;
        $this->httponly = $httponly;
        $this->raw      = $raw;
        
        if (!$this->name) {
            throw new Exception\InvalidArgumentException('Cookies must have a name');
        }
        if (preg_match("/[=,; \t\r\n\013\014]/", $this->name)) {
            throw new Exception\InvalidArgumentException("Cookie name cannot contain these characters: =,; \\t\\r\\n\\013\\014 ({$this->name})");
        }
        if (is_string($this->expire)) {
            $time = strtotime($this->expire);
            if (!$time) {
                throw new Exception\InvalidArgumentException(
                    sprintf("Invalid expiration time: %s", $time)
                );
            }
            $this->expire = $time;
        }
        if ($this->expire !== null && !is_int($this->expire)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Cookie expiration time must be an integer, %s passed (%s)", gettype($this->expire), $this->expire)
            );
        }
        if ($this->raw && preg_match("/[=,; \t\r\n\013\014]/", $this->value)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Raw cookie value cannot contain these characters: =,; \\t\\r\\n\\013\\014 (%s)", $this->value)
            );
        }
        if (!is_scalar($this->value)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Cookie value must be a scalar value, %s passed", gettype($this->value))
            );
        }
    }
    
    public function value()
    {
        return $this->value;
    }
    
    public function set()
    {
        if ($this->raw) {
            setrawcookie($this->name, $this->value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
        } else {
            setcookie($this->name, $this->value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
        }
    }
    
    private function setDefaultValues()
    {
        $config = Rails::application()->config()->cookies;
        foreach ($config as $prop => $val) {
            # Silently ignore unknown properties.
            if (property_exists($this, $prop)) {
                $this->$prop = $val;
            }
        }
    }
}
