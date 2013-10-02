<?php
namespace Rails\ActionController\Response\Exception;

class ViewNotFoundException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title = "View not found";
}