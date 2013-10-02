<?php
namespace Rails\ActionView\Helper\Methods;

use Rails;
use Rails\Routing\UrlToken;
use Rails\ActionController\ActionController;

/**
 * $property may be a method by adding () at the end.
 * E.g. $this->text_field('artist', 'member_names()');
 */
trait Form
{
    private $default_model;
    
    public function formField($type, $model, $property, array $attrs = array())
    {
        return $this->_form_field($type, $model, $property, $attrs);
    }
    
    public function formFor(Rails\ActiveRecord\Base $model, $attrs, \Closure $block = null)
    {
        if ($attrs instanceof \Closure) {
            $block = $attrs;
            $attrs = [];
        }
        
        if (!isset($attrs['html']))
            $attrs['html'] = [];
        
        if (!isset($attrs['url'])) {
            
            // if (Rails::config()->ar2) {
                $className = get_class($model);
                if (($primaryKey = $className::table()->primaryKey()) && $model->getAttribute($primaryKey)) {
                    $action = 'update';
                } else {
                    $action = 'create';
                }
            // } else {
                // $action = $model->id ? 'update' : 'create';
            // }
            
            $attrs['url'] = ['#' . $action];
        } else {
            $token = new UrlToken($attrs['url'][0]);
            $action = $token->action();
        }
        
        $html_attrs = $attrs['html'];
        
        if (!isset($html_attrs['method'])) {
            if ($action == 'create')
                $html_attrs['method'] = 'post';
            elseif ($action == 'destroy')
                $html_attrs['method'] = 'delete';
            else
                $html_attrs['method'] = 'put';
        }
        
        # Check special attribute 'multipart'.
        if (!empty($html_attrs['multipart'])) {
            $html_attrs['enctype'] = 'multipart/form-data';
            unset($html_attrs['multipart']);
        }
        
        if ($html_attrs['method'] != 'post') {
            $method = $html_attrs['method'];
            $html_attrs['method'] = 'post';
        } else {
            $method = 'post';
        }
        
        $url_token = new UrlToken($attrs['url'][0]);
        
        if ($url_token->action() == 'create') {
            $action_url = Rails::application()->router()->urlFor($url_token->token());
        } else {
            list($route, $action_url) = Rails::application()->router()->url_helpers()->find_route_for_token($url_token->token(), $model);
        }
        
        $html_attrs['action'] = $action_url;
        
        ob_start();
        if ($method != 'post')
            echo $this->hiddenFieldTag('_method', $method, ['id' => '']);
        
        $block(new \Rails\ActionView\FormBuilder($this, $model));
        
        return $this->contentTag('form', ob_get_clean(), $html_attrs);
    }

    public function textField($model, $property, array $attrs = array())
    {
        return $this->_form_field('text', $model, $property, $attrs);
    }
    
    public function hiddenField($model, $property, array $attrs = array())
    {
        return $this->_form_field('hidden', $model, $property, $attrs);
    }
    
    public function passwordField($model, $property, array $attrs = array())
    {
        return $this->_form_field('password', $model, $property, array_merge($attrs, ['value' => '']));
    }
    
    public function checkBox($model, $property, array $attrs = array(), $checked_value = '1', $unchecked_value = '0')
    {
        if ($this->_get_model_property($model, $property))
            $attrs['checked'] = 'checked';
        
        $attrs['value'] = $checked_value;
        
        $hidden = $this->tag('input', array('type' => 'hidden', 'name' => $model.'['.$property.']', 'value' => $unchecked_value));
        
        $check_box = $this->_form_field('checkbox', $model, $property, $attrs);
        
        return $hidden . "\n" . $check_box;
    }
    
    public function textArea($model, $property, array $attrs = array())
    {
        if (isset($attrs['size']) && is_int(strpos($attrs['size'], 'x'))) {
            list($attrs['cols'], $attrs['rows']) = explode('x', $attrs['size']);
            unset($attrs['size']);
        }
        return $this->_form_field('textarea', $model, $property, $attrs, true);
    }
    
    public function select($model, $property, $options, array $attrs = array())
    {
        if (!is_string($options)) {
            $value = $this->_get_model_property($model, $property);
            $options = $this->optionsForSelect($options, $value);
        }
        
        if (isset($attrs['prompt'])) {
            $options = $this->contentTag('option', $attrs['prompt'], ['value' => '', 'allow_blank_attrs' => true]) . "\n" . $options;
            unset($attrs['prompt']);
        }
        
        $attrs['value'] = $options;
        
        return $this->_form_field('select', $model, $property, $attrs, true);
    }
    
    public function radioButton($model, $property, $tag_value, array $attrs = array())
    {
        (string)$this->_get_model_property($model, $property) == (string)$tag_value && $attrs['checked'] = 'checked';
        $attrs['value'] = $tag_value;
        return $this->_form_field('radio', $model, $property, $attrs);
    }
    
    public function fileField($model, $property, array $attrs = array())
    {
        return $this->_form_field('file', $model, $property, $attrs);
    }
    
    public function setDefaultModel(\Rails\ActiveRecord\Base $model)
    {
        $this->default_model = $model;
    }
    
    private function _form_field($field_type, $model, $property, array $attrs = array(), $content_tag = false)
    {
        $value = array_key_exists('value', $attrs) ? $attrs['value'] : $this->_get_model_property($model, $property);
        
        # Note here that the name tag attribute is forced to be underscored.
        $underscoreProperty = preg_match('/[A-Z]/', $property) ?
                              Rails::services()->get('inflector')->underscore($property) : $property;
        
        $attrs['name'] = $model.'['.$underscoreProperty.']';
        
        if (!isset($attrs['id']))
            $attrs['id'] = $model . '_' . $underscoreProperty;
        
        if ($content_tag) {
            unset($attrs['value']);
            return $this->contentTag($field_type, $value, $attrs);
        } else {
            $attrs['type'] = $field_type;
            if ($value !== '')
                $attrs['value'] = $value;
            return $this->tag('input', $attrs);
        }
    }
    
    private function _get_model_property($model, $property)
    {
        $value = '';
        $mdl = false;
        
        if ($this->default_model) {
            $mdl = $this->default_model;
        } else {
            $vars  = Rails::application()->dispatcher()->controller()->vars();
            if (!empty($vars->$model)) {
                $mdl = $vars->$model;
            }
        }

        if ($mdl) {
            // if (!Rails::config()->ar2) {
                if ($mdl->isAttribute($property)) {
                    $value = (string)$mdl->$property;
                } elseif (($modelProps = get_class_vars(get_class($mdl))) && array_key_exists($property, $modelProps)) {
                    $value = (string)$mdl->$property;
                } else {
                    # It's assumed this is a method.
                    $value = (string)$mdl->$property();
                }
            // } else {
                // /**
                 // * 
                 // */
                // $value = (string)$mdl->$property();
            // }
        }
        
        $this->default_model = null;
        return $value;
    }
}
