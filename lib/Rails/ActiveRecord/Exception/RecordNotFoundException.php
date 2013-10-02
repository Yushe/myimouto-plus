<?php
namespace Rails\ActiveRecord\Exception;

class RecordNotFoundException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title = 'Record not found';
    
    protected $status = 404;
}