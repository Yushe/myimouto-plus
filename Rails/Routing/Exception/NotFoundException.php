<?php
namespace Rails\Routing\Exception;

class NotFoundException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title = "Not found";
    
    protected $status = 404;
    
    protected $skip_info = true;
}