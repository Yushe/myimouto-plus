<?php
namespace Rails\Routing\Exception;

class RoutingErrorException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title = "Routing Error";
    
    protected $status = 404;
    
    protected $skip_info = true;
}