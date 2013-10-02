<?php
namespace Rails\ActionController;

use Rails\ActionDispatch\ActionDispatch;

class Response extends ActionDispatch
{
    private
        /**
         * ActionController_Cookies instance
         */
        $cookies,
        
        $body;
    
    /**
     * If set to false, cookies and headers won't be send.
     */
    protected $send_headers = true;
    
    public function cookies()
    {
        if (!$this->cookies)
            $this->cookies = new Cookies();
        return $this->cookies;
    }
    
    public function headers()
    {
        return \Rails::application()->dispatcher()->headers();
    }
    
    public function body($body = null)
    {
        if (null !== $body) {
            $this->body = $body;
            return $this;
        } else {
            return $this->body;
        }
    }
    
    public function sendHeaders($value = null)
    {
        if ($value === null)
            return $this->send_headers;
        else {
            $this->send_headers = $value;
            return $this;
        }
    }
    
    public function send_headers($value = null)
    {
        if ($value === null)
            return $this->send_headers;
        else {
            $this->send_headers = $value;
            return $this;
        }
    }
    
    protected function _init()
    {
    }
    
    protected function _respond()
    {
        $this->_send_headers();
        echo $this->body;
        $this->body = null;
    }
    
    private function _send_headers()
    {
        if ($this->send_headers) {
            $this->headers()->send();
            $this->cookies()->set();
        }
    }
}