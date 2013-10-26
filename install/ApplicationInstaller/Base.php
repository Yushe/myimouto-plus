<?php
namespace ApplicationInstaller;

use Rails;

abstract class Base
{
    static protected $instance;
    
    static protected $step;
    
    static public function instance()
    {
        if (!self::$instance) {
            self::initialize();
        }
        return self::$instance;
    }
    
    static protected function initialize()
    {
        Rails::loader()->addPath(dirname(__DIR__));
        self::$instance = new Installer();
    }
    
    protected function root()
    {
        return dirname(__DIR__);
    }
    
    protected function request()
    {
        return Rails::application()->dispatcher()->request();
    }
    
    protected function refreshPage()
    {
        $urlFor = new Rails\Routing\UrlFor('root');
        header('Location: ' . $urlFor->url());
    }
    
    protected function step()
    {
        return self::$step;
    }
}
