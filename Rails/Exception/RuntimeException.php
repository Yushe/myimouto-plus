<?php
namespace Rails\Exception;

class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    use ExceptionTrait;
}