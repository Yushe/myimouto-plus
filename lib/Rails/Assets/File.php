<?php
namespace Rails\Assets;

use Rails;

class File
{
    /**
     * One of the multiple assets directories where this file is.
     */
    protected $baseDir;
    
    /**
     * Subdirectories inside $baseDir.
     */
    protected $subDirs = [];
    
    /**
     * File root - File name with no extension.
     */
    protected $fileRoot;
    
    /**
     * File extension (wihout dot).
     */
    protected $extension;
    
    protected $extensions = [];
    
    /**
     * Asset extension, css or js.
     */
    protected $type;
    
    /**
     * @param srtring path   relative or full path to the file.
     */
    public function __construct($type, $path = null)
    {
        if (!$type) {
            throw new Exception\InvalidArgumentException(
                "First argument can't be empty"
            );
        }
        
        if (null === $path) {
            $path = $type;
            $type = $ext = pathinfo($path, PATHINFO_EXTENSION);
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
        }
        
        $this->type = $this->extension = $type;
        
        if (preg_match('/^\w:/', $path) || strpos($path, '/') === 0) {
            $path = str_replace('\\', '/', $path);
			
            foreach ($this->assets_paths() as $asset_path) {
				$asset_path = str_replace('\\', '/', $asset_path);
                if (strpos($path, $asset_path) === 0) {
                    $this->baseDir = $asset_path;
                    $path = substr($path, strlen($asset_path) + 1);
                    break;
                }
            }
        }
        
        $subDirs   = explode('/', str_replace('\\', '/', dirname($path)));
        
        $parseExt = $path;
        
        while ($ext != $this->type) {
            $this->extensions[] = $ext;
            $parseExt = substr($parseExt, 0, (strlen($ext) + 1) * -1);
            $ext = pathinfo($parseExt, PATHINFO_EXTENSION);
            if (!$ext) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        "Asset of type %s doesn't have the corresponding extension: %s",
                        $this->type,
                        $path
                    )
                );
            }
        }
        
        $fileRoot  = pathinfo($parseExt, PATHINFO_FILENAME);
        
        $this->subDirs = $subDirs;
        $this->fileRoot = $fileRoot;
        
        $this->validate_subdirs();
    }
    
    public function base_dir($baseDir = null)
    {
        if ($baseDir)
            $this->baseDir = str_replace('\\', '/', $baseDir);
        else
            return $this->baseDir;
    }
    
    /**
     * Relative dir (relative to assets paths).
     */
    public function relative_dir()
    {
        return implode('/', $this->subDirs);
    }
    
    public function full_dir()
    {
        $relativeDir = $this->relative_dir();
        if ($relativeDir) {
            $relativeDir = '/' . $relativeDir;
        }
        return $this->baseDir . $relativeDir;
    }
    
    public function relative_path()
    {
        $full_path = implode('/', $this->subDirs) . '/' . $this->fileRoot . '.' . $this->extension;
        if ($this->extensions) {
            $full_path .= '.' . implode('.', array_reverse($this->extensions));
        }
        return ltrim($full_path, '/');
    }
    
    public function full_path()
    {
        return $this->baseDir . '/' . $this->relative_path();
    }
    
    /**
     * Full path except file extension
     */
    public function full_file_root_path()
    {
        $subDirs = implode('/', $this->subDirs);
        if ($subDirs) {
            $subDirs .= '/';
        }
        return $this->baseDir . '/' . $subDirs . $this->fileRoot;
    }

    /**
     * Sub dirs + file root
     */
    public function relative_file_root_path()
    {
        $subDirs = implode('/', $this->subDirs);
        if ($subDirs) {
            $subDirs .= '/';
        }
        return $subDirs . $this->fileRoot;
    }
    
    public function sub_dirs()
    {
        return $this->subDirs;
    }
    
    public function file_root()
    {
        return $this->fileRoot;
    }
    
    public function extension()
    {
        return $this->extension;
    }
    
    public function extensions()
    {
        return $this->extensions;
    }
    
    public function type()
    {
        return $this->type;
    }
    
    /**
     * Creates a file "familiar": another asset file of the same type,
     * whose baseDir is unknown.
     */
    public function familiar($file_path)
    {
        $dirs = explode('/', $file_path);
        $new_file_root = array_pop($dirs);
        array_unshift($dirs, reset($this->subDirs));
        
        $file = new static($this->type);
        $file->subDirs = $dirs;
        $file->fileRoot = $new_file_root;
        $file->extension = $this->extension;
        return $file;
    }
    
    public function url($basePath = null, $prefix = null)
    {
        if (null === $basePath) {
            $basePath = Rails::assets()->base_path();
        }
        if (null === $prefix) {
            $prefix = Rails::assets()->prefix();
        }
        
        return $basePath . $prefix . '/' . $this->relative_file_root_path() . '.' . $this->type;
    }
    
    protected function assets_paths()
    {
        return Rails::assets()->paths();
    }
    
    protected function validate_subdirs()
    {
        $subDirs = [];
        
        foreach ($this->subDirs as $sd) {
            if (preg_match('/^[\w-]+$/', $sd))
                $subDirs[] = $sd;
        }
        
        $this->subDirs = $subDirs;
    }
}
