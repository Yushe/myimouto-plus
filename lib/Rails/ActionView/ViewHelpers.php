<?php
namespace Rails\ActionView;

use Rails;

class ViewHelpers
{
    const BASE_HELPER_NAME = 'Rails\ActionView\Helper\Base';
    
    static protected $registry = [];
    
    /**
     * Helpers instances.
     */
    static protected $helpers = [];
    
    static protected $helpersLoaded = false;
    
    /**
     * Helpers queue list. They will be available in this order.
     */
    static protected $queue = [];
    
    static protected $appQueue = [];
    
    /**
     * Searches for the helper that owns $method.
     *
     * @return object | false
     */
    static public function findHelperFor($method)
    {
        if (isset(self::$registry[$method])) {
            return self::$helpers[self::$registry[$method]];
        }
        
        foreach (self::$helpers as $helperName => $helper) {
            if (method_exists($helper, $method)) {
                self::$registry[$method] = $helperName;
                return $helper;
            }
        }
        
        return false;
    }
    
    static public function getBaseHelper()
    {
        return self::getHelper(self::BASE_HELPER_NAME);
    }
    
    static public function getHelper($name)
    {
        return self::$helpers[$name];
    }
    
    /**
     * Adds class names to the helper queues. These classes will be instantiated
     * in includeHelpers().
     *
     * @var name string helper name
     * @see load()
     */
    static public function addHelper($className)
    {
        self::$queue[] = $className;
    }
    
    static public function addHelpers(array $classNames)
    {
        self::$queue = array_merge(self::$queue, $className);
    }
    
    /**
     * For application helpers. Class names passed will be appended with "Helper".
     * This should only be called by Rails.
     */
    static public function addAppHelpers(array $helpers)
    {
        self::$appQueue = array_merge(self::$appQueue, array_map(function($c) { return $c . 'Helper'; }, $helpers));
    }
    
    /**
     * Actually include the helpers files.
     *
     * Application and current controller's helper are added here,
     * to make sure they're top on the list.
     */
    static public function load()
    {
        if (!self::$helpersLoaded) {
            if (($router = Rails::application()->dispatcher()->router()) && ($route = $router->route())) {
                $controllerHelper = Rails::services()->get('inflector')->camelize($route->controller()) . 'Helper';
                array_unshift(self::$appQueue, $controllerHelper);
            }
            $appHelper = 'ApplicationHelper';
            array_unshift(self::$appQueue, $appHelper);
            
            foreach (array_unique(self::$appQueue) as $name) {
                self::loadHelper($name);
            }
            foreach (array_unique(self::$queue) as $name) {
                self::loadHelper($name, true);
            }
            
            # Add base helper
            self::$helpers[self::BASE_HELPER_NAME] = new Helper\Base();
            
            self::$helpersLoaded = true;
        }
    }
    
    static protected function loadHelper($name, $throwE = false)
    {
        try {
            Rails::loader()->loadClass($name);
            self::$helpers[$name] = new $name();
        } catch (Rails\Loader\Exception\ExceptionInterface $e) {
            if ($throwE) {
                throw new Exception\RuntimeException(
                    sprintf("Couldn't load file for helper %s", $name)
                );
            }
        }
    }
}
