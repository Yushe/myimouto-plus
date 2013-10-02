<?php
namespace Rails\ActionView\Template\Exception;

class LayoutMissingException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title = "Layout missing";
}