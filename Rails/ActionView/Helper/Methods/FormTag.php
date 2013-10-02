<?php
namespace Rails\ActionView\Helper\Methods;

use Closure;
use Rails;
use Rails\ActionController\ActionController;
use Rails\ActionView\Exception;
use Rails\Toolbox\ArrayTools;

trait FormTag
{
    /**
     * Passing an empty value as $action_url will cause the form
     * to omit the "action" attribute, causing the form to be
     * submitted to the current uri.
     *
     * To avoid passing an empty array for $attrs,
     * pass a Closure as second argument and it
     * will be taken as $block.
     *
     * Likewise, passing Closure as first argument
     * (meaning the form will be submitted to the current url)
     * will work too, instead of passing an empty value as 
     * $action_url.
     *
     * Note that if $action_url is an array like [ 'controller' => 'ctrl', 'action' => ... ],
     * it will be taken as $attrs. Therefore the action url should be passed as [ 'ctrl#action', ... ].
     */
    public function formTag($action_url = null, $attrs = [], Closure $block = null)
    {
        if (func_num_args() == 1 && $action_url instanceof Closure) {
            $block = $action_url;
            $action_url = null;
        } elseif (func_num_args() == 2 && is_array($action_url) && is_string(key($action_url))) {
            $block = $attrs;
            $attrs = $action_url;
            $action_url = null;
        } elseif ($attrs instanceof Closure) {
            $block = $attrs;
            $attrs = [];
        }
        
        if (!$block instanceof Closure)
            throw new Exception\BadMethodCallException("One of the arguments must be a Closure");
        
        if (empty($attrs['method'])) {
            $attrs['method'] = 'post';
            $method = 'post';
        } elseif (($method = strtolower($attrs['method'])) != 'get') {
            $attrs['method'] = 'post';
        }
        
        # Check special attribute 'multipart'.
        if (!empty($attrs['multipart'])) {
            $attrs['enctype'] = 'multipart/form-data';
            unset($attrs['multipart']);
        }
        
        if ($action_url)
            $attrs['action'] = Rails::application()->router()->urlFor($action_url);
        
        ob_start();
        if ($method != 'get' && $method != 'post')
            echo $this->hiddenFieldTag('_method', $method, ['id' => '']);
        $block();
        return $this->contentTag('form', ob_get_clean(), $attrs);
    }

    public function formFieldTag($type, $name, $value = null, array $attrs = [])
    {
        return $this->_form_field_tag($type, $name, $value, $attrs);
    }
    
    public function submitTag($value, array $attrs = [])
    {
        $attrs['type'] = 'submit';
        $attrs['value'] = $value;
        !isset($attrs['name']) && $attrs['name'] = 'commit';
        return $this->tag('input', $attrs);
    }
    
    public function textFieldTag($name, $value = null, array $attrs = [])
    {
        if (is_array($value)) {
            $attrs = $value;
            $value = null;
        }
        return $this->_form_field_tag('text', $name, $value, $attrs);
    }
    
    public function hiddenFieldTag($name, $value, array $attrs = [])
    {
        return $this->_form_field_tag('hidden', $name, $value, $attrs);
    }
    
    public function passwordFieldTag($name, $value = null, array $attrs = array())
    {
        return $this->_form_field_tag('password', $name, $value, $attrs);
    }
    
    public function checkBoxTag($name, $value = '1', $checked = false, array $attrs = [])
    {
        if ($checked) {
            $attrs['checked'] = 'checked';
        }
        return $this->_form_field_tag('checkbox', $name, $value, $attrs);
    }
    
    public function textAreaTag($name, $value, array $attrs = [])
    {
        if (isset($attrs['size']) && is_int(strpos($attrs['size'], 'x'))) {
            list($attrs['cols'], $attrs['rows']) = explode('x', $attrs['size']);
            unset($attrs['size']);
        }
        return $this->_form_field_tag('textarea', $name, $value, $attrs, true);
    }
    
    public function radioButtonTag($name, $value, $checked = false, array $attrs = [])
    {
        if ($checked)
            $attrs['checked'] = 'checked';
        return $this->_form_field_tag('radio', $name, $value, $attrs);
    }
    
    # $options may be closure, collection or an array of name => values.
    public function selectTag($name, $options, array $attrs = [])
    {
        # This is found also in Form::select()
        if (!is_string($options)) {
            if (is_array($options) && ArrayTools::isIndexed($options) && count($options) == 2)
                list ($options, $value) = $options;
            else
                $value = null;
            $options = $this->optionsForSelect($options, $value);
        }
        
        if (isset($attrs['prompt'])) {
            $options = $this->contentTag('option', $attrs['prompt'], ['value' => '', 'allow_blank_attrs' => true]) . "\n" . $options;
            unset($attrs['prompt']);
        }
        $attrs['value'] = $attrs['type'] = null;
        
        $select_tag = $this->_form_field_tag('select', $name, $options, $attrs, true);
        return $select_tag;
    }
    
    /**
     * Note: For options to recognize the $tag_value, it must be identical to the option's value.
     */
    public function optionsForSelect($options, $tag_value = null)
    {
        # New feature: accept anonymous functions that will return options.
        if ($options instanceof Closure) {
            $options = $options();
        /**
         * New feature: accept collection as option in index 0, in index 1 the option name and in index 2 the value
         * which are the properties of the models that will be used.
         * Example: [ Category::all(), 'name', 'id' ]
         * The second and third indexes can be either:
         *  - An attribute name
         *  - A public property
         *  - A method of the model that will return the value for the option/name
         */
        } elseif (is_array($options) && count($options) == 3 && ArrayTools::isIndexed($options) && $options[0] instanceof \Rails\ActiveModel\Collection) {
            list($models, $optionName, $valueName) = $options;
            
            $options = [];
            if ($models->any()) {
                $modelClass = get_class($models[0]);
            
                foreach ($models as $m) {
                    if ($modelClass::isAttribute($optionName)) {
                        $option = $m->getAttribute($optionName);
                    } elseif ($modelClass::hasPublicProperty($optionName)) {
                        $option = $m->$optionName;
                    } else {
                        # We assume it's a method.
                        $option = $m->$optionName();
                    }
                    
                    if ($modelClass::isAttribute($valueName)) {
                        $value = $m->getAttribute($valueName);
                    } elseif ($modelClass::hasPublicProperty($optionName)) {
                        $option = $m->$optionName;
                    } else {
                        # We assume it's a method.
                        $value = $m->$valueName();
                    }
                    
                    $options[$option] = $value;
                }
            }
        }
        $tag_value = (string)$tag_value;
        
        $tags = [];
        foreach ($options as $name => $value) {
            $value = (string)$value;
            $tags[] = $this->_form_field_tag('option', null, $name, array('selected' => $value === $tag_value ? '1' : '', 'id' => '', 'value' => $value), true);
        }
        
        return implode("\n", $tags);
    }
    
    private function _form_field_tag($field_type, $name = null, $value, array $attrs = [], $content_tag = false)
    {
        !isset($attrs['id']) && $attrs['id'] = trim(str_replace(['[', ']', '()', '__'], ['_', '_', '', '_'], $name), '_');
        $name && $attrs['name'] = $name;
        $value = (string)$value;
        
        if ($content_tag) {
            return $this->contentTag($field_type, $value, $attrs);
        } else {
            $attrs['type'] = $field_type;
            $value !== '' && $attrs['value'] = $value;
            return $this->tag('input', $attrs);
        }
    }
}