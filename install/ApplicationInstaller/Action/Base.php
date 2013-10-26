<?php
namespace ApplicationInstaller\Action;

abstract class Base
{
    abstract public function commit();
    
    protected function renameIndex()
    {
        rename(\Rails::publicPath() . '/index', \Rails::publicPath() . '/index.php');
    }
    
    protected function deleteInstallFiles()
    {
        \Rails\Toolbox\FileTools::emptyDir($this->root());
        
        # We may not have permissions to delete the dir
        try {
            unlink($this->root());
        } catch (\Exception $e) {
        }
    }
    
    protected function root()
    {
        return __DIR__ . '/../..';
    }
}
