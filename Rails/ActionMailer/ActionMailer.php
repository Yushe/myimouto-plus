<?php
namespace Rails\ActionMailer;

use Zend\Mail;
use Rails;

abstract class ActionMailer
{
    static protected $transport;
    
    static public function load_mailer($name, $raise_exception = true)
    {
        if (!class_exists($name, false)) {
            $mails_path = Rails::config()->paths->mailers;
            $file = $mails_path . '/' . Rails::services()->get('inflector')->underscore($name) . '.php';
            if (!is_file($file)) {
                if ($raise_exception)
                    throw new Exception\RuntimeException(
                        sprintf('No file found for mailer %s (searched in %s)', $name, $file)
                    );
                return false;
            }
            
            require $file;
            
            if (!class_exists($name, false)) {
                if ($raise_exception)
                    throw new Exception\RuntimeException(
                        sprintf("File for mailer %s doesn't contain expected class", $name)
                    );
                return false;
            }
        }
        
        self::init();
        return true;
    }
    
    static public function transport(Mail\Transport\TransportInterface $transport = null)
    {
        if (null !== $transport) {
            self::$transport = $transport;
        } elseif (!self::$transport) {
            self::setDefaultTransport();
        }
        return self::$transport;
    }
    
    static public function filenameGenerator()
    {
        return 'action_mailer_' . $_SERVER['REQUEST_TIME'] . '_' . mt_rand() . '.tmp';
    }
    
    static protected function setDefaultTransport()
    {
        $config = Rails::application()->config()->action_mailer;
        
        switch ($config['delivery_method']) {
            /**
             * Rails to Zend options:
             * address          => name
             * domain           => host
             * port             => port
             * authentication   => connection_class
             * user_name        => connection_config[username]
             * password         => connection_config[password]
             * enable_starttls_auto (true)  => connection_config[ssl] => 'tls'
             * enable_starttls_auto (false) => connection_config[ssl] => 'ssl'
             */
            case 'smtp':
                $defaultConfig = [
                    'address'    => '127.0.0.1',
                    'domain'   => 'localhost',
                    'port'      => 25,
                    'user_name' => '',
                    'password'  => '',
                    
                    'enable_starttls_auto' => true,
                    
                    /**
                     * Differences with RoR:
                     * - ZF2 adds the "smtp" option
                     * - The "cram_md5" option is called "crammd5"
                     */
                    'authentication' => 'login'
                ];
                
                $smtp = array_merge($defaultConfig, $config['smtp_settings']->toArray());
                
                $options = [
                    'host'              => $smtp['address'],
                    'name'              => $smtp['domain'],
                    'port'              => $smtp['port'],
                    'connection_class'  => $smtp['authentication'],
                    'connection_config' => [
                        'username'  => $smtp['user_name'],
                        'password'  => $smtp['password'],
                        'ssl'       => $smtp['enable_starttls_auto'] ? 'tls' : null,
                    ],
                ];
                
                $options = new Mail\Transport\SmtpOptions($options);
                
                $transport = new Mail\Transport\Smtp();
                
                $transport->setOptions($options);
                break;
            
            /**
             * location       => path
             * name_generator => callback
             */
            case  'file':
                $customOpts = $config['file_settings'];
                $options = [];
                
                if ($customOpts['location'] === null) {
                    $dir = Rails::root() . '/tmp/mail';
                    if (!is_dir($dir))
                        mkdir($dir, 0777, true);
                    $customOpts['location'] = $dir;
                }
                
                $options['path'] = $customOpts['location'];
                
                if ($customOpts['name_generator'] === null) {
                    $options['callback'] = 'Rails\ActionMailer\ActionMailer::filenameGenerator';
                } else {
                    $options['callback'] = $customOpts['name_generator'];
                }
                
                $fileOptions = new Mail\Transport\FileOptions($options);
                
                $transport = new Mail\Transport\File();
                
                $transport->setOptions($fileOptions);
                
                break;
            
            case ($config['delivery_method'] instanceof Closure):
                $transport = $config['delivery_method']();
                break;
        }
        
        self::transport($transport);
    }
}