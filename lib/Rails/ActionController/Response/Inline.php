<?php
namespace Rails\ActionController\Response;

use Rails\ActionView;

# TODO
class Inline extends Base
{
    private $_template;
    
    public function _render_view()
    {
        # Include helpers.
        ActionView\ViewHelpers::load();
        $layout = !empty($this->_params['layout']) ? $this->_params['layout'] : false;
        # Create a template so we can call render_inline;
        $this->_template = new ActionView\Template(['inline' => $this->_params['code']], ['layout' => $layout]);
        $this->_template->setLocals(\Rails::application()->controller()->vars());
        $this->_template->renderContent();
    }
    
    public function _print_view()
    {
        return $this->_template->content();
    }
}