<?php
namespace Moebooru;

trait TempfilePrefix
{
    public $tempfile_prefix;
    
    public function tempfile_prefix()
    {
        if (!$this->tempfile_prefix) {
            $this->tempfile_prefix = \Rails::publicPath() . '/data/temp-' . uniqid('tfp_');
        }
        return $this->tempfile_prefix;
    }
}
