<?php
namespace Rails\ActionController\Response;

use Rails;
use Rails\ActionView;

class View extends Base
{
    private $_xml;
    
    public function _render_view()
    {
        try {
            ActionView\ViewHelpers::load();
            
            $this->_renderer = new ActionView\Template($this->_params, $this->_params['layout']);
            
            $locals = Rails::application()->controller()->vars();
            
            if (!empty($this->_params['is_xml'])) {
                $this->_xml = new ActionView\Xml();
                $locals->xml = $this->_xml;
            }
            
            $this->_renderer->setLocals($locals);
            
            $this->_renderer->render_content();
        } catch (ActionView\Template\Exception\ExceptionInterface $e) {
            switch (get_class($e)) {
                case 'Rails\ActionView\Template\Exception\LayoutMissingException':
                case 'Rails\ActionView\Template\Exception\TemplateMissingException':
                    $route = $this->router()->route();
                    if (Rails::application()->dispatcher()->action_ran()) {
                        $token = $route->to();
                        throw new Exception\ViewNotFoundException(
                            sprintf("View for %s not found", $token)
                        );
                    } else {
                        if ($route->namespaces())
                            $namespaces = ' [ namespaces => [ ' . implode(', ', $route->namespaces()) . ' ] ]';
                        else
                            $namespaces = '';
                        
                        throw new Exception\ActionNotFoundException(
                            sprintf("Action '%s' not found for controller '%s'%s", $route->action(), $route->controller(), $namespace)
                        );
                    }
                    break;
                
                default:
                    throw $e;
                    break;
             }
        }
    }
    
    public function _print_view()
    {
        if (!empty($this->_params['is_xml']))
            return $this->_xml->output();
        else
            return $this->_renderer->get_buffer_and_clean();
    }
}