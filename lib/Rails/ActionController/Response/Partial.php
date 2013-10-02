<?php
namespace Rails\ActionController\Response;

use Rails\ActionView;

# TODO
class ActionController_Response_Partial extends Base
{
    public function _render_view()
    {
        $params = [$this->_params['partial']];
        if (isset($this->_params['locals']))
            $params = array_merge($params, [$this->_params['locals']]);
        
        # Include helpers.
        ActionView\ViewHelpers::load();
        # Create a template so we can call render_partial.
        # This shouldn't be done this way.
        
        $template = new ActionView\Template([]);
        
        $this->_body = call_user_func_array([$template, 'render_partial'], $params);
    }
    
    public function _print_view()
    {
        return $this->_body;
    }
}