<?php
namespace Rails\Assets;

use Rails;
use Rails\Assets\Exception as AException;
use Rails\Assets\Server;
use Rails\Assets\File;

class Assets
{
    const BASE_CACHE_KEY = 'RAILS_ASSETS_';
    
    // static protected $parsers = [];
    
    static protected $instance;
    
    /**
     * Name of the YAML file that will store information
     * about compiled files.
     */
    public $manifestFileName = 'manifest';
    
    /**
     * Path where the manifest file will stored.
     * No trailing slash.
     * Defaults to compile path.
     */
    public $manifestFilePath;
    
    protected $prefix;
    
    protected $paths = [];
    
    protected $server;
    
    protected $console;
    
    /**
     * File patterns to search and compile files other than .css and .js.
     */
    protected $filePatterns = [];
    
    static public function instance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    
    // static public function addParsers(array $parsers)
    // {
        // self::$parsers = array_merge(self::$parsers, $parsers);
    // }
    
    // static public function setParsers(array $parsers)
    // {
        // self::$parsers = $parsers;
    // }
    
    // static public function getParsers()
    // {
        // return self::$parsers;
    // }
    
    public function console($message)
    {
        if ($this->console) {
            $this->console->write($message);
        }
    }
    
    public function setConsole(Rails\Console\Console $console)
    {
        $this->console = $console;
    }
    
    public function addPaths($paths)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        
        $this->paths = array_merge($this->paths, $paths);
    }
    
    /**
     * URL for non-static assets.
     */
    public function getFileUrl($filename)
    {
        if ($file = $this->findFile($filename)) {
            return $file->url();
        }
        return false;
    }
    
    /**
     * Returns not only the URL for the file, but also the URLs
     * of the files it requires (if it's a manifest file).
     */
    public function getFileUrls($filename)
    {
        if ($file = $this->findFile($filename)) {
            $parser = new Rails\Assets\Parser\Base($file);
            $parser->parse(Rails\Assets\Parser\Base::PARSE_TYPE_GET_PATHS);
            $urls = [];
            foreach ($parser->urlPaths() as $url) {
                $urls[] = $url . '?body=1';
            }
            return $urls;
        }
        return false;
    }
    
    public function findFile($filename)
    {
        $pinfo = pathinfo($filename);
        $extension = $pinfo['extension'];
        $dir = $pinfo['dirname'] == '.' ? '' : $pinfo['dirname'] . '/';
        $file_relative_path_root = $dir . $pinfo['filename'];
        
        $found = false;
        $pattern = $file_relative_path_root . '.' . $extension . '*';
        
        foreach ($this->paths as $assets_root) {
            $filePatt = $assets_root . '/' . $pattern;
            $files = glob($filePatt);
            
            if ($files) {
                $file = new File($extension, $files[0]);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            return $file;
        } else {
            return false;
        }
    }
    
    public function findCompiledFile($file)
    {
        if (!$this->config()->digest) {
            return $this->base_path() . ltrim($this->prefix()) . '/' . $file;
        } else {
            $index = $this->getManifestIndex();
            
            if (isset($index[$file])) {
                return $this->base_path() . ltrim($this->prefix()) . '/' . $index[$file];
            }
        }
        
        return false;
    }
    
    /**
     * Finds all files named $filename within all assets
     * dirs and subdirs.
     */
    public function findAllFiles($filename)
    {
        $pinfo = pathinfo($filename);
        $extension = $pinfo['extension'];
        $dir = $pinfo['dirname'] == '.' ? '' : $pinfo['dirname'] . '/';
        $file_relative_path_root = $dir . $pinfo['filename'];
        
        $foundFiles = [];
        
        $pattern = $file_relative_path_root . '.' . $extension . '*';
        
        foreach ($this->paths as $assetsRoot) {
            $foundFiles = array_merge($foundFiles, Rails\Toolbox\FileTools::searchFile($assetsRoot, $pattern));
        }
        
        return $foundFiles;
    }
    
    /**
     * Deletes all compiled files and compiles them again.
     */
    public function compileAll()
    {
        $this->emptyCompileDir();
        
        $this->compileOtherFiles();
        
        $manifestNames = $this->config()->precompile;
        
        foreach ($manifestNames as $manifest) {
            $ext = pathinfo($manifest, PATHINFO_EXTENSION);
            $paths = $this->findAllFiles($manifest);
            foreach ($paths as $path) {
                $this->compileFile(new File($ext, $path));
            }
        }
    }
    
    public function addFilePatterns($exts)
    {
        $this->filePatterns = array_merge($this->filePatterns, (array)$exts);
    }
    
    protected function compileOtherFiles()
    {
        $exts = $this->filePatterns;
        $pattern = '{' . implode(',', $exts) .'}';
        $foundFiles = [];
        
        foreach ($this->paths as $assetsRoot) {
            $foundFiles = array_merge($foundFiles, Rails\Toolbox\FileTools::searchFile($assetsRoot, $pattern, GLOB_BRACE));
        }
        
        $files = [];
        $assetsPath = $this->compilePath() . $this->prefix();
        foreach ($foundFiles as $foundFile) {
            $file = new File($foundFile);
            
            $contents = file_get_contents($foundFile);
            $this->createCompiledFile($assetsPath . '/' . $file->relative_path(), $contents, false);
            
            
            if ($this->config()->digest) {
                $md5 = md5_file($foundFile);
                
                $relativeDir = $file->relative_dir();
                if ($relativeDir) {
                    $relativeDir .= '/';
                }
                $relativePath = $relativeDir . $file->file_root();
                

                $basePath = $this->compilePath() . $this->prefix();
                $fileroot = $basePath . '/' . $relativeDir . $file->file_root();
                
                # Delete previous md5 files
                $pattern = $fileroot . '-*.' . $ext . '*';
                if ($mfiles = glob($pattern)) {
                    $regexp = '/-' . $md5 . '\.' . $ext . '(\.gz)?$/';
                    foreach ($mfiles as $mfile) {
                        if (!preg_match($regexp, $mfile)) {
                            unlink($mfile);
                        }
                    }
                }
                $this->updateManifestIndex($relativePath . '.' . $ext, $relativePath . '-' . $md5 . '.' . $ext);
                
                $md5File = $file->relative_file_root_path() . '-' . $md5 . '.' . $file->type();
                $this->createCompiledFile($assetsPath . '/' . $md5File, $contents, false);
            }
        }
    }
    
    /**
     * Deletes everything inside the compile folder.
     */
    public function emptyCompileDir()
    {
        $this->console("Deleting compiled assets");
        $dir = $this->compilePath() . $this->prefix();
        if (is_dir($dir)) {
            Rails\Toolbox\FileTools::emptyDir($dir);
        }
    }
    
    /**
     * Accepts:
     *  string - a filename (e.g. application.css), or
     *  File object
     *
     * Compiles, minifies and gz-compresses files. Also updates the
     * manifest index file.
     * Note that files with same name will be deleted.
     */
    public function compileFile($filename)
    {
        if (!$this->compilePath()) {
            throw new Exception\RuntimeException(
                "Missing asset configuration 'compile_path'"
            );
        } elseif (!$this->manifestFileName) {
            throw new Exception\RuntimeException(
                sprintf("Property %s::\$manifestFileName must not be empty", __CLASS__)
            );
        }
        
        if (is_string($filename)) {
            $file = $this->findFile($filename);
            if (!$file) {
                throw new Exception\RuntimeException(
                    sprintf("Asset file not found: %s", $filename)
                );
            }
        } elseif ($filename instanceof File) {
            $file = $filename;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Argument must be string or Rails\Assets\File, %s passed",
                    gettype($filename)
                )
            );
        }
        
        $this->console("Compiling file " . $file->full_path());
        
        $basePath = $this->compilePath() . $this->prefix();
        $ext = $file->type();
        $relativeDir = $file->relative_dir();
        if ($relativeDir) {
            $relativeDir .= '/';
        }
        $fileroot = $basePath . '/' . $relativeDir . $file->file_root();
        
        $compiledFileDir = dirname($fileroot);
        
        if (!is_dir($compiledFileDir)) {
            try {
                mkdir($compiledFileDir, 0755, true);
            } catch (\Exception $e) {
                throw new Exception\RuntimeException(
                    sprintf("Couldn't create dir %s: %s", $compiledFileDir, $e->getMessage())
                );
            }
        }
        
        set_time_limit(360);
        
        $parser = new Parser\Base($file);
        $parser->parse(Parser\Base::PARSE_TYPE_FULL);
        $fileContents = $parser->parsed_file();
        
        if ($this->config()->compress) {
            $fileContents = $this->compressFile($fileContents, $ext);
        }
        
        $compileFiles = [ $fileroot . '.' . $ext ];
        
        if ($this->config()->digest) {
            $md5 = md5($fileContents);
            $compileFiles[] = $fileroot . '-' . $md5 . '.' . $ext;
        }
        
        foreach ($compileFiles as $compileFile) {
            $this->createCompiledFile($compileFile, $fileContents);
        }
        
        $relativePath = $relativeDir . $file->file_root();
        
        if ($this->config()->digest) {
            # Delete previous md5 files
            $pattern = $fileroot . '-*.' . $ext . '*';
            if ($mfiles = glob($pattern)) {
                $regexp = '/-' . $md5 . '\.' . $ext . '(\.gz)?$/';
                foreach ($mfiles as $mfile) {
                    if (!preg_match($regexp, $mfile)) {
                        unlink($mfile);
                    }
                }
            }
            $this->updateManifestIndex($relativePath . '.' . $ext, $relativePath . '-' . $md5 . '.' . $ext);
        }
        return true;
    }
    
    protected function createCompiledFile($compiledFilename, $fileContents, $compress = null)
    {
        $dir = dirname($compiledFilename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        if (file_put_contents($compiledFilename, $fileContents)) {
            $this->console("Created " . $compiledFilename);
        } else {
            throw new Exception\RuntimeException(
                sprintf("Couldn't write file %s", $compiledFilename)
            );
        }
        
        if (null === $compress) {
            $compress = $this->config()->gz_compression;
        }
        
        # Compress files.
        if ($compress) {
            $gzFile = $compiledFilename . '.gz';
            $gzdata = gzencode($fileContents, $this->config()->gz_compression_level);
            
            if (file_put_contents($gzFile, $gzdata)) {
                $this->console("Created " . $gzFile);
            } else {
                throw new Exception\RuntimeException(
                    sprintf("Couldn't write file %s", $compiledFilename)
                );
            }
        }
    }

    /**
     * Path where the folder named after $prefix will
     * be created to store compiled assets.
     */
    public function compilePath()
    {
        return $this->config()->compile_path;
    }
    
    /**
     * Gets the contents of the manifest index file.
     *
     * @return array
     */
    public function getManifestIndex()
    {
        $file = $this->manifestIndexFile();
        if (is_file($file)) {
            $index = Rails\Yaml\Parser::readFile($file);
            # Force array.
            if (!is_array($index)) {
                $index = [];
            }
        } else {
            $index = [];
        }
        return $index;
    }
    
    /**
     * Path to the manifest file.
     *
     * @return string
     */
    public function manifestIndexFile()
    {
        $basePath = $this->manifestFilePath ?: $this->config()->compile_path . $this->prefix();
        $indexFile = $basePath . '/' . $this->manifestFileName . '.yml';
        return $indexFile;
    }
    
    /**
     * @param string $file
     * @param string $md5File
     */
    protected function updateManifestIndex($file, $md5File)
    {
        $index = $this->getManifestIndex();
        $index[$file] = $md5File;
        Rails\Yaml\Parser::writeFile($this->manifestIndexFile(), $index);
    }
    
    public function prefix()
    {
        if (!$this->prefix) {
            $this->prefix = str_replace('\\', '/', Rails::application()->config()->assets->prefix);
        }
        return $this->prefix;
    }

    public function public_path()
    {
        return Rails::publicPath() . $this->prefix();
    }
    
    public function trim_paths(array $files)
    {
        $trimd = [];
        foreach ($files as $file) {
            foreach ($this->paths as $path) {
                if (is_int(strpos($file, $path))) {
                    $trimd[] = str_replace($path, '', $file);
                    continue 2;
                }
            }
            $trimd[] = $file;
        }
        return $trimd;
    }
    
    public function compressFile($contents, $ext)
    {
        $key = $ext . '_compressor';
        $conf = $this->config()->$key;
        
        if (!$conf) {
            throw new Exception\RuntimeException(
                "No compressor defined for extension $ext"
            );
        }
        
        if ($conf instanceof Closure) {
            $compressed = $conf($contents);
        } else {
            $class = $conf['class_name'];
            
            $method = $conf['method'];
            $static = empty($conf['static']);
            
            if (!empty($conf['file'])) {
                require_once $conf['file'];
            }
            
            if ($static) {
                $compressed = $class::$method($contents);
            } else {
                $compressor = new $class();
                $compressed = $compressor->$method($contents);
            }
        }
        
        return $compressed;
    }
    
    public function config()
    {
        return Rails::application()->config()->assets;
    }
    
    public function cache_read($key)
    {
        return Rails::cache()->read($key, ['path' => 'rails']);
    }
    
    public function cache_write($key, $contents)
    {
        return Rails::cache()->write($key, $contents, ['path' => 'rails']);
    }
    
    public function base_path()
    {
        return Rails::application()->router()->basePath();
    }
    
    public function paths()
    {
        return $this->paths;
    }
    
    public function server()
    {
        if (!$this->server) {
            $this->server = new Server;
        }
        return $this->server;
    }
}