<?php
namespace Rails\Yaml;

use Symfony\Component\Yaml\Yaml as SfYaml;

class Parser
{
    protected $filepath;
    
    static public function readFile($filepath)
    {
        return (new self($filepath))->read();
    }
    
    static public function writeFile($filepath, $contents)
    {
        return (new self($filepath))->write($contents);
    }
    
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }
    
    public function read()
    {
        try {
            if (function_exists('yaml_parse')) {
                return yaml_parse_file($this->filepath);
            } else {
                return SfYaml::parse($this->filepath);
            }
        } catch (\Exception $e) {
            $msg  = sprintf("Error while reading file %s:\n", $this->filepath);
            $msg .= $e->getMessage();
            $cn = get_class($e);
            throw new $cn($msg);
        }
    }
    
    public function write($contents, array $params = [])
    {
        try {
            if (function_exists('yaml_emit')) {
                $params = array_merge([$contents], $params);
                $yaml = call_user_func_array('yaml_emit', $params);
                return file_put_contents($this->filepath, $yaml);
            } else {
                $yaml = call_user_func_array('Symfony\Component\Yaml\Yaml::dump', array_merge([$contents], $params));
                return file_put_contents($this->filepath, $yaml);
            }
        } catch (\Exception $e) {
            $msg  = sprintf("Error while writing file %s:\n", $this->filepath);
            $msg .= $e->getMessage();
            $cn = get_class($e);
            throw new $cn($msg);
        }
    }
}