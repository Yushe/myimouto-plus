<?php
namespace Rails\ActionController\Response;

class Json extends Base
{
    private $_json;
    
    private $_header_params;
    
    public function __construct($json)
    {
        $this->_json = $json;
    }
    
    public function _render_view()
    {
        if ($this->_json instanceof \Rails\ActiveRecord\Base)
            $this->_json = $this->_json->toJson();
        elseif ($this->_json instanceof \Rails\ActiveRecord\Collection) {
            $this->_json = $this->_json->toJson();
        } elseif (isset($this->_json['json'])) {
            if (!is_string($this->_json['json']))
                $this->_json = json_encode($this->_json['json']);
            else
                $this->_json = $this->_json['json'];
        } elseif (!is_string($this->_json)) {
            if (is_array($this->_json)) {
                $json = [];
                foreach ($this->_json as $key => $val) {
                    $json[$key] = $this->_to_array($val);
                }
                $this->_json = $json;
            }
            $this->_json = json_encode($this->_json);
        }
    }
    
    public function _print_view()
    {
        return $this->_json;
    }
    
    private function _to_array($val)
    {
        if ($val instanceof \Rails\ActiveRecord\Collection) {
            $json = [];
            foreach ($val as $obj)
                $json[] = $this->_to_array($obj);
            return $json;
        } elseif (is_object($val)) {
            return (array)$val;
        } else
            return $val;
    }
}