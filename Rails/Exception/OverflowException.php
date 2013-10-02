<?php
namespace Rails\Exception;

class OverflowException extends \OverflowException implements ExceptionInterface
{
    use ExceptionTrait;
}