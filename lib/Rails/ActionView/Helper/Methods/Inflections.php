<?php
namespace Rails\ActionView\Helper\Methods;

trait Inflections
{
    public function pluralize($word, $locale = 'en')
    {
        return $this->inflector()->pluralize($word, $locale);
    }
    
    public function singularize($word, $locale = 'en')
    {
        return $this->inflector()->singularize($word, $locale);
    }
    
    public function camelize($term, $uppercaseFirstLetter = true)
    {
        return $this->inflector()->camelize($term, $uppercaseFirstLetter);
    }
    
    public function underscore($camelCasedWord)
    {
        return $this->inflector()->underscore($camelCasedWord);
    }
    
    public function humanize($lowerCaseAndUnderscoredWord)
    {
        return $this->inflector()->humanize($lowerCaseAndUnderscoredWord);
    }
    
    public function titleize($word)
    {
        return $this->inflector()->titleize($word);
    }
    
    public function tableize($className)
    {
        return $this->inflector()->tableize($className);
    }
    
    public function classify($tableName)
    {
        return $this->inflector()->classify($tableName);
    }
    
    public function ordinal($number)
    {
        return $this->inflector()->ordinal($number);
    }
    
    public function inflector()
    {
        return \Rails::services()->get('inflector');
    }
}