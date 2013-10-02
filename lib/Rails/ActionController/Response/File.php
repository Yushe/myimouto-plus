<?php
namespace Rails\ActionController\Response;

use Rails\ActionView;

# TODO
class File extends Base
{
    private $_template;
    
    public function _render_view()
    {
        # Include helpers.
        ActionView\ViewHelpers::load();
        $layout = !empty($this->_params['layout']) ? $this->_params['layout'] : false;
        
        $this->_template = new ActionView\Template($this->_params['file'], ['layout' => $layout]);
        
        $this->_template->setLocals(\Rails::application()->controller()->vars());
        
        $this->_template->renderContent();
    }
    
    public function _print_view()
    {
        return $this->_template->content();
    }
}