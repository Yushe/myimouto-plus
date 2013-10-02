<?php
namespace Rails\Toolbox\FileGenerators;

use Rails;
use Rails\Console\Console;

class ModelGenerator extends AbstractGenerator
{
    static public function generate($name, array $options = [], Console $console = null)
    {
        $modelsDir = Rails::config()->paths->models;
        
        $fileName = $name . '.php';
        
        preg_match('~(.*?)' . self::NAMESPACE_SEPARATOR . '(\w+)~', $name, $m);
        
        if (!empty($m[1])) {
            $fileParts = explode(self::NAMESPACE_SEPARATOR, $m[1]);
            $folders = implode(DIRECTORY_SEPARATOR, $fileParts);
            $folderPath = $modelsDir . DIRECTORY_SEPARATOR . $folders;
            
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true);
            }
            
            $namespace = "\nnamespace " . $m[1] . ";\n";
            $className = $m[2];
            
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $m[2] . '.php';
        } else {
            $namespace = '';
            $className = $name;
            $filePath = $modelsDir . DIRECTORY_SEPARATOR . $fileName;
        }
        
        if (is_file($filePath)) {
            $message = sprintf("File already exists: %s", $filePath);
            if ($console) {
                $console->terminate($message);
            } else {
                return false;
            }
        }
        
        $defaultOptions = [
            'parent' => ''
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        if (!$options['parent']) {
            $parent = 'Rails\ActiveRecord\Base';
            
            if ($namespace) {
                $parent = '\\' . $parent;
            }
        } else {
            $parent = "\n" . $options['parent'];
        }
        
        $template = self::modelTemplate();
        
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
    
    static private function modelTemplate()
    {
        return '<?php%namespace%
class %className% extends %parent%
{
    
}';
    }
}