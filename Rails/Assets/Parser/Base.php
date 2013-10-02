<?php
namespace Rails\Assets\Parser;

use Rails;
use Rails\Assets\Assets;
use Rails\Assets\File;
use Rails\Assets\Exception;
use Rails\Toolbox;

class Base
{
    const EOL = "\n";
    
    const PARSE_TYPE_NONE      = 1;
    
    const PARSE_TYPE_GET_PATHS = 2;
    
    const PARSE_TYPE_FULL      = 3;
    
    static protected $extension = '';
    
    static private
        $_first_parents = [];
    
    protected $directives = [];
    
    protected $requiredSelf = [];
    
    protected $urlPaths = [];
    
    protected
        $parsedFile = [],
        $_file_eol = "\n",
        $_required_files = [],
        $parseType,
        $_child,
        $fileContents;
    
    protected $parentParser;
    
    /**
     * Rails\Assets\File
     */
    protected $file;
    
    public function parse($parseType = self::PARSE_TYPE_FULL)
    {
        $this->parseType = $parseType;
        
        $this->getFileContents();
        
        if ($this->parseType == self::PARSE_TYPE_NONE) {
            $this->parsedFile = $this->fileContents;
            $this->fileContents = null;
        } else {
            if ($this->parseType == self::PARSE_TYPE_GET_PATHS) {
                $this->urlPaths[] = $this->file->url();
            }
            $this->extractDirectives();
            $this->executeDirectives();
        }
        
        return $this;
    }
    
    /**
     * $path_parts = [0]=>assets_path; [1]=>filename; [2]=>extension;
     */
    public function __construct($file, Base $parentParser = null)
    {
        if (!$file instanceof File) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $file = new File($extension, $file);
        }
        
        $this->file = $file;
        
        if (!isset(self::$_first_parents[$file->extension()])) {
            self::$_first_parents[$file->extension()] = $this;
        }
        
        $this->parentParser = $parentParser;
        
        $this->firstParent()->add_required_file($file);
    }
    
    protected function extractDirectives()
    {
        $lines = explode("\n", $this->fileContents);
        
        $commentBlock = false;
        
        $directivesRegexp = implode('|', [
            'require_self',
            'require_directory [.-\w\s\/]+',
            'require_tree [.-\w\s\/]+',
            'require [.-\w\s\/]+',
            'include [.-\w\s\/]+',
        ]);
        
        foreach ($lines as $k => $line) {
            $line = trim($line);
            
            if (!$line) {
                continue;
            }
            
            $ab = substr($line, 0, 2);
            $c  = substr($line, 2, 1);
            
            if ($ab == '/*') {
                $commentBlock = true;
            } elseif (!$commentBlock && !preg_match('~^\s*//|/\*~', $line)) {
                # Not a comment and not in a comment block.
                # Directives block ended.
                break;
            }
            
            if (preg_match('~^\W+=\s+?(' . $directivesRegexp . ')~', $line, $m)) {
                $this->directives[] = array_filter(explode(' ', $m[1]));
                unset($lines[$k]);
            }
            
            if ($commentBlock && is_int(strpos($line, '*/'))) {
                $commentBlock = false;
            }
        }
        
        $this->fileContents = implode("\n", $lines);
    }
    
    protected function executeDirectives()
    {
        if ($this->directives) {
            foreach ($this->directives as $directive) {
                $command = $directive[0];
                
                if (isset($directive[1])) {
                    $params = $directive[1];
                }
                
                switch ($command) {
                    case 'require_directory':
                        $this->requireDir($params);
                        break;
                    
                    case 'require_tree':
                        $this->requireTree($params);
                        break;
                    
                    case 'require_self':
                        $this->requireSelf();
                        break;
                    
                    case 'require':
                        $this->requireFile($params . '.' . $this->file->type());
                        break;
                    
                    case 'include':
                        $this->includeFile($params . '.' . $this->file->type());
                        break;
                    
                    default:
                        throw new Exception\RuntimeException(
                            sprintf("Invalid directive in file %s:\n%s", $this->file->full_path(), var_export($directive, true))
                        );
                }
            }
        }
        
        $this->requireSelf();
        $this->parsedFile = implode("\n", $this->parsedFile);
    }
    
    public function urlPaths()
    {
        return $this->urlPaths;
    }
    
    protected function requireSelf()
    {
        if (!$this->requiredSelf) {
            $this->requiredSelf = true;
            $this->parsedFile[] = $this->fileContents;
            $this->fileContents = null;
        }
    }
    
    protected function includeFile($filename)
    {
        if (!$file = $this->filenameToFile($filename)) {
            throw new Exception\FileNotFoundException(
                sprintf(
                    "Included file '%s' not found. Require trace:\n%s",
                    $filename,
                    var_export($this->_parents_trace(), true)
                )
            );
        }
    
        $parser = new static($file, $this);
        $parser->parse($this->parseType);
        
        if ($this->parseType == Base::PARSE_TYPE_GET_PATHS) {
            $this->urlPaths = array_merge($this->urlPaths, $parser->urlPaths());
        } 
        
        $this->firstParent()->add_required_file($file);
        $this->parsedFile[] = $parser->parsed_file();
    }
    
    protected function requireFile($filename)
    {
        if (!$file = $this->filenameToFile($filename)) {
            throw new Exception\FileNotFoundException(
                sprintf(
                    "Required file '%s' not found. Require trace:\n%s",
                    $filename,
                    var_export($this->_parents_trace(), true)
                )
            );
        }
        
        if ($this->firstParent()->file_already_required($file)) {
            return false;
        }
        
        $this->includeFile($file);
    }
    
    private function filenameToFile($filename)
    {
        if (!$filename instanceof File) {
            $file = Rails::assets()->findFile($filename);
            if (!$file) {
                return false;
            }
        } else {
            $file = $filename;
        }
        
        return $file;
    }
    
    protected function requireDir($dir)
    {
        $path = $this->file->full_dir() . '/' . $dir;
        $realPath = realpath($path);
        
        if (!$realPath) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Directory not found: %s",
                    $path
                )
            );
        }
        
        $files = glob($realPath . '/*.' . $this->file->type() . '*');
        
        foreach ($files as $file) {
            $file = new File($this->file->type(), $file);
            $this->requireFile($file);
        }
    }
    
    protected function requireTree($tree)
    {
        $rootDir = $this->file->full_dir();
        
        $path = $rootDir . '/' . $tree;
        $realPath = realpath($path);
        
        if (!$realPath) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Path to tree not found: %s",
                    $path
                )
            );
        }
        
        # Require files in root directory.
        $this->requireDir($tree);
        
        # Require files in sub directories.
        $allDirs = Toolbox\FileTools::listDirs($realPath);
        
        foreach ($allDirs as $dir) {
            $relativeDir = trim(substr($dir, strlen($rootDir)), '/');
            $this->requireDir($relativeDir);
        }
    }
    
    public function parsedFile($implode = true)
    {
        return $implode && is_array($this->parsedFile) ? implode(self::EOL, $this->parsedFile) : $this->parsedFile;
    }
    
    public function parsed_file($implode = true)
    {
        return $this->parsedFile($implode);
    }
    
    public function parentParser()
    {
        return $this->parentParser;
    }
    
    public function add_required_file($file)
    {
        $this->_required_files[] = $file->full_file_root_path();
    }
    
    public function file_already_required($filename)
    {
        return in_array($filename->full_file_root_path(), $this->_required_files);
    }
    
    public function required_files()
    {
        return $this->_required_files;
    }
    
    public function config()
    {
        return Assets::instance()->config();
    }
    
    protected function getFileContents()
    {
        // if ($cached = $this->_cached_other_extension_file())
            // return $cached
        
        $contents = file_get_contents($this->file->full_path());
        
        if ($this->file->extensions()) {
            foreach ($this->file->extensions() as $ext) {
                if ($ext == 'php') {
                    $contents = PHParser::parseContents($contents);
                    // $contents = $parser->parse();
                } else {
                    $key = $this->file->type() . '_extensions';
                    // if (!$this->config()->$key)
                        // vpe($key);
                        // vpe($this->config()->$key);
                    $conf = $this->config()->$key->$ext;
                    // $conf = $this->config()['parsers'][self::EXTENSION][$ext];
                    
                    if (!$conf) {
                        throw new Exception\RuntimeException(
                            sprintf(
                                "Unknown parser [ file=>%s, extension=>%s ]",
                                $this->file->full_path(),
                                $ext
                            )
                        );
                    }
                    
                    if ($conf instanceof Closure) {
                        $conf($contents);
                    } else {
                        $class = $conf['class_name'];
                        $method = $conf['method'];
                        $static = !empty($conf['static']);
                        
                        if (!empty($conf['file'])) {
                            // vpe(get_include_path());
                            require_once $conf['file'];
                        }
                        
                        if ($static) {
                            $func = $class . '::' . $method;
                            $contents = $func($contents);
                        } else {
                            $parser = new $class();
                            $contents = $parser->$method($contents);
                        }
                    }
                }
            }
        }
        
        $this->fileContents = $contents;
    }
    
    protected function firstParent()
    {
        return self::$_first_parents[$this->file->extension()];
    }
    
    protected function _parents_trace()
    {
        $parent = $this->parentParser;
        if (!$parent)
            return [$this->file->full_path()];
        
        $files = [$parent->file->full_path()];
        
        while ($parent) {
            $parent = $parent->parentParser;
            $parent && $files[] = $parent->file->full_path();
        }
        return $files;
    }
}