<?php
namespace Rails\Routing;

use Closure;
use Rails;
use Rails\Toolbox;

class Mapper
{
    const ROOT_URL             = '/';
    
    const ROOT_DEFAULT_TO      = 'welcome#index';
    
    const ADMIN_DEFAULT_TO     = 'admin#';
	
    const ADMIN_STYLESHEET_TO  = 'admin#stylesheet';
    
    private $concernList = [];
    
    private $paths = [];
    
    private $scopeParams = [];
    
    private $routeNames = [];
    
    private $modules = [];
    
    private $createAsMember = false;
    
    private $resources = [];
    
    private $resource = [];
    
    private $resourceNesting = [];
    
    private $routeSet;

    public function __construct(Route\RouteSet $routeSet)
    {
        $this->routeSet = $routeSet;
    }
    
    public function __call($method, $params)
    {
        if ($method == 'namespace') {
            call_user_func_array([$this, 'namespaced'], $params);
        }
    }
    
    /**
     * Must specify a verb through the via parameter.
     */
    public function match($url, $to = null, array $params = array())
    {
        if (!$params && is_array($to)) {
            $params = $to;
            $to = null;
        }
        $this->createAndAddRoute($url, $to, $params);
    }
    
    public function get($url, $to = null, array $params = array())
    {
        $this->createRouteWithVerb('get', $url, $to, $params);
    }
    
    public function post($url, $to = null, array $params = array())
    {
        $this->createRouteWithVerb('post', $url, $to, $params);
    }
    
    public function put($url, $to = null, array $params = array())
    {
        $this->createRouteWithVerb('put', $url, $to, $params);
    }
    
    public function patch($url, $to = null, array $params = array())
    {
        $this->createRouteWithVerb('patch', $url, $to, $params);
    }
    
    public function delete($url, $to = null, array $params = array())
    {
        $this->createRouteWithVerb('delete', $url, $to, $params);
    }
    
    public function root($to)
    {
        if (!$this->paths) {
            $this->routeSet->set_root_route(
                $this->createRoute(
                    self::ROOT_URL,
                    $to,
                    [
                        'via' => ['get'],
                        'as'  => 'root'
                    ]
                )
            );
        } else {
            $this->createAndAddRoute('', $to, ['via' => ['get'], 'as' => implode('_', $this->paths) . '_root']);
        }
    }
    
    public function scope($params, Closure $routesBlock)
    {
        if (is_string($params)) {
            $params = [ 'path' => $params ];
        } elseif (!is_array($params)) {
            $params = [ $params ];
        }
        
        if (isset($params['path'])) {
            $this->paths[] = $params['path'];
            unset($params['path']);
        }
        
        $this->scopeParams[] = $params;
        $routesBlock();
        array_pop($this->scopeParams);
        array_pop($this->paths);
    }
    
    public function member(Closure $block)
    {
        $this->scopeParams[] = ['on' => 'member'];
        $block();
        array_pop($this->scopeParams);
    }
    
    /**
     * Namespace is a mix of module + path options.
     */
    public function namespaced($namespace, Closure $block)
    {
        $this->paths[]      = $namespace;
        $this->modules[]    = $namespace;
        $this->routeNames[] = $namespace;
        $block();
        array_pop($this->paths);
        array_pop($this->modules);
        array_pop($this->routeNames);
    }
    
    /**
     * @param string $name   String with format /^\w+$/
     */
    public function resources($name, $params = null, Closure $block = null)
    {
        $this->resources[]       = $name;
        $this->resourceNesting[] = 'resources';
        $this->createRoutesWithResource(true, $params, $block);
        array_pop($this->resources);
        array_pop($this->resourceNesting);
    }
    
    /**
     * @param string $name   String with format /^\w+$/
     */
    public function resource($name, $params = null, Closure $block = null)
    {
        $this->resource[]        = $name;
        $this->resourceNesting[] = 'resource';
        $this->createRoutesWithResource(false, $params, $block);
        array_pop($this->resource);
        array_pop($this->resourceNesting);
    }
    
    public function concern($name, Closure $block)
    {
        $this->concernList[$name] = $block;
    }
    
    private function createRoutesWithResource($multiple, $params, Closure $block = null)
    {
        if ($params instanceof Closure) {
            $block = $params;
            $params = [];
        } elseif (!is_array($params)) {
            $params = [];
        }
        
        if ($block) {
            $block();
        }
        
        if (isset($params['concerns'])) {
            $params['concerns'] = (array)$params['concerns'];
            foreach ($params['concerns'] as $name) {
                if (!isset($this->concernList[$name])) {
                    throw new Exception\RuntimeException(
                        sprintf("No concern named %s was found")
                    );
                }
                $this->concernList[$name]();
            }
            unset($params['concerns']);
        }
        
        if (!isset($params['defaults']) || $params['defaults']) {
            $actions = [ 'index', 'create', 'new', 'edit', 'show', 'update', 'destroy' ];
            
            if (!$multiple) {
                array_shift($actions);
            }
            
            $only    = !empty($params['only'])   ? $params['only']   : [];
            $except  = !empty($params['except']) ? $params['except'] : [];
            
            unset($params['only'], $params['except']);
            
            foreach ($actions as $action) {
                if ($only) {
                    $create = in_array($action, $only);
                } elseif ($except) {
                    $create = !in_array($action, $except);
                } else {
                    $create = true;
                }
                
                if ($create) {
                    $this->addRoute($this->createResourceRoute($action, $params, $multiple));
                }
            }
        }
    }
    
    private function createResourceRoute($action, $params, $multiple = true, $to = null)
    {
        $path = '';
        $pre_alias = [];
        $resources = $this->resources;
        $count = count($resources);
        $inflector = Rails::services()->get('inflector');
        
        $uncountable = ($action == 'index' || $action == 'create' || $action == 'new');
        $single = $count == 1;
        
        foreach ($resources as $k => $res) {
            $path .= '/' . $res;
            $last = $k + 1 == $count;
            
            if ($multiple) {
                if ($uncountable && $last) {
                    continue;
                } elseif ($last || $single) {
                    $path .= '/:id';
                } else {
                    $path .= '/:' . $inflector->singularize($res) . '_id';
                }
            } else {
                $singularRes = $inflector->singularize($res);
                $pre_alias[] = $singularRes;
                $path .= '/:' . $singularRes . '_id';
            }
            
        }
        
        if (!$multiple) {
            $resources = $this->resource;
            foreach ($resources as $res) {
                $path .= '/' . $res;
            }
        }
        
        $path = ltrim($path, '/');
        
        $setAlias = true;
        switch ($action) {
            case 'index':
                $method = 'get';
                break;
                
            case 'new':
                $method = 'get';
                $path .= '/new';
                $action = 'blank';
                array_unshift($pre_alias, 'new');
                break;
                
            case 'create':
                $method = 'post';
                $setAlias = !$multiple;
                break;
                
            case 'show':
                $method = 'get';
                break;
                
            case 'edit':
                $method = 'get';
                $path .= '/edit';
                array_unshift($pre_alias, 'edit');
                break;
            
            case 'update':
                $method = ['put', 'patch'];
                $setAlias = false;
                break;
            
            case 'destroy':
                $setAlias = false;
                $method = 'delete';
                break;
        }
        
        $controller = end($resources);
        $plural = substr($controller, -1, 1) == 's';
        
        if ($setAlias) {
            if ($multiple) {
                $aliasedResources = [];
                foreach ($resources as $resource) {
                    $aliasedResources[] = $inflector->singularize($resource);
                }
            } else {
                $aliasedResources = $resources;
            }
            
            if ($action == 'index') {
                $lastIndex = count($resources) - 1;
                $aliasedResources[$lastIndex] = $resources[$lastIndex];
            }
            
            $pre_alias = implode('_', $pre_alias);
            $pre_alias .= ($pre_alias && $this->routeNames ? '_' : '') . implode($this->routeNames);
            $alias = ($pre_alias ? $pre_alias . '_' : '') . implode('_', $aliasedResources);
            
            if ($action == 'index') {
                if (!$plural) {
                    $alias .= '_index';
                }
            }
            
            // $alias = $inflector->camelize($alias, false);
            
            if (Route\RouteSet::validate_route_alias($alias)) {
                Route\RouteSet::add_route_alias($alias);
                $params['as'] = $alias;
            }
        } else {
            $params['as'] = '';
        }
        
        if ($method) {
            $params['via'] = $method;
        }
        
        if (!$to) {
            if (!$multiple) {
                $controller = $inflector->pluralize($controller);
            }
            $to = $controller . '#' . $action;
        }
        
        unset($params['on']);
        
        return $this->createRoute($path, $to, $params, ['skip_resources' => true]);
    }
    
    private function createResourceNestedRoute($action, $params, $multiple = true, $to = null)
    {
        $path = '';
        $setAlias = true;
        $resources = $this->resources;
        
        if (isset($params['as'])) {
            $pre_alias = '';
            // $pre_alias = $params['as'];
            $setAlias = false;
        } elseif (preg_match('/\A\w+\Z/', $action)) {
            $pre_alias = $action;
        } else {
            $pre_alias = '';
            $setAlias = false;
        }
        
        foreach ($resources as $k => $res) {
            $path .= '/' . $res;
            
            if ($this->createAsMember || (isset($params['on']) && $params['on'] == 'member'))
                $path .= '/:id';
            else
                $path .= '/:' . $res . '_id';
            
            $pre_alias .= '_' . $res;
        }
        
        if (!$multiple) {
            $resources = $this->resource;
            foreach ($resources as $res) {
                $path .= '/' . $res;
            }
        }
    
        $method = !empty($params['via']) ? $params['via'] : null;
        
        
        if ($action != 'index') {
            $path .= '/' . $action;
        }
        
        $path = ltrim($path, '/');
        
        $controller = end($resources);
        $plural = substr($controller, -1, 1) == 's';
        $inflector = Rails::services()->get('inflector');
        
        if ($setAlias) {
            $aliasedResources = [];
            foreach ($resources as $resource) {
                $aliasedResources[] = $inflector->singularize($resource);
            }
            
            if ($action == 'index') {
                $lastIndex = count($resources) - 1;
                $aliasedResources[$lastIndex] = $resources[$lastIndex];
            }
            
            $pre_alias .= ($pre_alias && $this->routeNames ? '_' : '') . implode($this->routeNames);
            $alias = ($pre_alias ? $pre_alias . '_' : '') . implode('_', $aliasedResources);
            
            if ($action == 'index') {
                if (!$plural)
                    $alias .= '_index';
            }
            // elseif ($plural)
                // $alias = substr($alias, 0, -1);
                
            if (Route\RouteSet::validate_route_alias($alias)) {
                Route\RouteSet::add_route_alias($alias);
                $params['as'] = $alias;
            }
        } elseif (empty($params['as'])) {
            $params['as'] = '';
        }
        
        if ($method) {
            $params['via'] = $method;
        }
        
        if (!$to) {
            if (!$multiple) {
                $controller = $inflector->pluralize($controller);
            }
            $to = $controller . '#' . $action;
        }
        
        return $this->createRoute($path, $to, $params, ['skip_resources' => true]);
    }
    
    /**
     * Used by Rails.
     */
    public function drawRoutes(Closure $block)
    {
        $block = $block->bindTo($this);
        $block();
        
        if (!$this->routeSet->rootRoute()) {
            $this->root(self::ROOT_DEFAULT_TO);
        }
        
        $this->createPanelRoute();
        $this->createAssetsRoute();
    }
    
    private function createRoute($url, $to, array $params = [], array $createOpts = [])
    {
        if (!array_key_exists('as', $params)) {
            if (preg_match('/^[\w\/]+$/', $url)) {
                $params['as'] = str_replace('/', '_', $url);
            }
        }
        
        $this->addNestedParams($url, $params);
        
        if (empty($createOpts['skip_resources']) && $this->resourceNesting) {
            $current = end($this->resourceNesting);
            $route = $this->createResourceNestedRoute($url, $params, $current == 'resources', $to);
        } else {
            unset($params['on']);
            $route = new Route\Route($url, $to, $params);
        }
        return $route;
    }
    
    private function addNestedParams(&$url, array &$params)
    {
        if ($this->scopeParams) {
            $scopeParams = [];
            foreach ($this->scopeParams as $k => $param) {
                $scopeParams = array_merge($scopeParams, $param);
            }
            $params = array_merge($scopeParams, $params);
        }
        
        if ($this->modules) {
            if (!isset($params['module'])) {
                $params['module'] = [];
            } else {
                $params['module'] = explode('/', $params['module']);
            }
            $params['modules'] = array_filter(array_merge($this->modules, $params['module']));
            unset($params['module']);
        }
        
        if ($this->paths) {
            if (!isset($params['path'])) {
                $params['path'] = [];
            } else {
                $params['path'] = explode('/', $params['path']);
            }
            $params['paths'] = array_filter(array_merge(Toolbox\ArrayTools::flatten($this->paths), $params['path']));
            unset($params['path']);
        }
    }
    
    private function addRoute($route)
    {
        $route->build();
        $this->routeSet->add($route);
    }
    
    private function createAndAddRoute($url, $to, array $params)
    {
        $this->addRoute($this->createRoute($url, $to, $params));
    }
    
    private function createRouteWithVerb($via, $url, $to = null, array $params)
    {
        if (is_array($to)) {
            $params = $to;
            $to = null;
        }
        $params['via'] = $via;
        
        $this->createAndAddRoute($url, $to, $params);
    }
    
    private function createPanelRoute()
    {
        if ($panelPath = Rails::application()->config()->rails_panel_path) {
            $route = new Route\PanelRoute(
                $panelPath . '(/:action)',
                self::ADMIN_DEFAULT_TO,
                [
                    'rails_panel' => true,
                    'defaults'    => ['controller' => 'admin'],
                    'via'         => ['get', 'post']
                ]
            );
            $route->build();
            $this->routeSet->set_panel_route($route);
            
            $this->routeSet->add(new Route\HiddenRoute($panelPath . '/stylesheet.css', 
                self::ADMIN_STYLESHEET_TO,
                [
                    'via'    => ['get'],
                    'format' => false
                ]
            ));
        }
    }
    
    private function createAssetsRoute()
    {
        if (Rails::application()->config()->assets->enabled && !Rails::application()->config()->serve_static_assets) {
            $prefix = ltrim(Rails::application()->config()->assets->prefix, '/');
            $this->routeSet->set_assets_route(
                $this->createRoute(
                    $prefix . '/*file',
                    '#',
                    [
                        'assets_route' => true,
                        'via' => ['get'],
                        'format' => false
                    ]
                )
            );
        }
    }
}
