<?php
namespace ApplicationInstaller;

/**
 * Console used by CompileAssets.
 */
class Console extends \Rails\Console\Console
{
    private $io;
    
    public function __construct($io)
    {
        $this->io = $io;
    }
    
    public function write($message, $n = 1)
    {
        $this->io->write($message . str_repeat("\n", $n));
    }
}
