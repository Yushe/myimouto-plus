<?php
namespace Rails\Console;

use Zend;
use Rails\Toolbox\FileGenerator;

/**
 * Basic console.
 */
class ApplicationConsole extends Console
{
    public $argv;
    
    protected $mainArgv;
    
    protected $params;
    
    public function __construct()
    {
        $this->argv = !empty($_SERVER['argv']) ? $_SERVER['argv'] : [];
        array_shift($this->argv);
        $this->mainArgv = array_shift($this->argv);
    }
    
    public function params()
    {
        return $this->params;
    }
    
    public function run()
    {
        switch ($this->mainArgv) {
            case 'generate':
                $gen = new Generators\Generator($this);
                $gen->parseCmd();
                break;
            
            case 'assets':
                $rules = [
                    'assets'  => '',
                    'action'  => ''
                ];
                
                $opts = new Zend\Console\Getopt($rules);
                
                $argv = $opts->getArguments();
                if (empty($argv[1])) {
                    $this->terminate("Missing argument 2");
                }
                
                \Rails::resetConfig('production');
                
                switch ($argv[1]) {
                    case 'compile:all':
                        \Rails::assets()->setConsole($this);
                        \Rails::assets()->compileAll();
                        break;
                    
                    case (strpos($argv[1], 'compile:') === 0):
                        $parts = explode(':', $argv[1]);
                        if (empty($parts[1])) {
                            $this->terminate("Missing asset name to compile");
                        }
                        \Rails::assets()->setConsole($this);
                        \Rails::assets()->compileFile($parts[1]);
                        break;
                    
                    default:
                        $this->terminate("Unknown action for assets");
                        break;
                }
                
                break;
            
            case 'routes':
                $routes = $this->createRoutes();
                
                $rules = [
                    'routes' => '',
                    'f-s'    => ''
                ];
                
                $opts = new Zend\Console\Getopt($rules);
                
                if ($filename = $opts->getOption('f')) {
                    if (true === $filename) {
                        $logFile = \Rails::config()->paths->log->concat('routes.log');
                    } else {
                        $logFile = \Rails::root() . '/' . $filename;
                    }
                    file_put_contents($logFile, $routes);
                }
                
                $this->write($routes);
                break;
        }
    }
    
    protected function createRoutes()
    {
        $router = \Rails::application()->dispatcher()->router();
        
        $routes = [
            ['root', '', '/', $router->rootRoute()->to()]
        ];
        
        foreach ($router->routes() as $route) {
            $routes[] = [
                $route->alias() ?: '',
                strtoupper(implode(', ', $route->via())),
                '/' . $route->url(),
                $route->to()
            ];
        }
        
        $aliasMaxLen = 0;
        $viaMaxLen = 0;
        $pathMaxLen = 0;
        $toMaxLen = 0;
        
        foreach ($routes as $route) {
            $aliasLen = strlen($route[0]);
            $viaLen = strlen($route[1]);
            $pathLen = strlen($route[2]);
            
            if ($aliasLen > $aliasMaxLen)
                $aliasMaxLen = $aliasLen;
            if ($viaLen > $viaMaxLen)
                $viaMaxLen = $viaLen;
            if ($pathLen > $pathMaxLen)
                $pathMaxLen = $pathLen;
        }
        
        $lines = [];
        
        foreach ($routes as $route) {
            $route[0] = str_pad($route[0], $aliasMaxLen, ' ', STR_PAD_LEFT);
            $route[1] = str_pad($route[1], $viaMaxLen, ' ');
            $route[2] = str_pad($route[2], $pathMaxLen, ' ');
            $lines[] = implode(' ', $route);
        }
        
        return implode("\n", $lines);
    }
}