<?php
namespace Rails\ActionView;

use Rails;
use Rails\ActionView\Template\Exception;

class Template extends Base
{
    protected $_filename;
    
    /**
     * Layout filename.
     */
    protected $_layout;
    
    protected $_layout_name;
    
    private $_params;
    
    private $_template_token;
    
    private $_initial_ob_level;
    
    /**
     * Used by Inline responder.
     */
    private $_inline_code;
    
    /**
     * To render for first time, render() must be
     * called, and this will be set to true.
     * Next times render() is called, it will need
     * parameters in order to work.
     */
    private $_init_rendered = false;
    
    /**
     * Template file.
     */
    protected $template_file;
    
    /**
     * Full template path.
     */
    protected $template_filename;
    
    protected $type;
    
    protected $params;
    
    protected $contents;
    
    protected $inline_code;
    
    protected $lambda;
    
    /**
     * $render_params could be:
     * - A string, that will be taken as "file".
     * - An array specifying the type of parameter:
     *  - file: a file that will be required. Can be a relative path (to views folder) or absolute path.
     *  - contents: string that will be included to the layout, if any.
     *  - inline: inline code that will be evaluated.
     *  - lambda: pass an anonyomous function that will be bound to the template and ran.
     */
    public function __construct($render_params, array $params = [], $locals = array())
    {
        if (!is_array($render_params)) {
            $render_params = ['file' => $render_params];
        }
        
        $this->type = key($render_params);
        
        switch ($this->type) {
            case 'file':
                $this->template_file = array_shift($render_params);
                break;
            
            /**
             * Allows to receive the contents for this template, i.e.
             * no processing is needed. It will just be added to the layout, if any.
             */
            case 'contents':
                $this->contents = array_shift($render_params);
                break;
            
            /**
             * Contents will be evalued.
             */
            case 'inline':
                $this->inline_code = array_shift($render_params);
                break;
            
            case 'lambda':
                $this->lambda = array_shift($render_params);
                break;
        }
        $this->params = $params;
        
        if (isset($params['locals'])) {
            $locals = $params['locals'];
            unset($params['locals']);
        }
        
        $locals && $this->setLocals($locals);
    }
    
    public function renderContent()
    {
        Rails\ActionView\ViewHelpers::load();
        $this->_set_initial_ob_level();
        
        if (!$this->_init_rendered) {
            ob_start();
            $this->_init_rendered = true;
            $this->_init_render();
            $this->_buffer = ob_get_clean();
            
            if (!$this->_validate_ob_level()) {
                $status = ob_get_status();
                throw new Exception\OutputLeakedException(
                    sprintf('Buffer level: %s; File: %s<br />Topmost buffer\'s contents: <br />%s',
                        $status['level'], substr($this->_filename, strlen(Rails::root()) + 1), htmlentities(ob_get_clean()))
                );
            }
        }
        return $this;
    }
    
    protected function render(array $params)
    {
        $type = key($params);
        switch ($type) {
            case 'template':
                $file = array_shift($params);
                $template = new self(['file' => $file]);
                $template->renderContent();
                return $template->content();
                break;
        }
    }
    
    public function content($name = null)
    {
        if (!func_num_args())
            return $this->_buffer;
        else
            return parent::content($name);
    }
    
    public function contents()
    {
        return $this->content();
    }
    
    /**
     * Finally prints all the view.
     */
    public function get_buffer_and_clean()
    {
        $buffer = $this->_buffer;
        $this->_buffer = null;
        return $buffer;
    }
    
    public function t($name, array $params = [])
    {
        if (is_array($name)) {
            $params = $name;
            $name = current($params);
        }
        
        if (strpos($name, '.') === 0) {
            if (is_int(strpos($this->template_filename, Rails::config()->paths->views->toString()))) {
                $parts = array();
                $path = substr(
                            $this->template_filename, strlen(Rails::config()->paths->views) + 1,
                            strlen(pathinfo($this->template_filename, PATHINFO_BASENAME)) * -1
                        )
                        . pathinfo($this->template_filename, PATHINFO_FILENAME);
                
                foreach (explode('/', $path) as $part) {
                    $parts[] = ltrim($part, '_');
                }
                $name = implode('.', $parts) . $name;
            }
        }
        
        return parent::t($name, $params);
    }
    
    protected function _init_render()
    {
        $layout_wrap = !empty($this->params['layout']);
        
        if ($layout_wrap) {
            $layout_file = $this->resolve_layout_file($this->params['layout']);
            
            if (!is_file($layout_file))
                throw new Exception\LayoutMissingException(
                    sprintf("Missing layout '%s' [ file => %s ]",
                        $this->params['layout'], $layout_file)
                );
            ob_start();
        }
        
        switch ($this->type) {
            case 'file':
                $this->build_template_filename();
                
                if (!is_file($this->template_filename)) {
                    throw new Exception\TemplateMissingException(
                        sprintf("Missing template file %s", $this->template_filename)
                    );
                }
                
                require $this->template_filename;
                break;
            
            case 'contents':
                echo $this->contents;
                break;
            
            case 'inline':
                eval('?>' . $this->inline_code . '<?php ');
                break;
            
            case 'lambda':
                $lambda = $this->lambda;
                $this->lambda = null;
                $lambda = $lambda->bindTo($this);
                $lambda();
                break;
        }
        
        if ($layout_wrap) {
            $this->_buffer = ob_get_clean();
            $layout = new Layout($layout_file, ['contents' => $this->_buffer], $this->locals);
            $layout->renderContent();
            echo $layout->content();
            // require $layout_file;
        }
    }
    
    private function _set_initial_ob_level()
    {
        $status = ob_get_status();
        $this->_initial_ob_level = $this->_get_ob_level();
    }
    
    private function _validate_ob_level()
    {
        $status = ob_get_status();
        return $this->_initial_ob_level == $this->_get_ob_level();
    }
    
    protected function resolve_layout_file($layout)
    {
        if (strpos($layout, '/') === 0 || preg_match('/^[A-Za-z]:(?:\\\|\/)/', $layout))
            return $layout;
        else {
            if (is_int(strpos($layout, '.')))
                return Rails::config()->paths->layouts . '/' . $layout;
            else
                return Rails::config()->paths->layouts . '/' . $layout . '.php';
        }
    }
    
    private function build_template_filename()
    {
        $template = $this->template_file;
        if (strpos($template, '/') === 0 || preg_match('/^[A-Za-z]:(?:\\\|\/)/', $template))
            $this->template_filename = $template;
        else {
            if (is_int(strpos($template, '.'))) {
                $this->template_filename = Rails::config()->paths->views . '/' . $template;
            } else {
                $this->template_filename = Rails::config()->paths->views . '/' . $template . '.php';
            }
        }
    }
    
    private function _get_ob_level()
    {
        $status = ob_get_status();
        return !empty($status['level']) ? $status['level'] : 0;
    }
}