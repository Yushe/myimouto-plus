<?php
namespace Rails\Assets\Parser;

use Rails\Assets\Exception;
use Rails;

class PHParser
{
    static protected $instance;
    
    protected $file;
    
    static public function parseContents($contents, $file)
    {
        if (!self::$instance) {
            self::$instance = new self($file);
        }
        return self::$instance->parse($contents);
    }
    
    public function __construct($file)
    {
        $this->file = $file;
    }
    
    public function parse($contents)
    {
        ob_start();
        eval('?>' . $contents);
        return ob_get_clean();
    }
    
    /**
     * Returns relative paths.
     */
    protected function assetPath($file, array $options = [])
    {
        $root = \Rails::application()->router()->rootPath();
        if ($root == '/') {
            $root = '';
        }
        $path = Rails::assets()->prefix() . '/' . $file;
        return $this->getRelativePath($this->file->url(), $path);
    }
    
    # SO: /a/12236654/638668
    protected function getRelativePath($from, $to)
    {
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from     = explode('/', $from);
        $to       = explode('/', $to);
        $relPath  = $to;

        foreach($from as $depth => $dir) {
            if($dir === $to[$depth]) {
                array_shift($relPath);
            } else {
                $remaining = count($from) - $depth;
                if($remaining > 1) {
                    $padLength = (count($relPath) + $remaining - 1) * -1;
                    $relPath = array_pad($relPath, $padLength, '..');
                    break;
                } else {
                    $relPath[0] = './' . $relPath[0];
                }
            }
        }
        return implode('/', $relPath);
    }
}
