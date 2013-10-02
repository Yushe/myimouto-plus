<?php
namespace Rails\Exception;

class UnexpectedValueException extends \UnexpectedValueException implements ExceptionInterface
{
    use ExceptionTrait;
}