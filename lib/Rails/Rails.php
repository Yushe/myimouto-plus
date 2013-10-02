<?php
/**
 * PHP on Rails
 *
 * This is a Ruby on Rails-like framework.
 * http://code.google.com/p/php-on-rails/
 */
use Rails\Config\Config;
 
final class Rails
{
    static private $config;
    
    static private $assets;
    
    static private $cache;
    
    static private $customExceptionHandlerRan;
    
    static private $systemExit;
    
    static private $loader;
    
    static private $console;
    
    /**
     * Path to the framework folder.
     */
    static private $path;
    
    static private $env;
    
    /**
     * Application's root folder.
     */
    static private $root;
    
    /**
     * Application's public folder.
     */
    static private $publicPath;
    
    /**
     * Path to Zend library.
     * Defaults to Rails::root() . '/vendor/ZF2/library/Zend'
     *
     * @var string
     */
    // static private $zf2Path;
    
    /**
     * Path to Symfony library.
     * Defaults to Rails::root() . '/vendor/Symfony'
     *
     * @var string
     */
    // static private $sfPath;
    
    static private $serviceManager;
    
    static private $cli = false;
    
    /**
     * Boots system.
     */
    static public function initialize()
    {
        self::$cli = PHP_SAPI === 'cli';
        
        # Set Rails path
        self::$path = str_replace(DIRECTORY_SEPARATOR, '/', __DIR__);
        // self::$path = str_replace(DIRECTORY_SEPARATOR, '/', RAILS_PATH);
        
        # Set root path
        self::$root = str_replace(DIRECTORY_SEPARATOR, '/', RAILS_ROOT);
        
        # Set public path
        self::$publicPath = defined('RAILS_PUBLIC_PATH') ? RAILS_PUBLIC_PATH : self::$root . '/public';
        self::$publicPath = str_replace(DIRECTORY_SEPARATOR, '/', self::$publicPath);
        
        # Set ZF2 path
        if (defined("ZF2_PATH")) {
            self::$zf2Path = ZF2_PATH;
        }
        
        # Set Symfony path
        if (defined("SYMFONY_PATH")) {
            self::$sfPath = SYMFONY_PATH;
        }
        
        /**
         * Set environment.
         * Note that it will be development when running from cli.
         */
        self::$env = self::$cli ? 'development' : RAILS_ENV;
        
        self::$config = self::defaultConfig();
        
        # Debugging functions...
        include self::$path . '/Toolbox/functions.php';
        
        self::setRailsConfig();
    }
    
    static public function application()
    {
        return Rails\Application\Base::instance();
    }
    
    static public function config()
    {
        return self::$config;
    }
    
    static public function resetConfig($environment)
    {
        self::$config = self::defaultConfig();
        self::application()->resetConfig($environment);
    }
    
    static public function loader()
    {
        return self::$loader;
    }
    
    static public function assets()
    {
        if (!self::$assets) {
            self::$assets = Rails\Assets\Assets::instance();
            
            $prefix = str_replace('\\', '/', self::$config->assets->prefix);
            
            $router = self::application()->router();
            if (!$router || !$router->route() || !$router->route()->isPanelRoute()) {
                $basePaths = [
                    str_replace('\\', '/', Rails::config()->paths->application) . $prefix,
                    str_replace('\\', '/', Rails::root() . '/lib') . $prefix,
                    str_replace('\\', '/', Rails::root() . '/vendor') . $prefix,
                ];
            }
            
            $paths = [];
            
            foreach ($basePaths as $basePath) {
                if (!is_dir($basePath)) {
                    continue;
                }
                
                $dir = new DirectoryIterator($basePath);
                
                foreach ($dir as $file) {
                    if ($file->isDot() || !$file->isDir()) {
                        continue;
                    }
                    
                    $paths[] = $file->getPathname();
                }
            }
            
            $customPaths = Rails::application()->config()->assets->paths->toArray();
            
            $paths = array_merge($paths, $customPaths);
            
            self::$assets->addPaths($paths);
            
            self::$assets->addFilePatterns(self::config()->assets->patterns->toArray());
        }
        return self::$assets;
    }
    
    static public function cache()
    {
        if (!self::$cache)
            self::$cache = new Rails\Cache\Cache(self::application()->config()->cache_store);
        return self::$cache;
    }
    
    /**
     * Returns logger set in config.
     * If arguments are passed, they will be logged with vars().
     */
    static public function log()
    {
        if (!self::$config->logger) {
            self::$config->logger = self::defaultLogger();
        } elseif (self::$config->logger instanceof \Closure) {
            $closure = self::$config->logger;
            self::$config->logger = $closure();
        }
        
        if (func_num_args()) {
            return self::$config->logger->vars(func_get_args());
        }
        
        return self::$config->logger;
    }
    
    static public function env()
    {
        return self::$env;
    }
    
    static public function root()
    {
        return self::$root;
    }
    
    static public function publicPath()
    {
        return self::$publicPath;
    }
    
    static public function path()
    {
        return self::$path;
    }
    
    static public function cli()
    {
        return self::$cli;
    }
    
    static public function console()
    {
        if (!self::$console) {
            self::$console = new Rails\Console\ApplicationConsole();
        }
        return self::$console;
    }
    
    static public function errorHandler($errno, $errstr, $errfile, $errline, $errargs)
    {
        $errtype = '';
        
        switch ($errno) {
            case E_WARNING:
                $class_name = 'Warning';
                break;

            case E_NOTICE:
                $class_name = 'Notice';
                break;
            
            case E_RECOVERABLE_ERROR:
                $class_name = 'Catchable';
                break;

            case E_USER_NOTICE:
                $class_name = 'UserNotice';
                break;
                
            case E_USER_WARNING:
                $class_name = 'UserWarning';
                break;

            default:
                /**
                 * When an "Unknown" error is triggered, the Loader isn't invoked for some reason.
                 * Hence the manual requires.
                 */
                require_once __DIR__ . '/Exception/ExceptionInterface.php';
                require_once __DIR__ . '/Exception/ExceptionTrait.php';
                require_once __DIR__ . '/Exception/ErrorException.php';
                require_once __DIR__ . '/Exception/PHPError/ExceptionInterface.php';
                require_once __DIR__ . '/Exception/PHPError/Base.php';
                require_once __DIR__ . '/Exception/PHPError/Unknown.php';
                $class_name = 'Unknown';
                $errtype = '[ErrNo '.$errno.']';
                break;
        }
        
        $class = 'Rails\Exception\PHPError\\' . $class_name;
        
        if (strpos($errfile, self::$path) === 0)
            $errfile = 'Rails' . substr($errfile, strlen(self::$path));
        
        $extra_params = ['php_error' => true, 'errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline, 'errargs' => $errargs];
        
        if ($errtype)
            $errtype .= ': ';
        
        $e = new $class(
            sprintf('%s%s ', $errtype, $errstr)
        );
        
        $e->error_data($extra_params);
        
        throw $e;
    }
    
    static public function exceptionHandler($e)
    {
        if (self::$cli) {
            $report = Rails\Exception\Reporting\Reporter::create_report($e);
            $report = Rails\Exception\Reporting\Reporter::cleanup_report($report);
            self::console()->terminate($report);
        } else {
            self::reportException($e);
        }
    }
    
    static public function systemExit()
    {
        if (!self::$systemExit) {
            self::$systemExit = new Rails\SystemExit\SystemExit();
        }
        return self::$systemExit;
    }
    
    static public function runSystemExit()
    {
        if (self::$systemExit) {
            self::$systemExit->run();
        }
    }
    
    static public function services()
    {
        if (!self::$serviceManager) {
            self::$serviceManager = new Rails\ServiceManager\ServiceManager();
        }
        return self::$serviceManager;
    }
    
    static private function reportException(Exception $e)
    {
        Rails\ActionView\ActionView::clean_buffers();
        
        $parmas = [];
        $params['status'] = $e instanceof Rails\Exception\ExceptionInterface ? $e->status() : 500;
        
        $params['report'] = Rails\Exception\Reporting\Reporter::create_report($e, $params);
        
        self::log()->message(Rails\Exception\Reporting\Reporter::cleanup_report($params['report']));
        
        if (!self::application() || !self::application()->dispatcher()) {
            try {
                if (!self::application()) {
                    self::_early_exception($e, $params['report']);
                }
                self::application()->setDispatcher();
            } catch (Exception $e) {
                self::_early_exception($e, $params['report']);
            }
        }
        self::application()->dispatcher()->response()->headers()->status($params['status']);
        
        if (self::config()->action_controller->exception_handler && !self::config()->consider_all_requests_local) {
            try {
                self::$customExceptionHandlerRan = true;
                $handler = Rails\ActionController\ActionController::load_exception_handler(self::$config->action_controller->exception_handler, $params['status']);
                Rails::application()->set_controller($handler);
                $handler->handleException($e);
            } catch (Exception $e) {
                self::_early_exception($e, $params['report']);
            }
        } else {
            $renderer = new Rails\ActionController\Response\Error($e, $params);
            self::application()->dispatcher()->response()->headers()->contentType('html');
            self::application()->dispatcher()->response()->body($renderer->render_view()->get_contents());
        }
        self::application()->dispatcher()->respond();
        exit;
    }
    
    static private function _early_exception($e, $html)
    {
        self::log()->exception($e);
        
        if (Rails::application() && Rails::application()->config()->consider_all_requests_local) {
            echo $html;
        } else {
            echo "<strong>An error occured.</strong>";
        }
        exit;
    }
    
    static private function setRailsConfig()
    {
        $paths = array_merge([
            get_include_path(),
            self::$root . '/lib',
            self::$root . '/vendor',
        ]);
        set_include_path(implode(PATH_SEPARATOR, $paths));
        
        # Set loader.
        require self::$path . '/Loader/Loader.php';
        
        # Guessing ZF2 and Symfony paths
        // if (self::$zf2Path) {
            // $zf2Path = self::$zf2Path;
        // } else {
            // $zf2Path = realpath(self::$path . '/../../../ZF2/library');
            // if (!$zf2Path) {
                // throw new Rails\Exception\RuntimeException(
                    // sprintf(
                        // "Can't find path to ZF2 library (tried: %s)",
                        // $zf2Path
                    // )
                // );
            // }
        // }
        
        // if (self::$sfPath) {
            // $sfPath = self::$sfPath;
        // } else {
            // $sfPath = realpath(self::$path . '/../../..');
            // if (!$sfPath) {
                // throw new Rails\Exception\RuntimeException(
                    // sprintf(
                        // "Can't find path to Symfony (tried: %s)",
                        // $sfPath
                    // )
                // );
            // }
        // }
        
        self::$loader = new Rails\Loader\Loader([
            dirname(self::$path),
            self::$config->paths->models->toString(),
            self::$config->paths->helpers,
            self::$config->paths->controllers->toString(),
            self::$config->paths->mailers->toString(),
            self::$root . '/lib',
            self::$root . '/vendor',
            // $zf2Path,
            // $sfPath,
        ]);
        
        $autoloadFile = defined("COMPOSER_AUTOLOAD_FILE") ?
                        COMPOSER_AUTOLOAD_FILE :
                        Rails::path() . '/../../../../autoload.php';
        self::$loader->setComposerAutoload(require $autoloadFile);
        // $loader = require $autoloadFile;
        // vpe($loader->loadClass("Rails\Bootstrap2\Initializer"));
        spl_autoload_register([Rails::loader(), 'loadClass']);
        
        set_exception_handler('Rails::exceptionHandler');
        
        register_shutdown_function('Rails::runSystemExit');
        
        # This will be re-set in Rails_Application, setting also the customized second parameter.
        set_error_handler('Rails::errorHandler', E_ALL);
    }
    
    static private function defaultConfig()
    {
        return require __DIR__ . '/Config/default_config.php';
    }
    
    static private function defaultLogger()
    {
        $logLevel = (string)self::$config->log_level;
        
        switch ($logLevel) {
            case '0':
            case 'emergency':
                $level = Zend\Log\Logger::EMERG;
                break;
            
            case '1':
            case 'alert':
                $level = Zend\Log\Logger::ALERT;
                break;
            
            case '2':
            case 'critical':
                $level = Zend\Log\Logger::CRIT;
                break;
            
            case '3':
            case 'error':
                $level = Zend\Log\Logger::ERR;
                break;
            
            case '4':
            case 'warning':
                $level = Zend\Log\Logger::WARN;
                break;
            
            case '4':
            case 'notice':
                $level = Zend\Log\Logger::NOTICE;
                break;
            
            case '5':
            case 'info':
                $level = Zend\Log\Logger::INFO;
                break;
            
            case '6':
            case 'debug':
                $level = Zend\Log\Logger::DEBUG;
                break;
            
            default:
                $level = self::$config->log_level;
                break;
        }
        
        if (false !== $level && null !== $level) {
            $filter = new Zend\Log\Filter\Priority($level);
            $writer->addFilter($filter);
        }
        
        $formatter = new Rails\Log\Formatter\Simple();
        
        $writer = new Zend\Log\Writer\Stream(self::$config->log_file);
        $writer->setFormatter($formatter);
        
        $logger = new Rails\Log\Logger();
        $logger->addWriter($writer);
        
        return $logger;
    }
}
