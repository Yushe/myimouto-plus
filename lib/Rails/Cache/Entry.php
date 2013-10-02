<?php
namespace Rails\Cache;

use Rails;

/**
 * This class is intended to be used only by Rails.
 */
class Entry
{
    const DATA_SEPARATOR      = ';';
    
    const KEY_VALUE_SEPARATOR = '=';
    
    private
        $_key,
        $_path,
        $_value,
        $_expires_in,
        $_file_exists,
        $_file_contents,
        $_params = [],
        /**
         * Subdirs inside the tmp/cache directory.
         * Names cannot contain dots.
         */
        $_dir = 'app';
    
    public function __construct($key)
    {
        $this->_key = $key;
        $this->_hash = $this->_hash_key($key);
    }
    
    public function read(array $params = [])
    {
        if (isset($params['path'])) {
            $this->_dir = $params['path'];
            unset($params['path']);
        }
        
        if ($this->file_exists())
            $this->_read_file();
        return $this->_value;
    }
    
    public function write($val, array $params)
    {
        $this->_value = serialize($val);
        if (isset($params['expires_in'])) {
            if (!ctype_digit((string)$params['expires_in']))
                $params['expires_in'] = strtotime($params['expires_in']);
        }
        if (isset($params['path'])) {
            $this->_dir = $params['path'];
            unset($params['path']);
        }
        $this->_params = $params;
        
        $header = [];
        foreach ($params as $k => $v)
            $header[] = $k . self::KEY_VALUE_SEPARATOR . $v;
        $header = implode(self::DATA_SEPARATOR, $header);
        
        if (!is_dir($this->_path()))
            mkdir($this->_path(), 0777, true);
        
        file_put_contents($this->_file_name(), $header . "\n" . $this->_value);
    }
    
    public function delete()
    {
        $this->_value = null;
        $this->_delete_file();
    }
    
    public function value()
    {
        return $this->_value;
    }
    
    public function file_exists()
    {
        if ($this->_file_exists === null) {
            $this->_file_exists = is_file($this->_file_name());
        }
        return $this->_file_exists;
    }
    
    public function unserialize_e_handler()
    {
        $this->_value = false;
    }
    
    private function _read_file()
    {
        $this->_file_contents = file_get_contents($this->_file_name());
        $this->_parse_contents();
        if ($this->_expired()) {
            $this->delete();
        } else {
            
        }
    }
    
    private function _parse_contents()
    {
        $regex = '/^(\V+)/';
        preg_match($regex, $this->_file_contents, $m);
        if (!empty($m[1])) {
            foreach(explode(self::DATA_SEPARATOR, $m[1]) as $data) {
                list($key, $val) = explode(self::KEY_VALUE_SEPARATOR, $data);
                $this->_params[$key] = $val;
            }
        } else
            $m[1] = '';
        
        # For some reason, try/catch Exception didn't work.
        $err_handler = set_error_handler([$this, "unserialize_e_handler"]);
        
        $this->_value = unserialize(str_replace($m[1] . "\n", '', $this->_file_contents));
        $this->_file_contents = null;
        
        set_error_handler($err_handler);
    }
    
    private function _expired()
    {
        if (!isset($this->_params['expires_in']) || $this->_params['expires_in'] > time())
            return false;
        return true;
    }
    
    private function _delete_file()
    {
        if (is_file($this->_file_name()))
            unlink($this->_file_name());
    }
    
    private function _file_name()
    {
        return $this->_path() . '/' . $this->_hash;
    }
    
    private function _generate_path($key)
    {
        $md5 = $this->_hash_key($key);
        $ab = substr($md5, 0, 2);
        $cd = substr($md5, 2, 2);
        return $this->_base_path() . '/' . $ab . '/' . $cd;
    }
    
    private function _base_path()
    {
        $subdir = $this->_dir ? '/' . str_replace('.', '', $this->_dir) : '';
        return Rails::cache()->path() . $subdir;
    }
    
    private function _hash_key($key)
    {
        return md5($key);
    }
    
    private function _path()
    {
        if (!$this->_path) {
            $this->_path = $this->_generate_path($this->_key);
        }
        return $this->_path;
    }
}