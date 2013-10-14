<?php
namespace Rails\ActionDispatch\Http;

use Rails\Toolbox\ArrayTools;
use Rails\ArrayHelper\GlobalVar;

class Parameters implements \IteratorAggregate
{
    private
        $deleteVars = [],
        $putVars    = [],
        $_json_params_error = null,
        $patchVars = [],
        # Parameters for request methods other than
        # delete, put, post, get, patchVars (need to support head requests).
        $other_params = [];
    
    private $files;
    
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }
    
    public function __construct()
    {
        $method = \Rails::application()->dispatcher()->request()->method();
        if ($method != 'GET' && $method != 'POST') {
            $params = file_get_contents('php://input');
            $decoded = [];
            if (!empty($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == "application/json") {
                $decoded = json_decode($params, true);
                if ($decoded === null) {
                    $decoded = [];
                    $this->_json_params_error = json_last_error();
                }
            } else {
                parse_str($params, $decoded);
            }
            
            if ($method == 'DELETE')
                $this->deleteVars = $decoded;
            elseif ($method == 'PUT')
                $this->putVars = $decoded;
            elseif ($method == 'PATCH')
                $this->patchVars = $decoded;
            else
                $this->other_params = $decoded;
        }
        
        $this->_import_files();
        // vpe($this->files);
    }
    
    public function __set($prop, $value)
    {
        if ($var = $this->_search($prop))
            global ${$var};
        
        if (is_object($value)) {
            if ($var)
                $this->$prop = ${$var}[$prop];
            else
                $this->$prop = $value;
        } elseif (is_array($value)) {
            if ($var)
                $value = new GlobalVar($value, $var, $prop);
            $this->$prop = $value;
        } else {
            if ($var)
                ${$var}[$prop] = $value;
            else
                $this->$prop = $value;
        }
    }
    
    public function __get($prop)
    {
        $ret = null;
        $var = $this->_search($prop);
        if ($var) {
            global ${$var};
            
            // if (is_array(${$var}[$prop])) {
                // if (isset($this->files[$prop])) {
                    // ${$var}[$prop] = array_merge(${$var}[$prop], $this->files[$prop]);
                // }
                // $this->$prop = new GlobalVar(${$var}[$prop], $var, $prop);
                # Return here.
                // return 
                // return $this->$prop;
            // } elseif (is_object(${$var}[$prop])) {
                // $this->$prop = ${$var}[$prop];
                // $ret = $this->$prop;
            // } else {
                $ret = ${$var}[$prop];
            // }
        } else {
            if (isset($this->putVars[$prop]))
                $ret = $this->putVars[$prop];
            elseif (isset($this->deleteVars[$prop]))
                $ret = $this->deleteVars[$prop];
            elseif (isset($this->patchVars[$prop])) {
                $ret = $this->patchVars[$prop];
            // elseif (isset($this->files[$prop])) {
                # Return here.
                // return $this->files[$prop];
            } elseif (isset($this->other_params[$prop]))
                $ret = $this->other_params[$prop];
        }
        
        // if ($ret && $this->files) {
            // vpe($this->files);
            // $this->mergeWithFiles($ret, $prop);

        // }
        
        return $ret;
    }
    
    public function __isset($prop)
    {
        return $this->_search($prop) || isset($this->deleteVars[$prop]) || isset($this->putVars[$prop]);
    }
    
    public function del($prop)
    {
        unset($this->$prop, $_GET[$prop], $_POST[$prop], $this->deleteVars[$prop], $this->putVars[$prop]);
    }
    
    public function get()
    {
        return $_GET;
    }
    
    public function post()
    {
        return $_POST;
    }
    
    public function delete()
    {
        return $this->deleteVars;
    }
    
    public function put()
    {
        return $this->putVars;
    }
    
    public function patch()
    {
        return $this->patchVars;
    }
    
    public function files()
    {
        return $this->files;
    }
    
    public function user()
    {
        get_object_vars($this);
    }
    
    public function toArray()
    {
        $obj_vars = get_object_vars($this);
        unset($obj_vars['deleteVars'], $obj_vars['putVars'], $obj_vars['_json_params_error'], $obj_vars['patchVars'], $obj_vars['other_params'], $obj_vars['files']);
        
        $ret = array_merge($_POST, $_GET, $obj_vars, $this->deleteVars, $this->putVars, $this->patchVars, $this->other_params/*, $this->files*/);
        return $ret;
    }
    
    public function all()
    {
        return $this->toArray();
    }
    
    public function merge()
    {
        $params = func_get_args();
        array_unshift($params, $this->all());
        return call_user_func_array('array_merge', $params);
    }
    
    public function json_params_error()
    {
        return $this->_json_params_error;
    }
    
    private function _search($prop)
    {
        if (isset($_GET[$prop]))
            return '_GET';
        elseif (isset($_POST[$prop]))
            return '_POST';
        else
            return false;
    }
    
    private function mergeWithFiles(&$array, $prop)
    {
        if (isset($this->files->$prop)) {
            foreach ($this->files->$prop as $key => $value) {
                if (is_array($value)) {
                    if (!isset($array[$key])) {
                        $array[$key] = [];
                    } elseif (!is_array($array[$key])) {
                        $array[$key] = [ $array[$key] ];
                    }
                    $array[$key] = array_merge($array[$key], $value);
                } else {
                    $array[$key] = $value;
                }
            }
        }
    }
    
    private function _import_files()
    {
        if (empty($_FILES)) {
            return;
        }
        
        $this->files = new \stdClass();
    
        foreach ($_FILES as $mainName => $data) {
            if (!is_array($data['name']) && $data['error'] != UPLOAD_ERR_NO_FILE) {
                $this->files->$mainName = new UploadedFile($_FILES[$mainName]);
            } else {
                $this->files->$mainName = $this->_get_subnames($data);
            }
        }
    }
    
    private function _get_subnames(array $arr)
    {
        $arranged = new \ArrayObject();
        // $arranged = [];
        
        foreach ($arr['name'] as $k => $value) {
            if (is_string($value)) {
                if ($arr['error'] != UPLOAD_ERR_NO_FILE) {
                    $arranged[$k] = [
                        'name'     => $value,
                        'type'     => $arr['type'][$k],
                        'tmp_name' => $arr['tmp_name'][$k],
                        'error'    => $arr['error'][$k],
                        'size'     => $arr['size'][$k],
                    ];
                }
            } else {
                $keys = ['name', $k];
                $this->_get_subnames_2($arranged, $keys, $arr);
            }
        }
        
        return $arranged->getArrayCopy();
    }
    
    private function _get_subnames_2($arranged, $keys, $arr)
    {
        $baseArr = $arr;
        foreach ($keys as $key) {
            $baseArr = $baseArr[$key];
        }
        
        foreach ($baseArr as $k => $value) {
            if (is_string($value)) {
                $this->setArranged($arranged, array_merge($keys, [$k]), [
                    'name'     => $value,
                    'type'     => $this->foreachKeys(array_merge(['type'] + $keys, [$k]), $arr),
                    'tmp_name' => $this->foreachKeys(array_merge(['tmp_name'] + $keys, [$k]), $arr),
                    'error'    => $this->foreachKeys(array_merge(['error'] + $keys, [$k]), $arr),
                    'size'     => $this->foreachKeys(array_merge(['size'] + $keys, [$k]), $arr),
                ]);
                // vpe($arranged, $key, $k);
                // $arranged[$k] = $arranged[$k]->getArrayCopy();
            } else {
                $tmpKeys = $keys;
                $tmpKeys[] = $k;
                $this->_get_subnames_2($arranged, $tmpKeys, $arr);
            }
        }
    }
    
    private function foreachKeys($keys, $arr)
    {
        $baseArr = $arr;
        foreach ($keys as $key) {
            $baseArr = $baseArr[$key];
        }
        return $baseArr;
    }
    
    private function setArranged($arr, $keys, $val)
    {
        if ($val['error'] == UPLOAD_ERR_NO_FILE) {
            return;
        }
        
        array_shift($keys);
        $lastKey = array_pop($keys);
        $baseArr = &$arr;
        foreach ($keys as $key) {
            if (!isset($baseArr[$key])) {
                // $baseArr[$key] = new \ArrayObject();
                $baseArr[$key] = [];
            }
            $baseArr = &$baseArr[$key];
        }
        $baseArr[$lastKey] = new UploadedFile($val);
    }
}
