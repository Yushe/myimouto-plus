<?php
namespace Rails\ActiveRecord\Validation;

use Rails;
use Rails\Validation\Validation as RailsValidation;

/**
 * A validation could be made with a Closure, which will receive
 * an argument (the value of the property) and must return true
 * and only true if the validation is passed.
 *
 * 'name' => array(
 *     'length' => '5..',
 *     'format' => function($name) { ... return true; },
 * )
 */
class Validator extends RailsValidation
{
    private
        $_model,
    
        $_action,
    
        $_property,
    
        /**
         * Helps for validations that could have
         * different messages (e.g. length (minimum, maximum, is))
         */
        $_error_message_type = 'default',
        
        $_error_message,
    
        $_continue_validation;
    
    public function set_params($action, Rails\ActiveRecord\Base $model, $property)
    {
        $this->_model    = $model;
        $this->_action   = $action;
        $this->_property = $property;
    }
    
    public function validate()
    {
        // if (current($this->_params) instanceof \Closure)
            // $this->_run_closure();
        // else
        $this->_check_conditions();
        
        if ($this->_continue_validation)
            parent::validate();
        
        return $this;
    }
    
    public function set_error_message()
    {
        if (isset($this->_params['base_message']))
            $this->_model->errors()->addToBase($this->_params['base_message']);
        else {
            $this->_set_error_message();
            $this->_model->errors()->add($this->_property, $this->_error_message);
        }
    }
    
    protected function _validate_number()
    {
        if (isset($this->_params['allow_null']) && $this->_model->getAttribute($this->_property) === null)
            return true;
        else
            return parent::_validate_number();
    }
    
    protected function _validate_length()
    {
        if (isset($this->_params['allow_null']) && $this->_model->getAttribute($this->_property) === null)
            return true;
        elseif (isset($this->_params['allow_blank']) && $this->_model->getAttribute($this->_property) === '')
            return true;
        else
            return parent::_validate_length();
    }
    
    protected function _validate_uniqueness()
    {
        $cn = get_class($this->_model);
        if ($this->_model->isNewRecord()) {
            $query = $cn::where('`'.$this->_property.'` = ?', $this->_model->getAttribute($this->_property));
        } else {
            $query = $cn::where('`'.$this->_property.'` = ? AND id != ?', $this->_model->getAttribute($this->_property), $this->_model->getAttribute('id'));
        }
        return !((bool)$query->first());
    }
    
    protected function _validate_presence()
    {
        $property = trim($this->_model->{$this->_property});
        return !empty($property);
    }
    
    protected function _validate_confirmation()
    {
        $property = Rails::services()->get('inflector')->camelize($this->_property, false) . 'Confirmation';
        
        if ($this->_model->$property === null)
            return true;
        
        return (string)$this->_model->getProperty($this->_property) == (string)$this->_model->$property;
    }
    
    protected function _validate_acceptance()
    {
        return !empty($this->_model->{$this->_property});
    }
    
    private function _run_closure()
    {
        $closure = current($this->_params);
        if ($closure($this->_model->{$this->_property}) === true) {
            $this->_success = true;
        }
    }
    
    private function _check_conditions()
    {
        if (!isset($this->_params['on']))
            $this->_params['on'] = 'save';
        $this->_run_on();
        
        if (isset($this->_params['if']))
            $this->_run_if();
    }
    
    private function _run_on()
    {
        if ($this->_params['on'] == 'save' || $this->_params['on'] == $this->_action)
            $this->_continue_validation = true;
        else
            $this->_success = true;
    }
    
    private function _run_if()
    {
        if (is_array($this->_params['if'])) {
            foreach ($this->_params['if'] as $cond => $params) {
                if ($params instanceof \Closure) {
                    if ($params() !== true) {
                        $this->_success = true;
                        $this->_continue_validation = false;
                        return;
                    }
                } else {
                    switch ($cond) {
                        case 'property_exists':
                            if ($this->_model->$params === null) {
                                $this->_success = true;
                                $this->_continue_validation = false;
                                return;
                            }
                            break;
                    }
                }
            }
        } else {
            throw new Exception\RuntimeException(
                sprintf("Validation condition must be an array, %s passed", gettype($this->_params['if']))
            );
        }
        
        $this->_continue_validation = true;
    }
    
    private function _set_error_message()
    {
        $message = '';
        $this->_define_error_message_type();
        
        if ($this->_error_message_type != 'default') {
            if (isset($this->_params[$this->_error_message_type]))
                $message = $this->_params[$this->_error_message_type];
        }
        if (!$message)
            $message = $this->_error_message();
        $this->_error_message = $message;
    }
    
    private function _define_error_message_type()
    {
        switch ($this->_type) {
            case 'length':
                if ($this->_result == -1)
                    $msg_type = 'too_short';
                elseif ($this->_result == 1)
                    $msg_type = 'too_long';
                else
                    $msg_type = 'wrong_length';
                break;
            default:
                $msg_type = 'default';
                break;
        }
        $this->_error_message_type = $msg_type;
    }
    
    private function _error_message()
    {
        switch ($this->_type) {
            case 'number':
            case 'length':
                if ($this->_result == 2 && (!empty($this->_params['even']) || !empty($this->_params['odd']))) {
                    $type = !empty($this->_params['even']) ? "even" : "odd";
                    $params = ["odd"];
                } else {
                    if (!empty($this->_params['in'])) {
                        if ($this->_result == -1) {
                            $params = ['greater_than_or_equal_to', ['count' => $this->_params['in'][0]]];
                        } else {
                            $params = ['less_than_or_equal_to', ['count' => $this->_params['in'][1]]];
                        }
                    } elseif (!empty($this->_params['is'])) {
                        $params = ['equal_to', ['count' => $this->_params['is']]];
                    } elseif (!empty($this->_params['minimum'])) {
                        $params = ['greater_than_or_equal_to', ['count' => $this->_params['minimum']]];
                    } elseif (!empty($this->_params['maximum'])) {
                        $params = ['less_than_or_equal_to', ['count' => $this->_params['maximum']]];
                    }
                }
                break;
            
            case 'blank':
                $params = ['blank'];
                break;
            
            case 'uniqueness':
                $params = ['uniqueness'];
                break;
            
            case 'confirmation':
                $params = ["confirmation"];
                break;
            
            default:
                $params = ['invalid'];
                break;
        }
        $params[0] = 'errors.messages.' . $params[0];
        return call_user_func_array([\Rails::application()->I18n(), 't'], $params);
    }
}
