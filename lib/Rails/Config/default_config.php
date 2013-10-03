<?php
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/../Paths/Paths.php';
require_once __DIR__ . '/../Paths/Path.php';

$appPath   = new Rails\Paths\Path('app', [self::$root]);
$viewsPath = new Rails\Paths\Path('views', [$appPath]);

$paths = new Rails\Paths\Paths([
    'application' => $appPath,
    'views'       => $viewsPath,
    'config'      => new Rails\Paths\Path('config', [self::$root]),
    'controllers' => new Rails\Paths\Path('controllers', [$appPath]),
    'helpers'     => new Rails\Paths\Path('helpers', [$appPath]),
    'models'      => new Rails\Paths\Path('models', [$appPath]),
    'layouts'     => new Rails\Paths\Path('layouts', [$viewsPath]),
    'mailers'     => new Rails\Paths\Path('mailers', [$appPath]),
    'log'         => new Rails\Paths\Path('log', [self::$root]),
]);

$config = new Rails\Config\Config();

$config->paths = $paths;

$config->base_path = ''; // must NOT include leading nor trailing slash.

/**
 * If names begin with a letter, the path of the file
 * will be relative to Rails::root().
 */
$config->load_files = [];
    
$config->rails_panel_path = false; // NO leading nor trailing slashes, e.g. "sysadmin", "foo/bar/sysadmin"

$config->safe_ips = [
    '127.0.0.1',
    '::1'
];

$config->error = [
    'report_types'  => E_ALL,
    'log'           => true,
    'log_max_size'  => 1024
];

$config->consider_all_requests_local = !empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1';

$config->date = [
    'timezone' => null
];

$config->i18n = [
    'path'           => Rails::root() . '/config/locales',
    'default_locale' => 'en'
];

$config->action_controller = [
    'base' => [
        'default_charset' => 'utf-8'
    ],
    'exception_handler' => false
];

# Temporary flag
$config->ar2 = false;

$config->active_record = [
    'use_cached_schema' => false,
    // 'base' => [
        // /**
         // * If true, overloading attributes or associations via __get() won't work
         // * on models. This is temporary, as now overloading should be don't through __call().
         // */
        // 'call_only_attr_overload' => false
    // ]
];

$config->action_view = [
    'layout' => 'application',
    'suffix' => 'phtml',
    'helpers' => [
        // 'will_paginate' => [
            // /**
             // * Sets a default custom renderer class.
             // * Built-in renderers are Bootstrap and Legacy.
             // * A custom renderer class can be created, extending
             // * Rails\ActionView\Helper\WillPaginate\AbstractRenderer.
             // */
            // 'renderer'    => 'bootstrap',
            // 'always_show' => false  # Show pagination even if there's only 1 page
        // ]
    ]
];

$config->action_mailer = [
    /**
     * Allows "sendmail", "smtp" or "file".
     * Also allows a Closure that must return a transport
     * implementing Zend\Mail\Transport\TransportInterface
     */
    'delivery_method' => 'sendmail',
    
    'smtp_settings'   => [
        'address'        => '',
        'port'           => 25,
        'domain'         => '',
        'user_name'      => '',
        'password'       => '',
        'authentication' => 'login',
        'enable_starttls_auto' => true // Requires openssl PHP extension.
    ],
    
    'file_settings'   => [
        /**
         * Directory where mails will be stored.
         *
         * Default directory is created automatically, otherwise
         * it must already exist.
         *
         * Defaults to Rails::root() . /tmp/mail.
         */
        'location'       => null,
        
        /**
         * Any callable argument.
         *
         * Defaults to Rails\ActionMailer\ActionMailer::filenameGenerator()
         */
        'name_generator' => null
    ],
    
    'default_options' => [],
];

/**
 * If assets are enabled, this option will make Assets serve static assets from the public folder
 * or non-static from application's folders.
 */
$config->serve_static_assets = false;

$config->assets = [
    'enabled' => true,
    
    /**
     * In development, files included by manifests are served individually to help debug javascript.
     * However, including many files can increase the page load times a lot.
     * With this option, manifest files and their children are concatenated, resulting in 1 file.
     */
    'concat' => false,
    
    /**
     * Additional [absolute] paths to search assets in.
     */
    'paths' => [],
    
    /**
     * Defines how an extension is handled.
     * Pass either an array or a Closure.
     *
     * The Closure must expect 1 parameter which are the contents
     * of the file to be processed.
     *
     * Arrays accept 4 parameters:
     *  file: if present, the file will be require()'d
     *  class_name: the name of the class.
     *  method: the name of the method to which the contents of the file
     *          will be passed.
     *  static: if true, the method will be called statically.
     */
    'css_extensions' => [
        'scss' => [
            'file'       => 'scss.inc.php',
            'class_name' => 'scssc',
            'method'     => 'compile',
            'static'     => false
        ]
    ],
    
    'js_extensions' => [
        // CoffeeScript
    ],
    
    /**
     * This is Not zip compression.
     * This tells Assets to minify files upon compile using the compressors
     * defined below.
     */
    'compress' => true,
    
    /**
     * Compressors.
     * Accepts an array like the one for extensions.
     */
    'css_compressor' => [
        'class_name' => 'Minify_CSS_Compressor',
        'method'     => 'process'
    ],
    'js_compressor' => [
        'class_name' => 'Rails\Assets\Parser\Javascript\ClosureApi\ClosureApi',
        'method'     => 'minify'
    ],
    
    /**
     * Create a gzipped version of compiled files.
     * Uses gzencode()
     */
    'gz_compression' => true,
    
    'gz_compression_level' => 9,
    
    /**
     * Names of the manifest files that will be compiled upon
     * Assets::compileAll(). Only names, no paths.
     */
    'precompile' => [
        'application.js',
        'application.css'
    ],
    
    /**
     * Non js or css files that will be copied to the public assets folder
     * when compiling. These values will be passed to glob() with GLOB_BRACE.
     */
    'patterns' => [
        '*.gif',
        '*.png',
        '*.jpg',
        '*.jpeg',
    ],
    
    /**
     * This is used as the URL path to server non-static assets for
     * development, and is also the name of the folder where assets are
     * stored.
     */
    'prefix' => '/assets',
    
    /**
     * Path where the prefix folder will be created, and inside it, compiled
     * assets will finally be stored.
     */
    'compile_path' => self::$publicPath,
    
    # Generate digests for assets URLs
    'digest' => false,
    
    # Has no use.
    'version' => '1.0'
];

$config->cookies = [
    'expires'  => 0,
    'path'     => null, # Defaults to base_path() . '/', set in Cookies
    'domain'   => null,
    'secure'   => false,
    'httponly' => false,
    'raw'      => false
];

$config->eager_load_paths = [];

/**
 * Set's the cache storing class.
 *
 * For FileStore
 * cache_store ( string 'file_store', string $cache_path )
 *
 * For Memcached
 * cache_store ( string 'memcached' [, mixed $server1 [, mixed $server2 ... ] ] )
 * Defaults to host "localhost" and port 11211.
 * Examples:
 * Add 1 server with default options.
 * $config->cache_store = [ 'memcached' ];
 * Add 1 server with foobarhost as host and default port.
 * $config->cache_store = [ 'memcached', 'foobarhost' ];
 * Add 2 servers: one with localhost as host with default port, and the other one
 *  with other.server as host and 4456 as port.
 * $config->cache_store = [ 'memcached', 'localhost', ['other.server', 4456] ];
 */
$config->cache_store = ['file_store', Rails::root() . '/tmp/cache'];

/**
 * This is supposed to be used to set a custom logger.
 * But rather, customize the default logger in Application\Base::init().
 */
$config->logger = null;

$config->log_file = Rails::root() . '/log/' . Rails::env() . '.log';

/**
 * Accepts int from 0 to 8.
 */
$config->log_level = false;

return $config;