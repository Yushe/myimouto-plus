<?php
namespace Rails\Log\Formatter;

class Simple extends \Zend\Log\Formatter\Simple
{
    const DEFAULT_FORMAT = '%priorityName%: %message% %extra%';
    
    protected $format    = '%priorityName%: %message% %extra%';
    
    protected $originalFormat;
    
    public function format($event)
    {
        $prevFormat = null;
        /**
         * Hack: write log without priorityName.
         */
        if ($event['priorityName'] == 'NONE') {
            $this->originalFormat = $this->format;
            $this->format = trim(str_replace('%priorityName%', '', $this->format), ': ');
        }
        
        /**
         * Hack: write log with date.
         */
        if (!empty($event['extra']['date'])) {
            if (!$this->originalFormat) {
                $this->originalFormat = $this->format;
            }
            $this->format = '[%timestamp%] ' . $this->format;
            unset($event['extra']['date']);
        }
        
        $ret = parent::format($event);
        
        if ($this->originalFormat) {
            $this->format = $this->originalFormat;
            $this->originalFormat = null;
        }
        
        return $ret;
    }
}