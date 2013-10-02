<?php
namespace Rails\ActionView;

class FormBuilder
{
    protected $helper;
    
    protected $model;
    
    protected $inputNamespace;
    
    public function __construct($helper, $model)
    {
        $this->helper = $helper;
        $this->model  = $model;
        $this->inputNamespace = \Rails::services()->get('inflector')->underscore(get_class($model));
    }
    
    public function textField($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->textField($this->inputNamespace, $property, $attrs);
    }
    
    public function hiddenField($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($model);
        return $this->helper->hiddenField($this->inputNamespace, $property, $attrs);
    }
    
    public function passwordField($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($model);
        return $this->helper->passwordField($this->inputNamespace, $property, $attrs);
    }
    
    public function checkBox($property, array $attrs = array(), $checked_value = '1', $unchecked_value = '0')
    {
        $this->helper->setDefaultModel($model);
        return $this->helper->passwordField($this->inputNamespace, $property, $attrs, $checked_value, $unchecked_value);
    }
    
    public function textArea($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($model);
        return $this->helper->textArea($this->inputNamespace, $property, $attrs);
    }
    
    public function select($property, $options, array $attrs = array())
    {
        $this->helper->setDefaultModel($model);
        return $this->helper->select($this->inputNamespace, $property, $options, $attrs);
    }
    
    public function radioButton($property, $tag_value, array $attrs = array())
    {
        $this->helper->setDefaultModel($model);
        return $this->helper->radioButton($this->inputNamespace, $property, $tag_value, $attrs);
    }
    
}
