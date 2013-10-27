<?php
namespace ApplicationInstaller;

use Composer\Script\Event;
use Rails;

/**
 * Post-install script ran when installing the system
 * using Composer to compile assets.
 */
class CompileAssets
{
    static public function compile(Event $event)
    {
        $railsRoot = __DIR__ . '/../..';
        
        # Create temporary config/config.php file
        $file = $railsRoot . '/config/config.php.example';
        $target = $railsRoot . '/config/config.php';
        copy($file, $target);
        
        # Load rails
        require $railsRoot . '/config/boot.php';
        
        # Reset configuration to production, because Rails will load
        # development by default when ran from CGI, and Assets won't
        # set environment to production itself. Fix this later.
        Rails::resetConfig('production');
        
        # Load console
        $console = new Console($event->getIO());
        
        # Warn about compiling
        $console->write("[Compiling assets]");
        
        # Set console to assets
        Rails::assets()->setConsole($console);
        
        # Compile files
        Rails::assets()->compileAll();
        
        # Delete temporary config file.
        unlink($target);
    }
}
