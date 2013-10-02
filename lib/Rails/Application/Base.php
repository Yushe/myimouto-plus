<?php
namespace Rails\Application;

use Closure;
use Zend;
use Rails;
use Rails\Config\Config;
use Rails\Exception\PHPError\Base as PHPErrorBase;
use Rails\ActionDispatch\ActionDispatch;
use Rails\ActionView\ActionView;
use Rails\ActiveRecord\ActiveRecord;
use Rails\I18n\I18n;
use Rails\Toolbox;

class Base
{
    static private $_instance;
    
    private $_router;
    
    private $_dispatcher;
    
    /**
     * Rails_I18n instance.
     */
    private $_I18n;
    
    private $config;
    
    /**
     * Actual controller instance.
     */
    private $_controller;
    
    private $name;
    
    static public function dispatchRequest()
    {
        self::$_instance->_run();
    }
    
    # This is the initializer.
    static public function initialize()
    {
        if (!self::$_instance) {
            $cn = get_called_class();
            self::$_instance = new $cn();
            self::$_instance->name = substr($cn, 0, strpos($cn, '\\'));
        }
        self::$_instance->boot();
        
        if (Rails::cli())
            Rails::console()->run();
    }
    
    static public function instance()
    {
        return self::$_instance;
    }

    static public function configure(Closure $config)
    {
        $config(self::$_instance->config);
    }
    
    static public function routes()
    {
        return self::$_instance->_dispatcher->router()->routes();
    }
    
    # Used only by Rails
    static public function setPanelConfig()
    {
        $basePath = Rails::path() . '/Panel';
        $paths = Rails::config()->paths;
        
        $paths->application->setPath($basePath)->setBasePaths([]);
        $trait = $basePath . '/traits/AdminController.php';
        require_once $trait;
        
        $trait = $basePath . '/traits/ApplicationController.php';
        require_once $trait;
        
        Rails::loader()->addPaths([
            $paths->helpers->toString(),
            $paths->controllers->toString()
        ]);
        
        Rails::config()->action_view->layout = 'application';
    }
    
    public function __construct()
    {
    }
    
    public function boot()
    {
        $this->resetConfig(Rails::env());
        $this->setPhpConfig();
        $this->_load_files();
        $this->_load_active_record();
        $this->initPlugins();
        $this->setDispatcher();
        $this->init();
        $this->runInitializers();
    }
    
    public function resetConfig($environment)
    {
        $this->setDefaultConfig();
        $this->initConfig($this->config);
        $this->setEnvironmentConfig($environment);
    }
    
    public function config()
    {
        return $this->config;
    }
    
    public function dispatcher()
    {
        return $this->_dispatcher;
    }
    
    public function controller()
    {
        return $this->_controller;
    }
    
    /**
     * Created so it can be called by Rails when creating the ExceptionHandler
     */
    public function set_controller(\Rails\ActionController\Base $controller)
    {
        $this->_controller = $controller;
    }
    
    public function I18n()
    {
        return Rails::services()->get('i18n');
        // if (!$this->_I18n) {
            // $this->_I18n = new I18n();
        // }
        // return $this->_I18n;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function validateSafeIps()
    {
        return $this->config()->safe_ips->includes($this->dispatcher()->request()->remoteIp());
    }
    
    public function router()
    {
        return $this->_dispatcher->router();
    }
    
    /**
     * For custom init.
     */
    protected function init()
    {
    }

    protected function _run()
    {
        ActionView::clean_buffers();
        ob_start();
        
        $this->_dispatcher->find_route();
        if ($this->dispatcher()->router()->route()->assets_route()) {
            Rails::assets()->server()->dispatch_request();
        } else {
            if ($this->dispatcher()->router()->route()->rails_admin())
                $this->setPanelConfig();
            $this->_load_controller();
            $this->controller()->run_request_action();
        }
        
        $this->_dispatcher->respond();
    }
    
    public function setDispatcher()
    {
        $this->_dispatcher = new ActionDispatch();
        $this->_dispatcher->init();
    }

    /**
     * Used in /config/application.php to initialize custom configuration.
     *
     * @see _default_config()
     * @return array.
     */
    protected function initConfig($config)
    {
    }
    
    private function initPlugins()
    {
        if ($this->config()->plugins) {
            foreach ($this->config()->plugins->toArray() as $pluginName) {
                $initClassName = $pluginName . '\Initializer';
                $initializer = new $initClassName();
                $initializer->initialize();
            }
        }
    }
    
    private function _load_controller()
    {
        $route = $this->dispatcher()->router()->route();
        $controller_name = $route->controller();
        
        if ($route->namespaces()) {
            $namespaces = [];
            foreach ($route->namespaces() as $ns)
                $namespaces[] = Rails::services()->get('inflector')->camelize($ns);
            $class_name = implode('\\', $namespaces) . '\\' . Rails::services()->get('inflector')->camelize($controller_name) . 'Controller';
        } else {
            $class_name = Rails::services()->get('inflector')->camelize($controller_name) . 'Controller';
        }
        
        $controller = new $class_name();
        
        if (!$controller instanceof \Rails\ActionController\Base) {
            throw new Exception\RuntimeException(
                sprintf('Controller %s must be extension of Rails\ActionController\Base', $class_name)
            );
        }
        $this->_controller = $controller;
    }
    
    /**
     * Load default files.
     */
    private function _load_files()
    {
        foreach ($this->config()->load_files as $file) {
            require $file;
        }
    }
    
    private function _load_active_record()
    {
        $basename = Rails::root() . '/config/database';
        $database_file = $basename . '.yml';
        $connections = [];
        
        /**
         * It's possible to not to have a database file if no database will be used.
         */
        if (is_file($database_file)) {
            $connections = Rails\Yaml\Parser::readFile($database_file);
        } else {
            $database_file = Rails::root() . '/config/database.php';
            if (is_file($database_file))
                $connections = require $database_file;
        }
        
        if ($connections) {
            foreach ($connections as $name => $config)
                ActiveRecord::addConnection($config, $name);
            # Set environment connection.
            ActiveRecord::set_environment_connection(Rails::env());
        }
    }
    
    private function setDefaultConfig()
    {
        $config = Rails::config();
        $config->session = [
            'name'  => '_' . Rails::services()->get('inflector')->underscore($this->name) . '_session_id',
            'start' => true
        ];
        $this->config = $config;
    }
    
    private function setEnvironmentConfig($environment)
    {
        $config = Rails::config();
        
        $file = $config->paths->config->concat('environments') . '/' . $environment . '.php';
        if (!is_file($file)) {
            throw new Exception\RuntimeException(
                sprintf("Environment config file not found [ environment => %s, file => %s ]",
                    $environment, $file)
            );
        }
        include $file;
    }
    
    /**
     * Sets some php-related config. This happens once (i.e. if the
     * config is reset, this method isn't called again).
     */
    private function setPhpConfig()
    {
        if (function_exists('ini_set')) {
            if ($this->config->error->log) {
                ini_set('log_errors', true);
                ini_set('error_log', Rails::config()->log_file);
            }
        }
        
        if ($this->config['date']['timezone']) {
            date_default_timezone_set($this->config['date']['timezone']);
        } elseif (function_exists('ini_get')) {
            if (!ini_get('date.timezone') && !date_default_timezone_get()) {
                throw new Exception\RuntimeException(
                    "date.timezone directive doesn't seem to have a value. " .
                    "You are requested to either set a value in your php.ini or in your application's configuration (date->timezone)"
                );
            }
        }
        
        if ($this->config()->session->start && !Rails::cli()) {
            if ($session_name = $this->config()->session->name) {
                session_name($session_name);
            }
            session_start();
        }
        
        if ($paths = $this->config()->eager_load_paths->toArray()) {
            Rails::loader()->addPaths($paths);
            set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, $paths));
        }
        
        set_error_handler('Rails::errorHandler', $this->config()->error->report_types);
    }
    
    private function runInitializers()
    {
        $dirName = 'initializers';
        $path = $this->config()->paths->config->concat($dirName);
        if (is_dir($path)) {
            $files = [];
            
            $patt = $path . '/*.php';
            $files = array_merge($files, glob($patt));
            
            foreach (Toolbox\FileTools::listDirs($path) as $dir) {
                $patt = $dir . '/*.php';
                $files = array_merge($files, glob($patt));
            }
            
            foreach ($files as $file) {
                require $file;
            }
        }
    }
}