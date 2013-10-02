<?php
namespace Rails\Exception;

class ErrorException extends \ErrorException implements ExceptionInterface
{
    use ExceptionTrait;
}