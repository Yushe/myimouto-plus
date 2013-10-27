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
        require __DIR__ . '/../../config/boot.php';
        \Rails::assets()->compileAll();
    }
}
