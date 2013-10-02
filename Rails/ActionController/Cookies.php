<?php
namespace Rails\ActionController;

/**
 * This class will be available in controllers by
 * calling '$this->cookies()'.
 *
 * Cookies can be set through this class. The cookies won't
 * be actually sent to the browser until the controller
 * finishes its work.
 *
 * Doing '$cookies->some_cookie' will call __get,
 * which checks the $_COOKIE variable. In other words, it will
 * check if the request sent a cookie named 'some_cookie'.
 * If not, it will return null.
 *
 * To set a cookie, use add(), to remove them use remove().
 * To check if a cookie was set by the controller, use in_jar().
 */
class Cookies
{
    /**
     * Holds cookies that will be added at the
     * end of the controller.
     */
    private $jar = array();
        
    /**
     * To know if cookies were set or not.
     */
    private $cookiesSet = false;
    
    public function __get($name)
    {
        if (isset($this->jar[$name]))
            return $this->jar[$name]->value();
        elseif (isset($_COOKIE[$name]))
            return $_COOKIE[$name];
        else
            return null;
    }
    
    public function __set($prop, $params)
    {
        if (is_array($params)) {
            if (isset($params['value'])) {
                $value = $params['value'];
                unset($params['value']);
            } else {
                $value = '';
            }
        } else {
            $value = $params;
            $params = [];
        }
        $this->add($prop, $value, $params);
    }
    
    public function add($name, $value, array $params = array())
    {
        $p = array_merge($this->defaultParams(), $params);
        $this->jar[$name] = new Cookie($name, $value, $p['expires'], $p['path'], $p['domain'], $p['secure'], $p['httponly'], $p['raw']);
        return $this;
    }
    
    public function delete($name, array $params = [])
    {
        $this->add($name, '', array_merge($params, [
            'expires' => time() - 172800
        ]));
        return $this;
    }
    
    /**
     * Actually sets cookie in headers.
     */
    public function set()
    {
        if (!$this->cookiesSet) {
            foreach ($this->jar as $c)
                $c->set();
            $this->cookiesSet = true;
        }
    }
    
    private function defaultParams()
    {
        $defaults = \Rails::application()->config()->cookies->toArray();
        if (!$defaults['path']) {
            $defaults['path'] = \Rails::application()->router()->basePath() . '/';
        }
        return $defaults;
    }
}
