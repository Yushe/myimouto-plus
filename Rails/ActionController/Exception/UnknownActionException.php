<?php
namespace Rails\ActionController\Exception;

class UnknownActionException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title = 'Unknown action';
    
    protected $status = 404;
    
    protected $skip_info = true;
}