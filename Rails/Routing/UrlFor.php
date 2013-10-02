<?php
namespace Rails\Routing;

use Rails;

/**
 * This class should be part of Router, because if no route is matched
 * a routing exception is raised.
 */
class UrlFor
{
    /**
     * Initial url.
     */
    private $_init_url;
    
    /**
     * Final url.
     */
    private $_url;
    
    private $_params;
    
    private $_token;
    
    private $_route;
    
    private
        $_anchor,
        $_only_path = true,
        $_host,
        $_protocol = 'http';
    
    public function __construct($params, $ignore_missmatch = false)
    {
        if (is_string($params)) {
            $init_url = $params;
            $params = array();
        } elseif (is_array($params)) {
            if (isset($params['controller']) && isset($params['action']))
                $init_url = $params['controller'] . '#' . $params['action'];
            elseif (isset($params['controller']) && !isset($params['action']))
                $init_url = $params['controller'] . '#index';
            elseif (!isset($params['controller']) && isset($params['action']))
                $init_url = Rails::application()->dispatcher()->router()->route()->controller . '#' . $params['action'];
            else
                $init_url = array_shift($params);
            unset($params['controller'], $params['action']);
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf("Argument must be either string or array, %s passed", gettype($params))
            );
        }
        
        if (is_array($init_url)) {
            $params = $init_url;
            $init_url = array_shift($params);
        }
        
        $this->_init_url = $init_url;
        
        # Set params.
        if (isset($params['only_path'])) {
            $this->_only_path = $params['only_path'];
            unset($params['only_path']);
        }
        if (!empty($params['host'])) {
            $this->_host = $params['host'];
            unset($params['host']);
        }
        if (!empty($params['protocol'])) {
            $this->_host = $params['protocol'];
            unset($params['protocol']);
        }
        
        $this->_params = $params;
        
        if ($init_url === 'root') {
            $this->_url = $this->_base_url() ?: '/';
        } elseif ($init_url === 'rails_panel' && Rails::application()->config()->rails_panel_path) {
            $this->_url = $this->_base_url() .  '/' . Rails::application()->config()->rails_panel_path;
        } elseif ($init_url == 'asset') {
            $this->_url = Rails::assets()->prefix() . '/';
        } elseif ($init_url == 'base') {
            $this->_url = Rails::application()->router()->basePath();
        } elseif (is_int(strpos($init_url, '#'))) {
            $this->_parse_token($init_url);
            $this->_find_route_for_token();
            
            if ($this->_route) {
                $this->_build_route_url();
            } else {
                if (!$ignore_missmatch) {
                    # TODO: Why is this exception thrown here?
                    $modules = $this->_token->modules();
                    if ($modules) {
                        $modules = implode(', ', $modules);
                        $modules = ' [ modules=>' . $modules . ' ]';
                    } else {
                        $modules = '';
                    }
                    $token = $this->_token->controller() . '#' . $this->_token->action();
                    throw new Exception\RuntimeException(
                        sprintf('No route matches %s%s', $token, $modules)
                    );
                } else {
                    $this->_build_basic_url_for_token();
                }
            }
        } else {
            $this->_url = $init_url;
            if (strpos($this->_url, '/') === 0 && strlen($this->_url) > 1)
                $this->_url = $this->_base_url() . $this->_url;
        }
        
        $this->_build_params();
        $this->_build_url();
        
        unset($this->_init_url, $this->_params, $this->_token, $this->_route, $this->_anchor);
    }
    
    /**
     * Returns final url.
     */
    public function url()
    {
        return $this->_url;
    }
    
    private function _parse_token($token)
    {
        if (Rails::application()->router()->route()) {
            $namespaces = Rails::application()->router()->route()->namespaces();
            
            # Automatically add the namespace if we're under one and none was set.
            if ($namespaces && false === strpos($token, UrlToken::MODULE_SEPARATOR)) {
                $token = implode(UrlToken::MODULE_SEPARATOR, $namespaces) . UrlToken::MODULE_SEPARATOR . $token;
            }
        }
        $this->_token = new UrlToken($token);
    }
    
    private function _find_route_for_token()
    {
        if ($data = Rails::application()->router()->url_helpers()->find_route_for_token($this->_token->toString(), $this->_params))
            list($this->_route, $this->_url) = $data;
    }
    
    private function _build_basic_url_for_token()
    {
        $this->_url = $this->_base_url() . '/' . $this->_token->toUrl();
    }
    
    private function _build_route_url()
    {
        if (!$this->_url)
            $this->_url = $this->_route->build_url($this->_params);
        $this->_params = array_intersect_key($this->_params, $this->_route->remaining_params());
    }
    
    private function _build_params()
    {
        if (isset($this->_params['anchor'])) {
            $this->_anchor = '#' . $this->_params['anchor'];
            unset($this->_params['anchor']);
        }
        unset($this->_params['ignore_missmatch']);
        $query = http_build_query($this->_params);
        $this->_params = $query ? '?' . $query : '';
    }
    
    private function _build_url()
    {
        // if (strpos($this->_url, '/') === 0)
            // $leading = $this->_base_url();
        // else
            
        
        // $url = $leading . $this->_url . $this->_params . $this->_anchor;
        $url = $this->_url . $this->_params . $this->_anchor;
        
        $this->_url = $url;
        
        if ($this->_host || !$this->_only_path) {
            if ($this->_host)
                $host = rtrim($this->_host, '/');
            else
                $host = $_SERVER['SERVER_NAME'];
            
            $this->_url = $this->_protocol . '://' . $host . $this->_url;
        }
    }
    
    private function _base_url()
    {
        return Rails::application()->router()->basePath();
    }
}