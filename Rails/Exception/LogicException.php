<?php
namespace Rails\Exception;

class LogicException extends \LogicException implements ExceptionInterface
{
    use ExceptionTrait;
}