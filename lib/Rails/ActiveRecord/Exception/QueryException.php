<?php
namespace Rails\ActiveRecord\Exception;

class QueryException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    use QueryAwareTrait;
}
