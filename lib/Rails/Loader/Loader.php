<?php
namespace Rails\Loader;

use Closure;
use Rails;
use Rails\ActiveRecord\ActiveRecord;
use Rails\ActionMailer\ActionMailer;
use Rails\Loader\Exception;

class Loader
{
    protected $paths = [];
    
    protected $class_autoloaders = [];
    
    protected $composerAutoloader;
    
    public function __construct($paths = [])
    {
        if ($paths) {
            $this->addPaths($paths);
        }
        
        $this->loadRequiredClasses();
    }
    
    public function setComposerAutoload(\Composer\Autoload\ClassLoader $loader)
    {
        $this->composerAutoloader = $loader;
    }
    
    public function addPath($path)
    {
        $this->addPaths((array)$path);
    }
    
    public function addPaths(array $paths)
    {
        $this->paths = array_merge($this->paths, $paths);
    }
    
    public function loadClass($class_name)
    {
        if ($this->runAutoloaders($class_name)) {
            return;
        }
        
        if ($this->composerAutoloader->loadClass($class_name)) {
            return;
        }
        
        if (is_int(strpos($class_name, '\\'))) {
            $parts = explode('\\', $class_name);
            $file = array_pop($parts);
            $class_file_path = implode('/', $parts) . '/' . $file . '.php';
        } else
            $class_file_path = str_replace('_', '/', $class_name) . '.php';
        
        $paths = $this->paths;
        $found = false;
        
        foreach ($paths as $path) {
            if ($found = is_file(($class_file = $path . '/' . $class_file_path))) {
                break;
            }
        }
        
        if (!$found) {
            require_once __DIR__ . '/Exception/FileNotFoundException.php';
            throw new Exception\FileNotFoundException(sprintf("Couldn't find file for class %s, searched for %s in:\n%s", $class_name, $class_file_path, implode("\n", $paths)));
        }
        
        require $class_file;
        
        if (!class_exists($class_name, false) && !interface_exists($class_name, false) && !trait_exists($class_name, false)) {
            require_once __DIR__ . '/Exception/ClassNotFoundException.php';
            throw new Exception\ClassNotFoundException(sprintf("File %s doesn't contain class/interface/trait %s.", $class_file, $class_name));
        }
    }
    
    /**
     * Autoloaders must return true is the class was
     * successfuly loaded.
     */ 
    public function registerAutoloader(callable $function)
    {
        $this->class_autoloaders[] = $function;
    }
    
    public function runAutoloaders($class_name)
    {
        foreach ($this->class_autoloaders as $autoload) {
            if (is_array($autoload)) {
                $object = $autoload[0];
                $method = $autoload[1];
                
                if (true === $object->$method($class_name)) {
                    return true;
                }
            } elseif (is_string($autoload)) {
                if (true === call_user_func($autoload, $class_name))
                    return true;
            } elseif ($autoload instanceof Closure) {
                if (true === $autoload($class_name))
                    return true;
            } else {
                require_once __DIR__ . '/Exception/RuntimeException.php';
                throw new Exception\RuntimeException(sprintf("Invalid autoloader type (%s)", gettype($autoload)));
            }
        }
        
        return false;
    }
    
    /**
     * Loader uses ActiveRecord and ActionMailer. These classes must be available.
     */
    protected function loadRequiredClasses()
    {
    }
}