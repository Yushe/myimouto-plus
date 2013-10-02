<?php
namespace Rails\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    use ExceptionTrait;
}