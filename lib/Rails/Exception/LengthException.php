<?php
namespace Rails\Exception;

class LengthException extends \LengthException implements ExceptionInterface
{
    use ExceptionTrait;
}