<?php
namespace Rails\Routing\Traits;

/**
 * These methods are expected to be used in __call() magic methods,
 * to dynamically search for named paths (for routes with alias).
 */
trait NamedPathAwareTrait
{
    protected function isNamedPathMethod($method)
    {
        return strpos($method, 'Path') === strlen($method) - 4;
    }
    
    protected function getNamedPath($method, array $params = [])
    {
        $alias = \Rails::services()->get('inflector')->underscore(substr($method, 0, -4));
        
        return \Rails::application()
                ->router()
                ->url_helpers()
                ->find_route_with_alias($alias, $params);
    }
}