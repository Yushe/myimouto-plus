<?php
namespace Rails\ActionController\Response;

class ActionController_Response_Redirect extends Base
{
    private $_redirect_params;
    
    private $_header_params;
    
    public function __construct(array $redirect_params)
    {
        // vde($redirect_params);
        # Todo: not sure what will be in second index
        # for now, only http status.
        list($this->_redirect_params, $this->_header_params) = $redirect_params;
    }
    
    protected function _render_view()
    {
        $url = \Rails::application()->router()->urlFor($this->_redirect_params);
        \Rails::application()->dispatcher()->response()->headers()->location($url);
    }
    
    protected function _print_view()
    {
    }
}