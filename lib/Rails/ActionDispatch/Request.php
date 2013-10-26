<?php
namespace Rails\ActionDispatch;

class Request
{
    const LOCALHOST = '127.0.0.1';
    
    /**
     * List of methods allowed through the _method parameter
     * in a POST request.
     */
    static private $allowedHackMethods = [
        'PUT',
        'PATCH',
        'DELETE',
    ];
    
    /**
     * Request path without the query string.
     * The application's basePath (i.e. if the app is ran under a subdirectory),
     * is cut off.
     * To get the complete path, call originalPath.
     *
     * @return string
     */
    public function path()
    {
        return substr($this->originalPath(), strlen(\Rails::application()->router()->basePath()));
    }
    
    /**
     * Request path without the query string.
     * The application's basePath is included.
     *
     * @return string
     */
    public function originalPath()
    {
        if (is_int($pos = strpos($this->get('REQUEST_URI'), '?'))) {
            return substr($this->get('REQUEST_URI'), 0, $pos);
        }
        return substr($this->get('REQUEST_URI'), 0);
    }
    
    /**
     * Full request path, includes query string, but excludes basePath.
     *
     * @return string
     */
    public function fullPath()
    {
        return substr($this->get('REQUEST_URI'), strlen(\Rails::application()->router()->basePath()));
    }
    
    /**
     * Full request path, includes both basePath and query string.
     *
     * @return string
     */
    public function originalFullPath()
    {
        return $this->get('REQUEST_URI');
    }
    
    public function controller()
    {
        if (!($router = \Rails::application()->dispatcher()->router()) || !($route = $router->route()))
            return false;
        return $route->controller;
    }
    
    public function action()
    {
        if (!($router = \Rails::application()->dispatcher()->router()) || !($route = $router->route()))
            return false;
        return $route->action;
    }
    
    public function isGet()
    {
        return $this->method() === 'GET';
    }
    
    public function isPost()
    {
        return $this->method() == 'POST';
    }
    
    public function isPut()
    {
        return $this->method() == 'PUT';
    }
    
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }
    
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }
    
    /**
     * Checks the request method.
     */
    public function is($method)
    {
        $method = strtoupper($method);
        return $this->method() == $method;
    }
    
    public function isLocal()
    {
        return \Rails::config()->consider_all_requests_local ?: $this->remoteIp() == self::LOCALHOST;
    }
    
    public function remoteIp()
    {
        if ($this->get('HTTP_CLIENT_IP'))
            $remoteIp = $this->get('HTTP_CLIENT_IP');
        elseif ($this->get('HTTP_X_FORWARDED_FOR'))
            $remoteIp = $this->get('HTTP_X_FORWARDED_FOR');
        else 
            $remoteIp = $this->get('REMOTE_ADDR');
        return $remoteIp;
    }
    
    /**
     * Returns the overridden method name.
     *
     * @return string
     */
    public function method()
    {
        if (isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
            if (in_array($method, self::$allowedHackMethods)) {
                return $method;
            }
        }
        return $this->get('REQUEST_METHOD');
    }
    
    public function protocol()
    {
        $protocol = ($val = $this->get('HTTPS')) && $val !== 'off' ? 'https' : 'http';
        return $protocol . '://';
    }
    
    public function isXmlHttpRequest()
    {
        return (($var = $this->get("HTTP_X_REQUESTED_WITH"))) && $var === "XMLHttpRequest";
    }
    
    public function format()
    {
        if ($route = \Rails::application()->dispatcher()->router()->route()) {
            return $route->format;
        }
    }
    
    /**
     * Get an index in the $_SERVER superglobal.
     *
     * @return null|string
     */
    public function get($name)
    {
        $name = strtoupper($name);
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        return null;
    }
}