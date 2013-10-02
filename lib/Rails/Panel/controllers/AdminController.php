<?php
use Rails\ActiveRecord\ActiveRecord;
use Rails\Toolbox;
use Rails\Routing\Route;

class AdminController extends ApplicationController
{
    use Rails\Panel\AdminControllerTrait;
    
    final public function index()
    {
    }
    
    public function stylesheet()
    {
        $path = realpath(__DIR__ . '/../assets/panel.css');
        $file = new Rails\Assets\File('css', $path);
        $parser = new Rails\Assets\Parser\Base($file);
        $parser->parse();
        
        $this->response()->headers()->setContentType('text/css');
		$this->render(['text' => $parser->parsedFile()]);
    }
    
    final public function genTableData()
    {
        Toolbox\DbTools::generateSchemaFiles();
        $this->toIndex('Database schema files updated');
    }
    
    public function createFiles()
    {
        if ($this->request()->isPost()) {
            Rails::resetConfig('development');
            
            $base_name = trim($this->params()->file_name);
            if ($base_name) {
                if ($this->params()->type['controller'])
                    $this->createControllerFile($base_name);
                if ($this->params()->type['model'])
                    $this->createModelFile($base_name);
                if ($this->params()->type['helper'])
                    $this->createHelperFile($base_name);
            }
            
            Rails::application()->setPanelConfig();
        }
    }
    
    public function showRoutes()
    {
        $router = Rails::application()->dispatcher()->router();
        $this->routes = [
            ['root', null, '/', $router->rootRoute()->to()],
            ['rails_panel', null, '/' . $router->panelRoute()->url(), ''],
        ];
        
        foreach ($router->routes() as $route) {
			if ($route instanceof Route\HiddenRoute) {
				continue;
            }
            $this->routes[] = [
                $route->alias(), strtoupper(implode(', ', $route->via())), '/' . $route->url(), $route->to()
            ];
        }
    }
    
    public function compileAssets()
    {
        $this->error = '';
        if ($this->request()->isPost()) {
            Rails::resetConfig('production');
            
            try {
                if ($this->params()->all) {
                    Rails::assets()->compileAll();
                } elseif ($this->params()->file) {
                    $file = $this->params()->file;
                    Rails::assets()->compileFile($file);
                }
            } catch (Rails\Assets\Parser\Javascript\ClosureApi\Exception\ErrorsOnCodeException $e) {
                if ($e instanceof Rails\Assets\Parser\Javascript\ClosureApi\Exception\ErrorsOnCodeException) {
                    Rails::log()->error(
                        sprintf(
                            "[%s] Asset compilation error for file %s\n%s",
                            date('Y-m-d H:i:s'),
                            $file_path . '.' . $ext,
                            $e->getMessage()
                        )
                    );
                    $message = sprintf("ClosureAPI reported an error - JS file was saved to %s for verfications, error was logged.<br /><pre>%s</pre>",
                        Rails\Assets\Parser\Javascript\ClosureApi\ClosureApi::errorFile(), $e->getMessage());
                } else {
                    // throw $e;
                    // $message = sprintf('%s raised: %s', get_class($e), $e->getMessage());
                }
                
                $this->error = $message;
            }
            
            Rails::resetConfig('development');
            Rails::application()->setPanelConfig();
        }
    }
    
    final protected function toIndex($notice)
    {
        $url = '/' . Rails::application()->config()->rails_panel_path;
        parent::redirectTo(array($url, 'notice' => $notice));
    }
    
    public function redirectTo($redirect_params, array $params = array())
    {
        $redirect_params[0] = '/' . Rails::application()->config()->rails_panel_path . '/' . $redirect_params[0];
        parent::redirectTo($redirect_params, $params);
    }
    
    private function createControllerFile($name, $options = [])
    {
        Toolbox\FileGenerators\ControllerGenerator::generate($name, $options);
    }
    
    private function createModelFile($name, $options = [])
    {
        Toolbox\FileGenerators\ModelGenerator::generate($name, $options);
    }
    
    private function createHelperFile($base_name)
    {
        $name = $base_name . '_helper.php';
        $path = Rails::config()->paths->helpers;
        
        if (is_file($path . '/' . $name))
            return;
        
        $class_name = Rails::services()->get('inflector')->camelize($base_name) . 'Helper';
        $contents = "<?php\nclass $class_name extends Rails\ActionView\Helper\n{\n    \n}";
        file_put_contents($path . '/' . $name, $contents);
    }
}