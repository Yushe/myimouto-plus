<?php
namespace Rails\Exception;

class BadFunctionCallException extends \BadFunctionCallException implements ExceptionInterface
{
    use ExceptionTrait;
}