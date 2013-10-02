<?php
namespace Rails\Exception;

class OutOfRangeException extends \OutOfRangeException implements ExceptionInterface
{
    use ExceptionTrait;
}