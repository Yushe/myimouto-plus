<?php
namespace Rails\ServiceManager;

/**
 * Beta test. Like everything else.
 */
class ServiceManager
{
    protected $serviceList = [];
    
    protected $instances   = [];
    
    public function __construct()
    {
        $this->serviceList = [
            'inflector' => [
                'class_name' => 'Rails\ActiveSupport\Inflector\Inflector'
            ],
            'i18n' => [
                'class_name' => 'Rails\I18n\I18n'
            ],
            'rails.cache' => function() {
                $cache = new \Rails\Cache\Cache('file');
            }
        ];
    }
    
    public function get($name)
    {
        if (!isset($this->instances[$name])) {
            if (isset($this->serviceList[$name])) {
                if ($this->serviceList[$name] instanceof \Closure) {
                    $this->instance[$name] = $this->serviceList[$name]();
                } else {
                    $this->instances[$name] = new $this->serviceList[$name]['class_name'];
                }
            } else {
                throw new Exception\RuntimeException(
                    sprintf("Unknown service %s", $name)
                );
            }
        }
        return $this->instances[$name];
    }
}