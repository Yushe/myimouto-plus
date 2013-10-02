<?php
/**
 * Placing ZF2 (c) notice around here, see detectBasePath().
 * *******************************************
 *
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Rails\Routing;

use Rails;

class Router
{
    
    /**
     * Instance of RouteSet holding Rails_Router_Route objects.
     */
    private $_routes;
    
    private 
        $_imported_routes = false,
        $_url_helpers;
    
    /**
     * Absolute request path.
     *
     * @see _find_request_path()
     */
    private $_request_path;
    
    
    // private
        /**
         * Rails_Router_Route instance that holds the root route.
         */
        // $_root,
        /**
         * Rails_Router_Route instance that holds the panel route.
         */
        // $_admin,
        // $_assets;
    
    /**
     * Rails_Router_Route instance that matched the request.
     */
    private $_route;
    
    private $base_path = false;
    
    public function url_helpers()
    {
        if (!$this->_url_helpers)
            $this->_url_helpers = new UrlHelpers\UrlHelpers();
        return $this->_url_helpers;
    }
    
    public function rootRoute()
    {
        return $this->routes()->rootRoute();
    }
    
    public function panelRoute()
    {
        return $this->routes()->panelRoute();
    }
    
    public function assetsRoute()
    {
        return $this->routes()->assetsRoute();
    }
    
    public function find_route()
    {
        $this->matchRoutes();
    }
    
    public function route()
    {
        return $this->_route;
    }
    
    public function routes()
    {
        $this->importRoutes();
        return $this->_routes;
    }
    
    /**
     * Base path can be get in views by calling urlFor('base') or basePath().
     */
    public function basePath()
    {
        if ($this->base_path === false) {
            if ($base_path = Rails::application()->config()->base_path)
                $this->base_path = '/' . $base_path;
            elseif (($base_path = $this->detectBasePath()) && $base_path != '/')
                $this->base_path = $base_path;
            else
                $this->base_path = null;
        }
        return $this->base_path;
    }
    
    public function rootPath()
    {
        ############ WARNING
        ############ MUST UPDATE ALL SYSTEMS TO THIS CHANGE (added trailing slash when returning basePath)
        if ($base_path = $this->basePath())
            return $base_path . '/';
        return '/';
    }
    
    public function urlFor($params, $ignore_missmatch = false)
    {
        $urlfor = new UrlFor($params, $ignore_missmatch);
        return $urlfor->url();
    }
    
    private function importRoutes()
    {
        if ($this->_imported_routes)
            return;
        
        $this->_imported_routes = true;
        $this->_routes = new Route\RouteSet();
        
        $config = Rails::config();
        $routes_file = $config->paths->config->concat('routes.php');
        
        require $routes_file;
    }
    
    private function matchRoutes()
    {
        $this->importRoutes();
        
        $request_path = $this->_find_request_path();
        
        $request_method = Rails::application()->dispatcher()->request()->method();
        
        if ($this->rootRoute()->match($request_path, $request_method)) {
            $this->_route = $this->rootRoute();
        } elseif ($this->panelRoute() && $this->panelRoute()->match($request_path, $request_method)) {
            $this->_route = $this->panelRoute();
        } elseif ($this->assetsRoute() && $this->assetsRoute()->match($request_path, $request_method)) {
            $this->_route = $this->assetsRoute();
        } else {
            foreach ($this->routes() as $route) {
                if ($route->match($request_path, $request_method)) {
                    if (!is_file($route->controller_file())) {
                        throw new Exception\RoutingErrorException(
                            sprintf('Controller file for "%sController" not found %s',
                                Rails::services()->get('inflector')->camelize($route->controller),
                                $route->modules ? ' [ module=>' . $route->namespace_path() . ' ]' : ''
                            )
                        );
                    } else {
                        $this->_route = $route;
                        break;
                    }
                }
            }
        }
        if (!$this->_route) {
            throw new Exception\NotFoundException(
                sprintf('No route matches [%s] "%s"',
                     Rails::application()->dispatcher()->request()->method(),
                     Rails::application()->dispatcher()->request()->path()
                )
            );
        }
    }
    
    private function _find_request_path()
    {
        if (!$this->_request_path) {
            preg_match('/([^?]+)/', $_SERVER['REQUEST_URI'], $m);
            $this->_request_path = $m[1];
        }
        return $this->_request_path;
    }
    
    /**
     * This method was copied from ZF2, slightly modified to fit Rails.
     * Zend\Http\PhpEnvironment\Request::detectBaseUrl()
     */
    protected function detectBasePath()
    {
        $request = Rails::application()->dispatcher()->request();
        
        $baseUrl        = '';
        $filename       = $request->get('SCRIPT_FILENAME');
        $scriptName     = $request->get('SCRIPT_NAME');
        $phpSelf        = $request->get('PHP_SELF');
        $origScriptName = $request->get('ORIG_SCRIPT_NAME');

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            $baseUrl = $origScriptName;
        } else {
            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        $requestUri = $request->get('REQUEST_URI');

        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }
        
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }
}