<?php
namespace Rails\ActionController\Response;

use Rails\ActionView;

# TODO
class Partial extends Base
{
    private $_template;
    
    public function _render_view()
    {
        # Include helpers.
        ActionView\ViewHelpers::load();
        $layout = !empty($this->_params['layout']) ? $this->_params['layout'] : false;
        $partial = $this->_params['partial'];
        $locals = (array)\Rails::application()->controller()->locals();
        $this->_template = new ActionView\Template(['lambda' => function() use ($partial, $locals) {
            echo $this->partial($partial, $locals);
        }], ['layout' => $layout]);
        
        // $this->_template->setLocals();
        
        $this->_template->renderContent();
        // $params = [$this->_params['partial']];
        // if (isset($this->_params['locals']))
            // $params = array_merge($params, [$this->_params['locals']]);
        
        # Include helpers.
        // ActionView\ViewHelpers::load();
        # Create a template so we can call render_partial.
        # This shouldn't be done this way.
        
        // $template = new ActionView\Template([]);
        // vpe($this);
        // $this->_body = call_user_func_array([$template, 'renderContent'], $params);
    }
    
    public function _print_view()
    {
        return $this->_template->content();
    }
}
