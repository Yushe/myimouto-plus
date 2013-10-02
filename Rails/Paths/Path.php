<?php
namespace Rails\Paths;

class Path
{
    protected $basePaths = [];
    
    protected $path;
    
    public function __construct($path, array $basePaths = [])
    {
        $this->basePaths = $basePaths;
        $this->path = $path;
    }
    
    public function __toString()
    {
        return $this->fullPath();
    }
    
    public function toString()
    {
        return $this->fullPath();
    }
    
    public function addBasePath($basePath)
    {
        $this->basePaths[] = $basePath;
    }
    
    public function basePaths(array $basePaths = null)
    {
        if (null === $basePaths) {
            return $this->baePaths;
        } else {
            $this->basePaths = $basePaths;
        }
    }
    
    public function path($path = null)
    {
        if (null === $path) {
            return $this->path;
        } else {
            $this->path = $path;
        }
    }
    
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    
    public function setBasePaths(array $basePaths)
    {
        $this->basePaths = $basePaths;
        return $this;
    }
    
    public function fullPath()
    {
        $basePath = implode('/', $this->basePaths);
        if ($basePath) {
            $basePath .= '/';
        }
        return  $basePath . $this->path;
    }
    
    /**
     * Concats fullPath with additional sub paths (to point to a file,
     * for example). This is therefore intended to be used if this Path
     * points to a folder.
     *
     * @param string|array $paths  paths to concat to the fullPath
     */
    public function concat($paths)
    {
        if (is_array($paths))
            $paths = implode('/', $paths);
        return $this->fullPath() . '/' . $paths;
    }
}