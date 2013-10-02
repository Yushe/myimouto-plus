<?php
namespace Rails\ActionMailer;

class Template extends \Rails\ActionView\Base
{
    private
        $_template_file,
        $_contents;
    
    public function __construct($template_file)
    {
        if (!is_file($template_file)) {
            throw new Exception\RuntimeException(
                sprintf("Template file %s doesn't exist", $template_file)
            );
        }
        $this->_template_file = $template_file;
    }
    
    public function renderContent()
    {
        ob_start();
        require $this->_template_file;
        $this->_contents = ob_get_clean();
        return $this->_contents;
    }
    
    public function contents()
    {
        return $this->_contents;
    }
}