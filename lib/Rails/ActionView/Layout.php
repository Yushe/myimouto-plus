<?php
namespace Rails\ActionView;

use Rails;

class Layout extends Template
{
    protected $template_filename;
    
    protected $filename;
    
    public function __construct($layoutName, array $params = [], $locals = [])
    {
        $this->template_filename = $layoutName;
        
        $this->filename = $this->resolve_layout_file($layoutName);
        
        if (isset($params['contents'])) {
            $this->_buffer = $params['contents'];
        }
        
        $locals && $this->setLocals($locals);
    }
    
    public function renderContent()
    {
        ob_start();
        require $this->filename;
        $this->_buffer = ob_get_clean();
    }
}