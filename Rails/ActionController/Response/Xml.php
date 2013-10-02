<?php
namespace Rails\ActionController\Response;

class Xml extends Base
{
    private $_xml;
    
    public function _render_view()
    {
        $el = array_shift($this->_params);
        
        if ($el instanceof \Rails\ActiveRecord\Collection) {
            $this->_xml = new \Rails\ActionView\Xml();
            $root = $this->_params['root'];
            $this->_xml->instruct();
            $this->_xml->$root([], function() use ($el) {
                foreach ($el as $model) {
                    $model->toXml(['builder' => $this->_xml, 'skip_instruct' => true]);
                }
            });
        } else
            $this->_xml = new \Rails\Xml\Xml($el, $this->_params);
    }
    
    public function _print_view()
    {
        return $this->_xml->output();
    }
}