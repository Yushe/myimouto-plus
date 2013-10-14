<?php
namespace Rails\ActionController;

use stdClass;
use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use ApplicationController;
use Rails;
use Rails\ActionController\ActionController;
use Rails\ActionController\ExceptionHandler;
use Rails\ActionView;
use Rails\Routing\Traits\NamedPathAwareTrait;
use Rails\Routing\UrlFor;

/**
 * This class is expected to be extended by the ApplicationController
 * class, and that one is expected to be extended by the current controller's class.
 * There can't be other extensions for now as they might cause
 * unexpected results.
 *
 * ApplicationController class will be instantiated so its _init()
 * method is called, because it is supposed to be called always, regardless
 * the actual controller.
 */
abstract class Base extends ActionController
{
    use NamedPathAwareTrait;
    
    const CONTENT_TYPE_JSON = 'application/json';
    
    const CONTENT_TYPE_XML  = 'application/xml';
    
    const DEFAULT_REDIRECT_STATUS = 302;
    
    const APP_CONTROLLER_CLASS = 'ApplicationController';
    
    static private $RENDER_OPTIONS = [
        'action','template', 'lambda', 'partial', 'json', 'xml', 'text', 'inline', 'file', 'nothing'
    ];
    
    /**
     * @see respondWith()
     */
    private $respondTo = [];
    
    private $layout;
    
    private $actionRan = false;
    
    # Must not include charset.
    private $contentType;
    
    private $charset;
    
    private $status = 200;

    private
        /**
         * Variables set to the controller itself will be stored
         * here through __set(), and will be passed to the view.
         *
         * @var stdClass
         * @see vars()
         */
        $locals,
        
        $_array_names = [],
         
        /**
         * Extra actions to run according to request format.
         *
         * This accepts the following params:
         * Null: Nothing has been set (default).
         * True: Can respond to format, no action needed.
         * Closure: Can respond to format, run Closure.
         * False: Can't respond to format. Render Nothing with 406.
         */
        $_respond_action,
        
        /**
         * Default variable to respond with.
         *
         * @see respond_with()
         */
        $_respond_with = [],

        /**
         * Stores the render parameters.
         *
         * @see render_params()
         * @see _render()
         * @see _redirect_to()
         * @see _set_response_params()
         */
        $_response_params = [],
        
        $redirect_params,
        $render_params,
        
        $_response_extra_params = [];
    
    /**
     * ReflectionClass for ApplicationController.
     */
    private $appControllerRefls = [];
    
    private $selfRefl;
    
    /**
     * Children classes shouldn't override __construct(),
     * they should override init() instead.
     *
     * The classes "ApplicationController" are practically abstract classes.
     * Some methods declared on them (init() and filters()) will be bound to the
     * actual controller class and executed.
     * This will happen with any class called "ApplicationController"
     * under any namespace.
     */
    public function __construct()
    {
        $class = get_called_class();
        
        if (!$this->isAppController($class)) {
            $this->locals = new stdClass();
            $this->selfRefl = new ReflectionClass($class);
            
            if (!$this instanceof ExceptionHandler) {
                $this->_set_default_layout();
                $reflection = $this->selfRefl;
                
                while (true) {
                    $parent = $reflection->getParentClass();
                    if ($this->isAppController($parent->getName())) {
                        $this->appControllerRefls[] = $parent;
                    } elseif ($parent->getName() == __CLASS__) {
                        break;
                    }
                    $reflection = $parent;
                }
                $this->appControllerRefls = array_reverse($this->appControllerRefls);
                $this->run_initializers();
            }
        }
    }
    
    public function __set($prop, $val)
    {
        $this->setLocal($prop, $val);
    }
    
    public function __get($prop)
    {
        if (!array_key_exists($prop, (array)$this->locals)) {
            throw new Exception\RuntimeException(
                sprintf("Trying to get undefined local '%s'", $prop)
            );
        }
        return $this->locals->$prop;
    }
    
    public function __call($method, $params)
    {
        if ($this->isNamedPathMethod($method)) {
            return $this->getNamedPath($method, $params);
        }
        
        throw new Exception\BadMethodCallException(
            sprintf("Called to unknown method: %s", $method)
        );
    }
    
    public function response_params()
    {
        return $this->_response_params;
    }
    
    public function responded()
    {
        return $this->render_params || $this->redirect_params;
    }
    
    public function setLocal($name, $value)
    {
        if (is_array($value)) {
            $this->_array_names[] = $name;
            $this->$name = $value;
        } else {
            $this->locals->$name = $value;
        }
        return $this;
    }
    
    public function locals()
    {
        foreach ($this->_array_names as $prop_name)
            $this->locals->$prop_name = $this->$prop_name;
        return $this->locals;
    }
    
    public function vars()
    {
        return $this->locals();
    }
    
    /**
     * Shortcut
     */
    public function I18n()
    {
        return Rails::services()->get('i18n');
    }
    
    public function action_name()
    {
        return Rails::services()->get('inflector')->camelize(Rails::application()->dispatcher()->router()->route()->action, false);
    }
    
    public function run_request_action()
    {
        $action = $this->action_name();
        
        if (!$this->_action_method_exists($action) && !$this->_view_file_exists()) {
            throw new Exception\UnknownActionException(
                sprintf("The action '%s' could not be found for %s", $action, get_called_class())
            );
        }
        
        $this->runFilters('before');
        /**
         * Check if response params where set by the
         * before filters.
         */
        if (!$this->responded()) {
            if ($this->_action_method_exists($action)) {
                $this->actionRan = true;
                $this->runAction($action);
            }
            $this->runFilters('after');
        }
        
        if ($this->redirect_params) {
            $this->_set_redirection();
        } else {
            $this->_parse_response_params();
            $this->_create_response_body();
        }
    }
    
    public function actionRan()
    {
        return $this->actionRan;
    }
    
    public function status()
    {
        return $this->status;
    }
    
    /**
     * This method was created so it can be extended in
     * the controllers with the solely purpose of
     * customizing the handle of Exceptions.
     */
    protected function runAction($action)
    {
        $this->$action();
    }
    
    protected function init()
    {
    }
    
    /**
     * Adds view helpers to the load list.
     * Accepts one or many strings.
     */
    public function helper()
    {
        ActionView\ViewHelpers::addAppHelpers(func_get_args());
    }
    
    /**
     * Respond to format
     *
     * Sets to which formats the action will respond.
     * It accepts a list of methods that will be called if the
     * request matches the format.
     *
     * Example:
     *   $this->respondTo(array(
     *      'html',
     *      'xml' => array(
     *          '_some_method' => array($param_1, $param_2),
     *          '_render'      => array(array('xml' => $obj), array('status' => 403))
     *      )
     *  ));
     *
     * Note: The way this function receives its parameters is because we can't use Closures,
     * due to the protected visibility of the methods such as _render().
     *
     * In the case above, it's stated that the action is able to respond to html and xml.
     * In the case of an html request, no further action is needed to respond; therefore,
     * we just list the 'html' format there.
     * In the case of an xml request, the controller will call _some_method($param_1, $param_2)
     * then it will call the _render() method, giving it the variable with which it will respond
     * (an ActiveRecord_Base object, an array, etc), and setting the status to 403.
     * Any request with a format not specified here will be responded with a 406 HTTP status code.
     *
     * By default all requests respond to xml, json and html. If the action receives a json
     * request for example, but no data is set to respond with, the dispatcher will look for
     * the .$format.php file in the views (in this case, .json.php). If the file is missing,
     * which actually is expected to happen, a Dispatcher_TemplateMissing exception will be
     * thrown.
     *
     * @see respond_with()
     */
    public function respondTo($responses)
    {
        $format = $this->request()->format();
        
        foreach ($responses as $fmt => $action) {
            if (is_int($fmt)) {
                $fmt = $action;
                $action = null;
            }
            
            if ($fmt !== $format)
                continue;
            
            if ($action) {
                if (!$action instanceof Closure) {
                    throw new Exception\InvalidArgumentException(sprinft('Only closure can be passed to respondTo, %s passed', gettype($action)));
                }
                $action();
            } else {
                $action = true;
            }
            
            $this->_respond_action = $action;
            return;
        }
        
        /**
         * The request format is not acceptable.
         * Set to render nothing with 406 status.
         */
        Rails::log()->message("406 Not Acceptable");
        $this->render(array('nothing' => true), array('status' => 406));
    }
    
    public function respondWith($var)
    {
        $format = $this->request()->format();
        
        if (!in_array($format, $this->respondTo)) {
            /**
             * The request format is not acceptable.
             * Set to render nothing with 406 status.
             */
            $this->render(array('nothing' => true), array('status' => 406));
        } else {
            $responses = [];
            foreach ($this->respondTo as $format) {
                if ($format == 'html') {
                    $responses[] = 'html';
                } else {
                    $responses[$format] = function() use ($var, $format) {
                        $this->render([$format => $var]);
                    };
                }
            }
            $this->respondTo($responses);
        }
    }
    
    /**
     * Sets layout value.
     */
    public function layout($value = null)
    {
        if (null === $value)
            return $this->layout;
        else
            $this->layout = $value;
    }
    
    public function setLayout($value)
    {
        $this->layout = $value;
    }
    
    protected function setRespondTo(array $respondTo)
    {
        $this->respondTo = $respondTo;
    }
    
    /**
     * @return array
     */
    protected function filters()
    {
        return [];
    }
    
    protected function render($render_params)
    {
        if ($this->responded()) {
            throw new Exception\DoubleRenderException('Can only render or redirect once per action.');
        }
        
        $this->render_params = $render_params;
    }
    
    /**
     * Sets a redirection.
     * Accepts ['status' => $http_status] among the $redirect_params.
     */
    protected function redirectTo($redirect_params)
    {
        if ($this->responded()) {
            throw new Exception\DoubleRenderException('Can only render or redirect once per action.');
        }
        
        $this->redirect_params = $redirect_params;
    }
    
    /**
     * For now we're only expecting one controller that extends ApplicationController,
     * that extends ActionController_Base.
     * This could change in the future (using Reflections) so there could be more classes
     * extending down to ApplicationController > ActionController_Base
     */
    protected function runFilters($type)
    {
        $closures = $this->getAppControllersMethod('filters');
        
        if ($closures) {
            $filters = [];
            foreach ($closures as $closure) {
                $filters = array_merge_recursive($closure(), $filters);
            }
            $filters = array_merge_recursive($filters, $this->filters());
        } else {
            $filters = $this->filters();
        }
        
        if (isset($filters[$type])) {
            /**
             * We have to filter duped methods. We can't use array_unique
             * because the the methods could be like 'method_name' => [ 'only' => [ actions ... ] ]
             * and that will generate "Array to string conversion" error.
             */
            $ranMethods = [];
            
            foreach ($filters[$type] as $methodName => $params) {
                if (!is_array($params)) {
                    $methodName = $params;
                    $params = [];
                }
                
                if ($this->canRunFilterMethod($params, $type) && !in_array($methodName, $ranMethods)) {
                    $this->$methodName();
                    /**
                     * Before-filters may set response params. Running filters stop if one of them does.
                     */
                    if ($type == 'before' && $this->responded()) {
                        break;
                    }
                    $ranMethods[] = $methodName;
                }
            }
        }
    }
    
    protected function setStatus($status)
    {
        $this->status = $status;
    }
    
    private function _action_method_exists($action)
    {
        $method_exists = false;
        $called_class = get_called_class();
        $refl = new ReflectionClass($called_class);
        
        if ($refl->hasMethod($action)) {
            $method = $refl->getMethod($action);
            if ($method->getDeclaringClass()->getName() == $called_class && $method->isPublic())
                $method_exists = true;
        }
        
        return $method_exists;
    }
    
    public function urlFor($params)
    {
        $urlfor = new UrlFor($params);
        return $urlfor->url();
    }
    
    /**
     * If the method for the requested action doesn't exist in
     * the controller, it's checked if the view file exists.
     */
    private function _view_file_exists()
    {
        $route = Rails::application()->dispatcher()->router()->route();
        /**
         * Build a simple path for the view file, as there's no support for
         * stuff like modules.
         * Note that the extension is PHP, there's no support for different
         * request formats.
         */
        $base_path = Rails::config()->paths->views;
        $view_path = $base_path . '/' . $route->path() . '.php';
        return is_file($view_path);
    }
    
    private function _set_default_layout()
    {
        $this->layout(Rails::application()->config()->action_view->layout);
    }
    
    private function canRunFilterMethod(array $params = [], $filter_type)
    {
        $action = Rails::services()->get('inflector')->camelize($this->request()->action(), false);
        
        if (isset($params['only']) && !in_array($action, $params['only'])) {
            return false;
        } elseif (isset($params['except']) && in_array($action, $params['except'])) {
            return false;
        }
        return true;
    }
    
    private function _parse_response_params()
    {
        if ($this->render_params) {
            if (is_string($this->render_params)) {
                $this->render_params = [ $this->render_params ];
            }
            
            if (isset($this->render_params[0])) {
                $param = $this->render_params[0];
                unset($this->render_params[0]);
                
                if ($param == 'nothing') {
                    $this->render_params['nothing'] = true;
                } elseif (is_int(($pos = strpos($param, '/'))) && $pos > 0) {
                    $this->render_params['template'] = $param;
                } elseif ($pos === 0 || strpos($param, ':') === 1) {
                    $this->render_params['file'] = $param;
                    if (!isset($this->render_params['layout'])) {
                        $this->render_params['layout'] = false;
                    }
                } else {
                    $this->render_params['action'] = $param;
                }
            }
            // else {

            // }
        }
        // else {
            // $route = Rails::application()->dispatcher()->router()->route();
            // $this->render_params = ['action' => $route->action];
        // }
    }
    
    /*
     * Protected so it can be accessed by ExceptionHandler
     */
    protected function _create_response_body()
    {
        $route = Rails::application()->dispatcher()->router()->route();
        $render_type = false;
        
        foreach (self::$RENDER_OPTIONS as $option) {
            if (isset($this->render_params[$option])) {
                $render_type = $option;
                $main_param = $this->render_params[$option];
                unset($this->render_params[$option]);
            }
        }
        
        if (!$render_type) {
            $render_type = 'action';
            $main_param = $route->action;
        }
        
        // $render_type = key($this->render_params);
        // $main_param = array_shift($this->render_params);
        
        
        if (isset($this->render_params['status']))
            $this->status = $this->render_params['status'];
        
        $class = null;
        
        switch ($render_type) {
            case 'action':
                # Cut the 'Controller' part of the class name.
                $path = Rails::services()->get('inflector')->underscore(substr(get_called_class(), 0, -10)) . '/' . $main_param;
                
                // if ($route->namespaces())
                    // $path = implode('/', $route->namespaces()) . '/' . $path;
                
                $main_param = $path;
                # Fallthrough
                
            case 'template':
                $respParams = [];
                
                $layout = !empty($this->render_params['layout']) ? $this->render_params['layout'] : $this->layout;
                $respParams['layout'] = $layout;
                
                $ext = pathinfo($main_param, PATHINFO_EXTENSION);
                
                if (!$ext) {
                    $template_name = $main_param;
                    
                    if ($this->request()->format() == 'html') {
                        $ext = 'php';
                    } else {
                        if ($this->request()->format() == 'xml') {
                            $respParams['is_xml'] = true;
                        }
                        $ext = [$this->request()->format(), 'php'];
                        
                        $this->response()->headers()->contentType($this->request()->format());
                    }
                } else {
                    $pinfo = pathinfo($main_param);
                    if ($sub_ext = pathinfo($pinfo['filename'], PATHINFO_EXTENSION)) {
                        $pinfo['filename'] = substr($pinfo['filename'], 0, -1 * (1 + strlen($pinfo['filename'])));
                        $ext = [$sub_ext, $ext];
                    } else {
                        
                    }
                    
                    $template_name = $pinfo['dirname'] . '/' . $pinfo['filename'];
                }
                
                $respParams['template_name'] = $template_name;
                $respParams['extension'] = $ext;
                
                $this->_response_params = $respParams;
                
                # Here we could choose a different responder according to extensions(?).
                $class = 'Rails\ActionController\Response\Template';
                break;
            
            case 'lambda':
                $class = 'Rails\ActionController\Response\Lambda';
                $this->_response_params['lambda'] = $main_param;
                break;
            
            case 'partial':
                $class = 'Rails\ActionController\Response\Partial';
                $this->_response_params['partial'] = $main_param;
                break;
			
            case 'json':
                $this->contentType = self::CONTENT_TYPE_JSON;
                $this->_response_params = $main_param;
                $class = "Rails\ActionController\Response\Json";
                break;
            
            case 'xml':
                $this->contentType = self::CONTENT_TYPE_XML;
                array_unshift($this->_response_params, $main_param);
                $class = "Rails\ActionController\Response\Xml";
                break;
            
            case 'text':
                $this->response()->body($main_param);
                break;
            
            case 'inline':
                $this->_response_params['code'] = $main_param;
                $this->_response_params['layout'] = $this->layout;
                $class = "Rails\ActionController\Response\Inline";
                break;
            
            case 'file':
                $this->_response_params['file'] = $main_param;
                $this->_response_params['layout'] = $this->layout ?: false;
                $class = "Rails\ActionController\Response\File";
                break;
            
            case 'nothing':
                break;
            
            default:
                throw new Exception\RuntimeException(sprintf("Invalid action render type '%s'", $render_type));
                break;
        }
		
        if ($class) {
            $responder = new $class($this->_response_params);
            $responder->render_view();
            $this->response()->body($responder->get_contents());
        }
        
        $this->setHeaders();
    }
    
    private function _set_redirection()
    {
        $redirect_params = $this->redirect_params;
        
        if (!is_array($redirect_params))
            $redirect_params = [$redirect_params];
        
        if (!empty($redirect_params['status'])) {
            $status = $redirect_params['status'];
            unset($redirect_params['status']);
        } else {
            $status = self::DEFAULT_REDIRECT_STATUS;
        }
        
        $url = Rails::application()->router()->urlFor($redirect_params);
        
        $this->response()->headers()->location($url);
        $this->response()->headers()->status($status);
    }
    
    /**
     * Runs initializers for both the actual controller class
     * and it's parent ApplicationController, if any.
     */
    private function run_initializers()
    {
        $method_name = 'init';
        $cn = get_called_class();
        
        # Run ApplicationController's init method.
        if ($inits = $this->getAppControllersMethod($method_name)) {
            foreach ($inits as $init) {
                $init = $init->bindTo($this);
                $init();
            }
        }
        
        $method = $this->selfRefl->getMethod($method_name);
        if ($method->getDeclaringClass()->getName() == $cn) {
            $this->$method_name();
        }
    }
    
    /**
     * Searches through all the ApplicationControllers classes for a method,
     * and returns them all.
     *
     * @return array
     */
    private function getAppControllersMethod($methodName, $scope = '')
    {
        if ($this->appControllerRefls) {
            $methods = [];
            foreach ($this->appControllerRefls as $appRefl) {
                if ($appRefl->hasMethod($methodName)) {
                    $method = $appRefl->getMethod($methodName);
                    
                    if ($this->isAppController($method->getDeclaringClass()->getName())) {
                        if ($scope) {
                            $isScope = 'is' . ucfirst($scope);
                            if (!$method->$isScope()) {
                                continue;
                            }
                        }
                        $methods[] = $method->getClosure($this);
                    }
                }
            }
            
            if ($methods) {
                return $methods;
            }
        }
        
        return false;
    }
    
    private function setHeaders()
    {
        $headers = $this->response()->headers();
        if (null === $this->charset)
            $this->charset = Rails::application()->config()->action_controller->base->default_charset;
        
        if (!$headers->contentType()) {
            if (null === $this->contentType) {
                $this->contentType = 'text/html';
            }
            $contentType = $this->contentType;
            if ($this->charset)
                $contentType .= '; charset=' . $this->charset;
            
            $headers->setContentType($contentType);
        }
        
        $this->response()->headers()->status($this->status);
    }
    
    private function isAppController($class)
    {
        return strpos($class, self::APP_CONTROLLER_CLASS) === (strlen($class) - strlen(self::APP_CONTROLLER_CLASS));
    }
}