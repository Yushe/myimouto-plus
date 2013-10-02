<?php
namespace Rails\Exception;

class DomainException extends \DomainException implements ExceptionInterface
{
    use ExceptionTrait;
}