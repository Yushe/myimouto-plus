<?php
namespace Rails\ActionController\Response;

use Rails;
use Rails\ActionView;

class Template extends Base
{
    private $_xml;
    
    protected $renderer;
    
    protected $template_file_name;
    
    public function _render_view()
    {
        try {
            ActionView\ViewHelpers::load();
            
            $this->build_template_file_name();
            
            $params = [
                'layout' => $this->_params['layout']
            ];
            
            $this->renderer = new ActionView\Template($this->template_file_name, $params);
            
            $locals = Rails::application()->controller()->vars();
            
            if (!empty($this->_params['is_xml'])) {
                $this->_xml = new ActionView\Xml();
                $locals->xml = $this->_xml;
            }
            
            $this->renderer->setLocals($locals);
            
            $this->renderer->renderContent();
        } catch (ActionView\Template\Exception\ExceptionInterface $e) {
            switch (get_class($e)) {
                case 'Rails\ActionView\Template\Exception\TemplateMissingException':
                    $route = Rails::application()->router()->route();
                    if (Rails::application()->dispatcher()->controller()->actionRan()) {
                        throw new Exception\ViewNotFoundException(
                            sprintf("View file not found: %s", $this->template_file_name)
                        );
                    } else {
                        if ($route->namespaces())
                            $namespaces = ' [ namespaces => [ ' . implode(', ', $route->namespaces()) . ' ] ]';
                        else
                            $namespaces = '';
                        
                        throw new Exception\ActionNotFoundException(
                            // sprintf("Action '%s' not found for controller '%s'%s", $route->action(), $route->controller(), $namespaces)
                            sprintf("Action '%s' not found for controller '%s'%s", $route->action(), $route->controller(), $namespaces)
                        );
                    }
                    break;
                
                // default:
                    // break;
             }
            throw $e;
        }
    }
    
    public function _print_view()
    {
        // if (!empty($this->_params['is_xml']))
            // return $this->_xml->output();
        // else
            return $this->renderer->get_buffer_and_clean();
    }
    
    private function build_template_file_name()
    {
        if (is_array($this->_params['extension']))
            $ext = implode('.', $this->_params['extension']);
        else
            $ext = $this->_params['extension'];
        
        $views_path = Rails::config()->paths->views;
        $this->template_file_name = $views_path . DIRECTORY_SEPARATOR . $this->_params['template_name'] . '.' . $ext;
    }
}