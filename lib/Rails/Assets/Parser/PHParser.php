<?php
namespace Rails\Assets\Parser;

use Rails\Assets\Traits\AssetPathTrait;

class PHParser
{
    use AssetPathTrait;
    
    static protected $instance;
    
    static public function parseContents($contents)
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance->parse($contents);
    }
    
    public function parse($contents)
    {
        ob_start();
        eval('?>' . $contents);
        return ob_get_clean();
    }
}
