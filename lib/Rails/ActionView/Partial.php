<?php
namespace Rails\ActionView;

class Partial extends Template
{
    public function render_content()
    {
        ob_start();
        $this->_init_render();
        return ob_get_clean();
    }
    
    public function t($name, array $params = [])
    {
        return parent::t($name, $params);
    }
}