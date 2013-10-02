<?php
namespace Rails\I18n\Locales;

abstract class AbstractLocales
{
    protected $translations = [];
    
    public function tr()
    {
        return $this->translations;
    }
}
