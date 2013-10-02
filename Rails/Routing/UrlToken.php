<?php
namespace Rails\Routing;

use Rails;

class UrlToken
{
    const SEPARATOR = '#';
    
    const MODULE_SEPARATOR = '\\';
    
    private
        $controller,
        $action = 'index',
        $modules = [];
    
    public function __construct($token)
    {
        if (is_int($pos = strpos($token, self::MODULE_SEPARATOR))) {
            if (!$pos) {
                $token = substr($token, 1);
            } else {
                $namespace = substr($token, 0, $pos);
                $token     = substr($token, $pos + 1);
                $this->modules = array_filter(explode(self::MODULE_SEPARATOR, $namespace));
            }
        }
        
        if (is_bool(strpos($token, self::SEPARATOR)))
            throw new Exception\InvalidArgumentException(sprintf("Missing separator in token '%s'", $token));
        $parts = explode(self::SEPARATOR, $token);
        
        if (empty($parts[0])) {
            $route = Rails::application()->dispatcher()->router()->route();
            if (!$route) {
                throw new Exception\RuntimeException(
                    "Can't complete URL token as there's no active route"
                );
            }
            $this->modules = $route->modules();
            $parts[0] = $route->controller();
        }
        $this->controller = $parts[0];
        
        if (!empty($parts[1]))
            $this->action = $parts[1];
    }
    
    public function parts()
    {
        return array($this->controller, $this->action, $this->modules);
    }
    
    public function controller()
    {
        return $this->controller;
    }
    
    public function action()
    {
        return $this->action;
    }
    
    public function namespaces()
    {
        return $this->modules;
    }
    
    public function modules()
    {
        return $this->modules;
    }
    
    public function token()
    {
        return $this->toString();
    }
    
    public function toString()
    {
        $namespace = $this->modules ? implode(self::MODULE_SEPARATOR, $this->modules) . self::MODULE_SEPARATOR : '';
        return $namespace . $this->controller . self::SEPARATOR . $this->action;
    }
    
    public function toUrl()
    {
        return str_replace(['#', self::MODULE_SEPARATOR], '/', $this->toString());
    }
}
