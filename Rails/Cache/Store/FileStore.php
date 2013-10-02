<?php
namespace Rails\Cache\Store;

use Rails;
use Rails\Toolbox\Toolbox;
use Rails\Cache\Store\FileStore\Entry;

/**
 * This class is intended to be used only by Rails.
 */
class FileStore extends AbstractStore
{
    protected $basePath;
    
    public function __construct(array $config)
    {
        $this->basePath = $config[0];
    }
    
    public function read($key, array $params = [])
    {
        return $this->getEntry($key, $params)->read();
    }
    
    public function write($key, $value, array $params)
    {
        return $this->getEntry($key, $params)->write($value);
    }
    
    public function delete($key, array $params)
    {
        return $this->getEntry($key, $params)->delete();
    }
    
    public function exists($key, array $params)
    {
        return $this->getEntry($key, $params)->fileExists();
    }
    
    /**
     * Removes cache files from directory.
     */
    public function deleteDirectory($dirname)
    {
        $dirpath = $this->path() . '/' . $dirname;
        
        if (is_dir($dirpath)) {
            Toolbox::emptyDir($dirpath);
        }
    }
    
    public function basePath()
    {
        return $this->basePath;
    }
    
    protected function getEntry($key, array $params)
    {
        return new Entry($key, $params, $this);
    }
}
