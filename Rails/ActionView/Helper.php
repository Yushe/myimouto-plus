<?php
namespace Rails\ActionView;

use Rails;
use Rails\ActionController\ActionController;
use Rails\ActionView\Helper\Methods;
use Rails\Routing\Traits\NamedPathAwareTrait;

/**
 * Some parts of this class was taken from Ruby on Rails helpers.
 */
abstract class Helper extends ActionView
{
    use NamedPathAwareTrait;
    
    /**
     * ActionView_Base children for methods that
     * require it when passing Closures, like form().
     */
    private $_view;
    
    public function __call($method, $params)
    {
        if ($this->isNamedPathMethod($method)) {
            return $this->getNamedPath($method, $params);
        } elseif ($helper = ViewHelpers::findHelperFor($method)) {
            $helper->setView($this);
            return call_user_func_array(array($helper, $method), $params);
        }
        
        throw new Exception\BadMethodCallException(
            sprintf("Called to unknown method/helper: %s", $method)
        );
    }
    
    /**
     * Returns instance of Helper\Base
     */
    public function base()
    {
        return ViewHelpers::getBaseHelper();
    }
    
    public function setView(ActionView $view)
    {
        $this->_view = $view;
    }
    
    public function view()
    {
        return $this->_view;
    }
    
    public function urlFor($params)
    {
        return Rails::application()->router()->urlFor($params);
    }
    
    public function params()
    {
        return Rails::application()->dispatcher()->parameters();
    }
    
    public function request()
    {
        return Rails::application()->dispatcher()->request();
    }
    
    public function controller()
    {
        return Rails::application()->controller();
    }
    
    public function u($str)
    {
        return urlencode($str);
    }
    
    public function hexEncode($str)
    {
        $r = '';
        $e = strlen($str);
        $c = 0;
        $h = '';
        while ($c < $e) {
            $h = dechex(ord(substr($str, $c++, 1)));
            while (strlen($h) < 3)
                $h = '%' . $h;
            $r .= $h;
        }
        return $r;
    }
    
    public function h($str, $flags = null, $charset = null)
    {
        $flags === null && $flags = ENT_COMPAT;
        !$charset && $charset = Rails::application()->config()->encoding;
        return htmlspecialchars($str, $flags, $charset);
    }
    
    public function I18n()
    {
        return Rails::services()->get('i18n');
    }
    
    public function t($name)
    {
        return $this->I18n()->t($name);
    }
    
    # TODO: move this method somewhere else, it doesn't belong here.
    protected function parseUrlParams($url_params)
    {
        if ($url_params != '#' && (is_array($url_params) || (strpos($url_params, 'http') !== 0 && strpos($url_params, '/') !== 0))) {
            if (!is_array($url_params))
                $url_params = array($url_params);
            $url_to = Rails::application()->router()->urlFor($url_params, true);
        } else {
            $url_to = $url_params;
        }
        return $url_to;
    }
}
