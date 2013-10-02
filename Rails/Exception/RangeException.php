<?php
namespace Rails\Exception;

class RangeException extends \RangeException implements ExceptionInterface
{
    use ExceptionTrait;
}