<?php
namespace Rails\Console;

class Console
{
    public function write($message, $n = 1)
    {
        echo $message . str_repeat("\n", $n);
    }
    
    public function terminate($message)
    {
        $this->write($message);
        exit;
    }
}
