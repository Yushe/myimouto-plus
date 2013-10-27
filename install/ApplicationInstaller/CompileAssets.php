<?php
namespace ApplicationInstaller;

/**
 * Post-install script ran when installing the system
 * using composer. It compiles assets.
 */
class CompileAssets
{
    static public function compile()
    {
        $railsRoot = __DIR__ . '/../..';
        
        # Create temporary config/config.php file
        $file = $railsRoot . '/config/config.php.example';
        $target = $railsRoot . '/config/config.php';
        copy($file, $target);
        
        require $railsRoot . '/config/boot.php';
        \Rails::assets()->compileAll();
        
        # Delete temporary config file.
        unlink($target);
    }
}
