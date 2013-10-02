<?php
namespace Rails\Exception;

class OutOfBoundsException extends \OutOfBoundsException implements ExceptionInterface
{
    use ExceptionTrait;
}