<?php
namespace Rails\ActionView\Template;

class Renderer
{
    private $template;
    
    private $file;
    
    public function __construct($template, $file)
    {
        $this->template = $template;
        $this->file = $file;
    }
    
    public function __get($prop)
    {
        return $this->getLocal($prop);
    }
    
    public function __set($prop, $value)
    {
        $this->setLocal($prop);
    }
    
    public function getLocal($prop)
    {
        return $this->template->getLocal($prop);
    }
    
    public function setLocal($prop, $value)
    {
        $this->template->setLocal($prop);
    }
}
