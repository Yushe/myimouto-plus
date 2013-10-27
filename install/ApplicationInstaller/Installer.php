<?php
namespace ApplicationInstaller;

use Rails;

class Installer extends Base
{
    static private $ACCESS_DENIED_MESSAGE = "Access denied for %s";
    
    static private $PLEASE_WAIT_MESSAGE = "Under maintenance - Please wait a little while";
    
    private $newVersion = '1.0.6';
    
    private $config;
    
    public function dispatch()
    {
        $this->loadConfig();
        
        if ($this->request()->path() == '/') {
            if (!$this->validateSafeIps()) {
                # Client not allowed
                if ($this->dataDirExists()) {
                    # Updating message
                    $this->renderMessage(self::$PLEASE_WAIT_MESSAGE);
                } else {
                    # Installing message
                    $this->renderMessage(sprintf(self::$ACCESS_DENIED_MESSAGE, $_SERVER['REMOTE_ADDR']));
                }
            } else {
                # Client allowed; serve forms/commit actions
                try {
                    # To make things nice, check write permissions
                    # on some paths.
                    $this->checkWriteablePaths();
                
                    if ($this->request()->isGet()) {
                        # Serve forms
                        if ($this->dataDirExists()) {
                            # Update form
                            $this->renderUpdateForm();
                        } else {
                            # Install form
                            $this->renderInstallForm();
                        }
                    } else {
                        # Commit actions
                        if ($this->dataDirExists()) {
                            # Update
                            $this->commitUpdate();
                        } else {
                            # Install
                            $this->commitInstall();
                        }
                    }
                } catch (Exception\ExceptionInterface $e) {
                    $this->renderMessage($e->getMessage());
                }
            }
        } else {
            # Load application and let it serve the request.
            $appClass = get_class(Rails::application());
            $appClass::dispatchRequest();
        }
    }
    
    protected function checkWriteablePaths()
    {
        $paths = [
            '/log',
            '/tmp',
            '/public'
        ];
        
        foreach ($paths as $path) {
            $fullpath = Rails::root() . $path;
            if (!is_writable($fullpath)) {
                throw new Exception\RuntimeException(
                    sprintf('File or path "%s" is not writeable; please make sure you have permissions', $fullpath)
                );
            }
        }
    }
    
    protected function commitInstall()
    {
        $action = new Action\Install($_POST['admin_name'], $_POST['admin_password']);
        $action->commit();
        setcookie('notice', "Installation completed", time() + 10, '/');
        header('Location: /post');
    }
    
    protected function commitUpdate()
    {
        $action = new Action\Update();
        $action->commit();
        setcookie('notice', "Upgrade completed", time() + 10, '/');
        header('Location: /history');
    }
    
    protected function renderInstallForm()
    {
        $this->renderForm('install');
    }
    
    protected function renderUpdateForm()
    {
        $this->renderForm('update', ['newVersion' => $this->newVersion]);
    }
    
    protected function renderForm($name, array $locals = [])
    {
        $file = $this->root() . '/views/' . $name . '.php';
        
        $template = new Rails\ActionView\Template(['file' => $file], ['layout' => $this->layoutFile()], $locals);
        $template->renderContent();
        echo $template->content();
    }
    
    protected function renderMessage($message)
    {
        $template = new Rails\ActionView\Template(['lambda' => function() use ($message) {
            echo $this->contentTag('div', $message, ['style' => 'text-align: center;margin-top: 85px;']);
        }], ['layout' => $this->layoutFile()]);
        $template->renderContent();
        echo $template->content();
    }
    
    protected function installDatabase()
    {
        $installer = new DatabaseInstaller();
        try {
            $installer->install();
        } catch (\Exception $e) {
            $this->renderError($e->getMessage());
        }
    }
    
    protected function installApp()
    {
        $installer = new AppInstaller();
        try {
            $installer->install();
        } catch (\Exception $e) {
            $this->renderError($e->getMessage());
        }
    }
    
    private function layoutFile()
    {
        return $this->root() . '/views/layout.php';
    }
    
    private function dataDirExists()
    {
        return is_dir(Rails::publicPath() . '/data');
    }
    
    private function validateSafeIps()
    {
        return in_array($_SERVER['REMOTE_ADDR'], $this->config['safe_ips']);
    }
    
    private function loadConfig()
    {
        $this->config = require __DIR__ . '/../config.php';
    }
}
