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
        
        if (is_file($filePath)) {
            $message = sprintf("File already exists (pass 'f' to overwrite): %s", $filePath);
            if ($console) {
                $console->terminate($message);
            } else {
                throw new Exception\FileExistsException(
                    $message
                );
            }
        }
        
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
    
}';
    }
}