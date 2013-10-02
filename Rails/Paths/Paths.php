<?php
namespace Rails\Paths;

class Paths extends \Rails\Config\Config
{
    public function __get($prop)
    {
        if ($this->offsetExists($prop)) {
            return $this->offsetGet($prop);
        }
        throw new \Rails\Exception\RuntimeException(
            sprintf("Trying to get undefined path %s", $prop)
        );
    }
}