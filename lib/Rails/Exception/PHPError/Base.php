<?php
namespace Rails\Exception\PHPError;

use Rails\Exception\ErrorException;

abstract class Base extends ErrorException implements ExceptionInterface
{
    protected $error_data;
    
    public function error_data(array $edata = [])
    {
        if (!func_get_args())
            return $this->error_data;
        else
            $this->error_data = $edata;
    }
}