<?php
namespace Rails\ActionController;

use Rails;
use Rails\ActionController\ExceptionHandler;
use Rails\ActionController\UrlFor;
use Rails\ActionController\Exception;

abstract class ActionController
{
    /**
     * Helps with _run_filters, which is protected.
     */
    static public function run_filters_for(ApplicationController $ctrlr, $type)
    {
        $ctrlr->run_filters($type);
    }
    
    static public function load_exception_handler($class_name, $status)
    {
        // $path = Rails::config()->paths->controllers;
        // $file = $path . '/' . Rails::services()->get('inflector')->underscore($class_name) . '.php';
        // if (!is_file($file))
            // throw new ActionController_Exception(sprintf("File for exception handler controller not found. Searched for %s", $file));
        // require $file;
        
        // if (!class_exists($class_name, false))
            // throw new ActionController_Exception(sprintf("Class for exception handler controller not found. File: %s", $file));
        $handler = new $class_name();
        if (!$handler instanceof ExceptionHandler) {
            throw new ActionController_Exception(sprintf("Exception handler class %s must extend %s. File: %s", $class_name, "ActionController\ExceptionHandler", $file));
        }
        
        $handler->class_name = $class_name;
        $handler->setStatus((int)$status);
        return $handler;
    }
    
    public function params($name = null, $val = null)
    {
        if ($name)
            $this->_dispatcher()->parameters()->$name = $val;
        return $this->_dispatcher()->parameters();
    }
    
    public function request()
    {
        return $this->_dispatcher()->request();
    }
    
    public function response()
    {
        return Rails::application()->dispatcher()->response();
    }
    
    public function session()
    {
        return $this->_dispatcher()->session();
    }
    
    /**
     * Retrieves Cookies instance, retrieves a cookie or
     * sets a cookie.
     */
    public function cookies($name = null, $val = null, array $params = array())
    {
        $num = func_num_args();
        
        if (!$num)
            return $this->_dispatcher()->response()->cookies();
        elseif ($num == 1)
            return $this->_dispatcher()->response()->cookies()->get($name);
        else {
            if ($val === null)
                $this->_dispatcher()->response()->cookies()->delete($name);
            else
                return $this->_dispatcher()->response()->cookies()->add($name, $val, $params);
        }
    }
    
    protected function _dispatcher()
    {
        return Rails::application()->dispatcher();
    }
}