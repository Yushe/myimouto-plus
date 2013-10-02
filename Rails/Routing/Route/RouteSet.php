<?php
namespace Rails\Routing\Route;

use ArrayObject;
use IteratorAggregate;
use Rails;
use Rails\Routing\Mapper;

class RouteSet implements IteratorAggregate
{
    /**
     * Holds routes helper names ("as") to avoid dupes.
     */
    static private $_routes_aliases = [];
    
    private $routes;
    
    private $rootRoute;
    
    private $panel_route;
    
    private $assets_route;
    
    private $routes_drawn = false;
    
    static public function validate_route_alias($alias)
    {
        return !in_array($alias, self::$_routes_aliases);
    }
    
    static public function add_route_alias($alias)
    {
        self::$_routes_aliases[] = $alias;
    }
    
    public function __construct()
    {
        $this->routes = new ArrayObject();
    }
    
    public function getIterator()
    {
        return $this->routes;
    }
    
    public function draw(\Closure $block)
    {
        if (!$this->routes_drawn) {
            $mapper = new Mapper($this);
            $mapper->drawRoutes($block);
            $this->routes_drawn = true;
        }
    }
    
    public function add(Route $route)
    {
        $this->routes[] = $route;
    }
    
    public function set_root_route(Route $route)
    {
        $this->rootRoute = $route;
    }
    
    public function set_panel_route(Route $route)
    {
        $this->panel_route = $route;
    }
    
    public function set_assets_route(Route $route)
    {
        $this->assets_route = $route;
    }
    
    public function rootRoute()
    {
        return $this->rootRoute;
    }
    
    public function panelRoute()
    {
        return $this->panel_route;
    }
    
    public function assetsRoute()
    {
        return $this->assets_route;
    }
    
    public function routes()
    {
        return $this->routes;
    }
}