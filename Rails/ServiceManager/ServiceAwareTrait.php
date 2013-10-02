<?php
namespace Rails\ServiceManager;

trait ServiceAwareTrait
{
    public function getService($name)
    {
        return \Rails::services()->get($name);
    }
}
