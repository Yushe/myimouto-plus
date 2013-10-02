<?php
namespace Rails\ActionView;

use stdClass;
use Rails;
use Rails\Routing\Traits\NamedPathAwareTrait;

/**
 * Base class for layouts, templates and partials.
 */
abstract class Base extends ActionView
{
    use NamedPathAwareTrait;
    
    /**
     * Local variables passed.
     * This could be either an array (partials) or an stdClass
     * (layouts and templates). They're accessed through __get();
     */
    protected $locals = [];
    
    public function __get($prop)
    {
        return $this->getLocal($prop);
    }
    
    public function __set($prop, $val)
    {
        if (!$this->locals) {
            $this->locals = new stdClass();
        }
        
        $this->setLocal($prop, $val);
    }
    
    public function __call($method, $params)
    {
        if ($helper = ViewHelpers::findHelperFor($method)) {
            $helper->setView($this);
            return call_user_func_array(array($helper, $method), $params);
        } elseif ($this->isNamedPathMethod($method)) {
            return $this->getNamedPath($method, $params);
        }
        
        throw new Exception\BadMethodCallException(
            sprintf("Called to unknown method/helper: %s", $method)
        );
    }
    
    # Remove for 2.0
    public function __isset($prop)
    {
        return $this->localExists($prop);
    }
    
    public function localExists($name)
    {
        if ($this->locals instanceof stdClass) {
            return property_exists($this->locals, $name);
        } else {
            return array_key_exists($name, $this->locals);
        }
    }
    
    public function getLocal($name)
    {
        if (!$this->localExists($name)) {
            throw new Exception\RuntimeException(
                sprintf("Undefined local '%s'", $name)
            );
        }
        
        if ($this->locals instanceof stdClass) {
            return $this->locals->$name;
        } else {
            return $this->locals[$name];
        }
    }
    
    public function setLocal($name, $value)
    {
        if ($this->locals instanceof stdClass) {
            $this->locals->$name = $value;
        } else {
            $this->locals[$name] = $value;
        }
        return $this;
    }
    
    // public function isset_local($name)
    // {
        // if ($this->locals instanceof stdClass)
            // return property_exists($this->locals, $name);
        // elseif (is_array($this->locals))
            // return array_key_exists($name, $this->locals);
    // }
    
    public function I18n()
    {
        return Rails::application()->I18n();
    }
    
    public function t($name, array $params = [])
    {
        $trans = $this->I18n()->t($name, $params);
        if (false === $trans) {
            return '<span class="translation_missing">#' . $name . '</span>';
        }
        return $trans;
    }
    
    /**
     * This method could go somewhere else.
     */
    public function optionsFromEnumColumn($model_name, $column_name, array $extra_options = [])
    {
        $options = [];
        foreach ($model_name::table()->enumValues($column_name) as $val) {
            $options[$this->humanize($val)] = $val;
        }
        
        if ($extra_options) {
            $options = array_merge($extra_options, $options);
        }
        
        return $options;
    }
    
    /**
     * This is meant to be a way to check if
     * there are contentFor awaiting to be ended.
     */
    public function activeContentFor()
    {
        return self::$_content_for_names;
    }
    
    public function setLocals($locals)
    {
        if (!is_array($locals) && !$locals instanceof stdClass)
            throw new Exception\InvalidArgumentException(
                sprintf('Locals must be either an array or an instance of stdClass, %s passed.', gettype($locals))
            );
        $this->locals = $locals;
    }
    
    public function params()
    {
        return Rails::application()->dispatcher()->parameters();
    }
    
    public function request()
    {
        return Rails::application()->dispatcher()->request();
    }
    
    public function partial($name, array $locals = array())
    {
        $ctrlr_name = Rails::services()->get('inflector')->camelize($this->request()->controller(), false);
        // $ctrlr_name = substr_replace(Rails::services()->get('inflector')->camelize($ctrlr_name), substr($ctrlr_name, 0, 1), 0, 1);
        
        if (!isset($locals[$ctrlr_name]) && $this->localExists($ctrlr_name)) {
            $locals[$ctrlr_name] = $this->getLocal($ctrlr_name);
        }
        
        // if (!Rails::config()->ar2) {
            // if (!isset($locals[$name]) && $this->localExists($name)) {
                // $locals[$name] = $this->getLocal($name);
            // }
        // } else {
            $camelized = Rails::services()->get('inflector')->camelize($name, false);
            if (!isset($locals[$camelized]) && $this->localExists($camelized)) {
                $locals[$camelized] = $this->getLocal($camelized);
            }
        // }
        
        $base_path = Rails::config()->paths->views;
        
        if (is_int(strpos($name, '/'))) {
            $pos      = strrpos($name, '/');
            $name     = substr_replace($name, '/_', $pos, 1) . '.php';
            $filename = $base_path . '/' . $name;
        } else {
            if ($namespaces = Rails::application()->dispatcher()->router()->route()->namespaces())
                $base_path .= '/' . implode('/', $namespaces);
            $filename = $base_path . '/' . $this->request()->controller() . '/_' . $name . '.php';
        }
        
        if (isset($locals['collection'])) {
            $collection = $locals['collection'];
            unset($locals['collection']);
            $contents = '';
            foreach ($collection as $member) {
                $locals[$name] = $member;
                $contents .= (new Partial($filename, [], $locals))->render_content();
            }
        } else {
            $partial = new Partial($filename, [], $locals);
            $contents = $partial->render_content();
        }
        
        return $contents;
    }
}
