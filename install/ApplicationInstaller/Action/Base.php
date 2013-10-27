<?php
namespace ApplicationInstaller\Action;

abstract class Base
{
    abstract public function commit();
    
    protected function renameIndex()
    {
        rename(\Rails::publicPath() . '/index', \Rails::publicPath() . '/index.php');
    }
    
    protected function root()
    {
        return __DIR__ . '/../..';
    }
}
