<?php
namespace Rails\Exception;

use Rails;

trait ExceptionTrait
{
    /**
     * Title to show instead of the class name.
     * Stetic purposes only.
     */
    protected
        $title,
        $_title,
        $_reduced_trace;
    
    protected $skip_info = false;
    
    protected $status = 500;
    
    private $_error_html;
    
    public function title()
    {
        $title = $this->title ?: $this->_title;
        
        // if (!empty($params['scope'])) {
            // $repl = '%scope%';
            // $title = str_replace($repl, $params['scope'], $title);
        // }
        
        return $title;
    }
    
    public function shift_trace()
    {
        if (!$this->_reduced_trace)
            $this->_reduced_trace = $this->getTrace();
        array_shift($this->_reduced_trace);
    }
    
    public function get_trace()
    {
        return $this->_reduced_trace;
    }
    
    public function skip_info()
    {
        return $this->skip_info;
    }
    
    public function status()
    {
        return $this->status;
    }
    
    public function postMessage()
    {
        return '';
    }
}