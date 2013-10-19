<?php
namespace Rails\ActionController\Exception;

class DoubleRenderException extends \Rails\Exception\LogicException implements ExceptionInterface
{
    protected $title = 'Double Render Error';
}
