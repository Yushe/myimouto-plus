<?php
namespace Rails\Routing\UrlHelpers\Exception;

class RuntimeException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $status = 404;
}