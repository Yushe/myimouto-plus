<?php
namespace Rails\ActionDispatch;

use Rails;
use Rails\ActionController\Response;
use Rails\Routing;

class ActionDispatch
{
    /**
     * ActionController_Response instance.
     */
    private $_response;
    
    private
        $_parameters,
        $_request,
        $_session,
        $_headers;
    
    private $_router;
    
    private $_action_ran = false;
    
    private $_action_dispatched = false;
    
    private $_view;
    
    private $_responded = false;
    
    public function init()
    {
        $this->_response = new Response();
        $this->_router = new Routing\Router();
        $this->_response->_init();
        $this->_headers = new Http\Headers();
        $this->load_request_and_params();
    }
    
    public function load_request_and_params()
    {
        if (!$this->_parameters) {
            $this->_request = new Request();
            $this->_parameters = new Http\Parameters();
            $this->_session = new Http\Session();
        } else {
            throw new Exception\LogicException("Can't call init() more than once");
        }
    }
    
    public function find_route()
    {
        $this->_router->find_route();
        $this->_route_vars_to_params();
    }
    
    public function router()
    {
        return $this->_router;
    }
    
    /**
     * This method shouldn't be accessed like Rails::application()->dispatcher()->parameters();
     */
    public function parameters()
    {
        return $this->_parameters;
    }
    
    public function request()
    {
        return $this->_request;
    }
    
    public function headers()
    {
        return $this->_headers;
    }
    
    public function controller()
    {
        return Rails::application()->controller();
    }
    
    public function session()
    {
        return $this->_session;
    }
    
    public function response()
    {
        return $this->_response;
    }
    
    public function respond()
    {
        // if ($this->_responded && !Rails::response_params()) {
        if ($this->_responded) {
            throw new Exception\LogicException("Can't respond to request more than once");
        } else {
            $this->_response->_respond();
            $this->_responded = true;
        }
    }
    
    // public function clear_responded()
    // {
        // static $cleared = false;
        
        // if ($cleared) {
            // throw new Rails_ActionDispatch_Exception("Can't clear response more than once");
        // } else {
            // $cleared = true;
            // $this->_responded = false;
        // }
    // }
    
    private function _route_vars_to_params()
    {
        $vars = $this->_router->route()->vars();
        unset($vars['controller'], $vars['action']);
        foreach ($vars as $name => $val) {
            if ($this->_parameters->$name === null)
                $this->_parameters->$name = $val;
        }
    }
    
    private function _action_name()
    {
        return $this->router()->route()->action;
    }
    
    private function _action_exists()
    {
        $controller = Rails::application()->controller();
        return method_exists($controller, $this->_action_name()) && is_callable(array($controller, $this->_action_name()));
    }
    
    private function _app()
    {
        return Rails::application();
    }
}