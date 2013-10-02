<?php
namespace Rails\ActionMailer;

use stdClass;
use Zend\Mime;
use Rails;
use Rails\Mail\Mail;

abstract class Base
{
    public $from;
    
    public $to;
    
    public $subject;
    
    /**
     * TODO: this isn't used anywhere.
     */
    public $partsOrder = ['text/plain', 'text/html'];
    
    public $templatePath;
    
    public $charset;
    
    public $textCharset;
    
    public $htmlCharset;
    
    public $templateName;
    
    public $attachments = [];
    
    public $calledMethod;
    
    public $headers = [];
    
    protected $vars;
    
    static public function mail($method, array $params = [], array $headers = [])
    {
        $cn = get_called_class();
        $mailer = new $cn();
        $mailer->calledMethod = $method;
        $mailer->headers = array_merge($mailer->headers, $headers);
        if (false !== call_user_func_array([$mailer, $method], $params))
            return $mailer->createMail();
    }
    
    public function __construct()
    {
        $this->vars = new stdClass();
        $this->init();
    }
    
    public function __set($prop, $value)
    {
        $this->vars->$prop = $value;
    }
    
    public function __get($prop)
    {
        if (!isset($this->vars->$prop))
            return null;
        return $this->vars->$prop;
    }
    
    /**
     * Just a quicker way to add an attachment.
     */
    public function attachment($name, $content)
    {
        if (!is_string($content) && (!is_resource($content) || get_resource_type($content) != 'stream')) {
            throw new Exception\InvalidArgumentException(
                sprintf("Attachment content must be either string or stream, %s passed")
            );
        }
        
        $this->attachments[$name] = [
            'content' => $content
        ];
    }
    
    /**
     * Just a quicker way to add an inline attachment.
     */
    public function inlineAttachment($name, $content)
    {
        if (!is_string($content) && (!is_resource($content) || get_resource_type($content) != 'stream')) {
            throw new Exception\InvalidArgumentException(
                sprintf("Attachment content must be either string or stream, %s passed")
            );
        }
        
        $this->attachments[$name] = [
            'content' => $content,
            'inline'  => true
        ];
    }
    
    public function vars()
    {
        return $this->vars;
    }
    
    /**
     * Default values for properties can be set in this method.
     */
    protected function init()
    {
    }
    
    private function createMail()
    {
        if (Rails::config()->action_mailer->defaults) {
            if (!Rails::config()->action_mailer->defaults instanceof \Closure) {
                throw new Exception\RuntimeException(
                    'Configuration action_mailer.defaults must be a closure'
                );
            }
            $closure = clone Rails::config()->action_mailer->defaults;
            $closure = $closure->bindTo($this);
            $closure();
        }
        
        if (!$this->templateName)
            $this->templateName = $this->calledMethod;
        
        if (!$this->templatePath)
            $this->templatePath = Rails::services()->get('inflector')->underscore(get_called_class());
        
        $deliverer = new Deliverer($this);
        return $deliverer;
    }
}
