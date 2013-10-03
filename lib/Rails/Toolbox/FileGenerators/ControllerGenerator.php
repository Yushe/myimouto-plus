<?php
namespace Rails\Toolbox\FileGenerators;

use Rails;
use Rails\Console\Console;

class ControllerGenerator extends AbstractGenerator
{
    static public function generate($name, array $options = [], Console $console = null)
    {
        $baseDir = Rails::config()->paths->controllers;
        
        $fileName = $name . 'Controller.php';
        
        preg_match('~(.*?)' . self::NAMESPACE_SEPARATOR . '(\w+)~', $name, $m);
        
        if (!empty($m[1])) {
            $fileParts = explode(self::NAMESPACE_SEPARATOR, $m[1]);
            $folders = implode(DIRECTORY_SEPARATOR, $fileParts);
            $folderPath = $baseDir . DIRECTORY_SEPARATOR . $folders;
            
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            
            $namespace = "\nnamespace " . $m[1] . ";\n";
            $className = $m[2];
            
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $m[2] . '.php';
        } else {
            $namespace = '';
            $className = $name;
            $filePath = $baseDir . DIRECTORY_SEPARATOR . $fileName;
        }
        
        $className .= 'Controller';
        
        $defaultOptions = [
            'parent' => ''
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$options['parent'] || $options['parent'] == 'namespaced') {
            $parent = 'ApplicationController';
            
            if ($options['parent'] != 'namespaced' && $namespace) {
                $parent = '\\' . $parent;
            }
        } else {
            $parent = "\n" . $options['parent'];
        }
        
        $template = self::template();
        
        $contents = str_replace([
            '%namespace%',
            '%className%',
            '%parent%',
        ], [
            $namespace,
            $className,
            $parent,
        ], $template);
        
        if (!is_file($filePath)) {
            if (!file_put_contents($filePath, $contents)) {
                $msg = "Couldn't create file";
                if ($console) {
                    $console->terminate($msg);
                } else {
                    throw new Exception\FileNotCreatedException(
                        $msg
                    );
                }
            }
        } else {
            if ($console) {
                $console->write("File already exists: " . $filePath);
            }
        }
        
        # Create view folder
        $viewsPath = Rails::config()->paths->views;
        $viewFolder = $viewsPath . '/' . Rails::services()->get('inflector')->underscore($name);
        if (!is_dir($viewFolder)) {
            mkdir($viewFolder);
            if ($console) {
                $console->write("Created directory: " . $viewFolder);
            }
        } else {
            if ($console) {
                $console->write("Directory already exists: " . $viewFolder);
            }
        }
        
        if ($console) {
            $console->terminate("Created file: " . $filePath);
        } else {
            return true;
        }
    }
    
    static private function template()
    {
        return '<?php%namespace%
class %className% extends %parent%
{
    
}
';
    }
}