<?php
namespace Rails\Exception;

class UnderflowException extends \UnderflowException implements ExceptionInterface
{
    use ExceptionTrait;
}