<?php
namespace Rails\Routing\Route;

use Excpetion;
use Rails;
use Rails\Routing\UrlToken;
use Rails\Routing\Mapper;
use Rails\Routing\Exception;
use Rails\Toolbox;

class Route
{
    const DEFAULT_VAR_CONSTRAINT = '(\w+)';
    
    const GLOBBING_REGEXP    = '(.*?)';
    
    /**
     * URL with variables.
     */
    private $_url;
    
    /**
     * URL with escaped variables.
     */
    private $_escaped_url;
    
    private $_to;
    
    private $_via;
    
    private $_format = 'html';
    
    private $_defaults = array();
    
    /**
        $name => array(
            'constraint' => $regexp  ?: null,
            'value'      => $value   ?: null, // Taken from request url or set by "default"
            'default'    => $default ?: null,
            'is_optional'=> boolean,
            'preceded_by'=> . | / | null      // This will help when building the route, only for optional vars
        )
     */
    private $_vars = array();
    
    private $_optional_groups;
    
    # variables in the order they're defined in the route.
    private $_vars_names = array();
    
    /**
     * Variable values taken from the request url.
     */
    private $_vars_values = array();
    
    /**
     * $var_name => $regexp
     */
    private $_constraints = array();
    
    private $_as;
    
    private $_subdomain;
    
    private $_paths = [];
    
    // private $_namespaces = [];
    
    private $_modules = [];
    
    private $_controller = '';
    
    private $_action = '';
    
    /**
     * Following props serve for Build
     */
    private $_optional_parts = array();
    
    /**
     * Needed to do a diff after building a route,
     * to see which vars were actually used.
     */
    private $_used_vars = array();
    
    private $_build_url;
    
    private $_build_params;
    
    private
        $_rails_panel = false,
        $_assets_route = false;
    
    public function __construct($url, $to, array $params = array())
    {
        foreach ($params as $name => $val) {
            $prop = '_' . $name;
            if (!property_exists($this, $prop))
                throw new Exception\RuntimeException(
                    sprintf("Tried to set invalid property '%s'.", $name)
                );
            $this->$prop = $val;
        }
        
        if (!$to && (is_bool(strpos($url, ':controller')) || is_bool(strpos($url, ':action')))) {
            $parts = explode('/', $url);
            $parts = array_slice($parts, 0, 2);
            if (!isset($parts[1]))
                $parts[1] = 'index';
            
            $parts[1] = preg_split('/\W/', $parts[1], 2)[0];
            
            $this->_to = implode('#', $parts);
        } else
            $this->_to = $to;
        
        # Set module.
        if ($this->_modules && $this->_to) {
            $this->_to = implode(UrlToken::MODULE_SEPARATOR, $this->_modules) . UrlToken::MODULE_SEPARATOR . $this->_to;
        }
        
        if ($this->paths) {
            $scope_paths = $this->paths;
            $scope_paths = $scope_paths ? implode('/', $scope_paths) : '';
            $url && $url = '/' . $url;
            $this->_url = $scope_paths . $url;
        } else {
            $this->_url = $url;
        }
        
        if (!$this->_via) {
            throw new Exception\RuntimeException(
                sprintf(
                    "The 'via' option can't be empty (to=>%s)",
                    $this->_to
                )
            );
        }
        !is_array($this->_via) && $this->_via = array($this->_via);
    }
    
    public function __get($prop)
    {
        $prop_prop = '_' . $prop;
        if (!property_exists($this, $prop_prop))
            throw new Exception\RuntimeException(
                sprintf("Property '%s' doesn't exist.", $prop)
            );
        return $this->$prop_prop;
    }
    
    public function build()
    {
        if (!$this->_escaped_url) {
            $this->_check_format_var();
            $this->_extract_vars();
            $this->_escape_url();
            $this->_set_default_vars_values();
            $this->_set_alias();
        }
    }
    
    public function match($url, $via)
    {
        if ($base_path = Rails::application()->router()->basePath())
            $url = substr($url, strlen($base_path));
        
        $this->build();
        if ($this->_is_root())
            return $this->_match_root($url);
        elseif ($this->rails_admin() && !Rails::application()->validateSafeIps())
            return false;
        
        if (!in_array(strtolower($via), $this->_via))
            return false;
        
        $url = ltrim($url, '/');
        $regex = '/^' . $this->_escaped_url . '$/';
        
        if (!preg_match($regex, $url, $m))
            return false;
        
        array_shift($m);
        
        $var_names = array_keys($this->_vars);
        
        /**
         * Add variable values to _var_values and also check if any
         * of the matches values is also a property (like :format)
         * to set it.
         */
        foreach ($m as $k => $value) {
            if (isset($this->_vars[$var_names[$k]]['constraint'])) {
                if (substr($this->_vars[$var_names[$k]]['constraint'], 0, 1) == '/') {
                    if (!preg_match($this->_vars[$var_names[$k]]['constraint'], $value)) {
                        return false;
                    }
                } else {
                    if ($value !== (string)$this->_vars[$var_names[$k]]['constraint'])
                        return false;
                }
            }
            # Workaround for action: when "/post/", action will be "" and
            # will cause errors; set it to "index".
            if ($var_names[$k] == 'action') {
                $value === "" && $value = 'index';
                $this->_action = $value;
            } elseif ($var_names[$k] == 'format') {
                $value === '' && $value = 'html';
                $this->_format = $value;
            }
            $this->_vars[$var_names[$k]]['value'] = $value;
        }
        
        if (!$this->_assets_route) {
            if (!$this->_parse_token())
                return false;
            // elseif (!$this->_path_exists()) {
                // throw new \Rails_ActionDispatch_Router_Route_Exception_ControllerFileNotFound();
                // throw new Exception\Rails_ActionDispatch_Router_Route_Exception_ControllerFileNotFound();
            // }
        }
        // vpe($this);
        return true;
    }
    
    public function match_with_token($token, array &$params)
    {
        if ($token === $this->_to || $token === $this->alias()) {
            if ($url = $this->build_url($params, false)) {
                return $url;
            }
        } elseif ($this->_has_variable_to()) {
            $to = '/' . preg_replace('/:\w+/', '(\w+)', $this->_to) . '/';
            // $to = '/' . preg_replace('/:\w+/', '(\w+)', preg_quote($this->_to)) . '/';
            $type = $this->_parse_variable_to();
            
            // try {
                if (preg_match($to, $token, $m)) {
                    $params[$type] = $m[1];
                    if ($url = $this->build_url($params, false)) {
                        return $url;
                    }
                }
            // } catch (\Exception $e) {
                // vpe($this);
            // }
        } elseif (!$this->to) {
            list($params['controller'], $params['action']) = explode('#', $token);
            if ($url = $this->build_url($params, false)) {
                return $url;
            }
        }
        
        return '';
    }
    
    /**
     * Builds the URL with params passed and returns it.
     * The params must include all variables defined for
     * the route, and also must pass the regex validation,
     * else an exception will be thrown.
     * Any param that is not used by the route will be ignored.
     *
     * @return string the bare built url, with NO base_path.
     */
    public function build_url(array $params, $raise_e = true)
    {
        // if ($this->_to == 'help#:action')
            
        $this->build();
        $this->_build_params = $params;
        
        $this->_build_url = $this->_url;
        
        # If main variables aren't present, return.
        if (!$this->_check_main_vars()) {
            $this->_build_params = array();
            $this->_build_url = '';
            return false;
        }
        if (($groups = $this->_optional_groups) && (array_shift($groups) ?: true) && $groups) {
            foreach ($groups as $group) {
                $parsed_group = $this->_check_optional_groups($group);
                $this->_build_url = str_replace($group['part'], $parsed_group, $this->_build_url);
            }
        }

        # Remove remaining parentheses.
        $this->_build_url = str_replace(array('(', ')'), '', $this->_build_url);
        # Set build_params as its keys for later call of remaining_params()
        $this->_build_params = array_keys($this->_build_params);
        # Add leading slash as routes don't include it.
        return '/' . $this->_build_url;
    }
    
    public function build_url_path(array $params)
    {
        $build_params = [];
        
        // if (isset($params[0]) && $params[0] instanceof \Rails\ActiveRecord\Base) {
            // $model = array_shift($params);
        // } else
            // $model = false;
        $i = 0;
        // vpe($params);
        // $params = array_values($params);
        foreach ($this->_vars as $name => $data) {
            // if ($model) {
                // $build_params[$name] = $model->$name;
            // } else {
            // vpe($params, !isset($params[$i]), empty($data['type']));
            if (!isset($params[$name]) || empty($data['type']))
                continue;
            // vpe(is_array($params[$name]));
            if (is_array($params[$name])) {
                $build_params = array_merge($build_params, $params[$i]);
            } else {
                $build_params[$name] = $params[$name];
            }
            // }
            $i++;
        }
        
        // vpe($build_params, $this->_vars);
        // vpe($this);
        $url = $this->build_url($build_params);
        // vpe
        // if (!$url) vpe($params, $build_params);
        if ($this->_build_params) {
            if ($query_params = array_filter(array_intersect_key($build_params, array_fill_keys($this->_build_params, true))))
                $url .= '?' . http_build_query($query_params);
        }
        
        return $url;
    }
    
    public function vars()
    {
        $vars = array();
        foreach ($this->_vars as $name => $params) {
            $vars[$name] = $params['value'];
        }
        return $vars;
    }
    
    public function remaining_params()
    {
        return array_fill_keys($this->_build_params, null);
    }
    
    public function alias()
    {
        return $this->_as;
    }
    
    public function to()
    {
        return $this->_to;
    }
    
    public function url()
    {
        return $this->_url;
    }
    
    public function via()
    {
        return $this->_via;
    }
    
    public function namespaces()
    {
        return $this->_modules;
    }
    
    public function modules()
    {
        return $this->_modules;
    }
    
    public function controller()
    {
        return $this->_controller;
    }
    
    public function action()
    {
        return $this->_action;
    }
    
    public function rails_admin()
    {
		return $this instanceof PanelRoute;
        // return $this->_rails_admin;
    }
    
    public function isPanelRoute()
    {
        return $this instanceof PanelRoute;
    }
    
    # TODO: Deprecated
    public function panel_route()
    {
        return $this->isPanelRoute();
    }
    
    public function assets_route()
    {
        return $this->_assets_route;
    }
    
    /**
     * Builds the internal view path for the route out of
     * module, controller and action.
     * i.e. route for /post/show/15 would return
     * this path: post/show[.$format]
     * 
     *
     * TODO: add path_names support.
     */
    public function path(array $params = array())
    {
        $this->build();
        
        $path = [];
        if ($this->_modules)
            $path = $this->_modules;
        $path[] = $this->_controller;
        $path[] = isset($params['action']) ? $params['action'] : $this->_action;
        
        $format = isset($params['format']) ? '.' . $params['format'] : '';
        
        return implode('/', $path) . $format;
    }
    
    public function namespace_path()
    {
        if ($this->_modules) {
            return implode('/', array_map(function($x) { return Rails::services()->get('inflector')->camelize($x); }, $this->_modules));
        }
    }
    
    public function controller_file()
    {
        $path = Rails::config()->paths->controllers . '/' . (($namespacedPath = $this->namespace_path()) ? $namespacedPath . '/' : '') . Rails::services()->get('inflector')->camelize($this->controller) . 'Controller.php';
        return $path;
    }
    
    /**
     * Each namespace can have its own application controller.
     * This is called by Rails_Application when checking for the file for ApplicationController;
     * If we're in a namespace, the path to the application_controller file changes.
     */
    public function namespaced_controller_file()
    {
        if (!$this->_modules)
            return false;
        return Rails::config()->paths->controllers . '/' . $this->namespace_path() . '/application_controller.php';
    }
    
    private function _check_main_vars()
    {
        if (($group = $this->_optional_groups) && ($group = array_shift($group))) {
            foreach ($group as $mv) {
                if (!isset($this->_build_params[$mv])) {
                    return false;
                } else {
                    $this->_build_url = str_replace($this->_vars[$mv]['type'].$mv, $this->_build_params[$mv], $this->_build_url);
                    unset($this->_build_params[$mv]);
                }
            }
        }
        return true;
    }
    
    private function _check_optional_groups($group)
    {
        # Used for workaround.
        $action = false;
        
        $group_str = $part = $group['part'];
        unset($group['part']);
        
        $main_vars = array_shift($group);
        if (!$main_vars)
            $group_str = '';
        
        foreach ($main_vars as $mv) {
            if (!isset($this->_vars[$mv]))
                continue;
            
            if (!isset($this->_build_params[$mv])) {
                $group_str = '';
                break;
            } else {
                # For workaround
                if ($mv == 'action')
                    $action = $this->_build_params['action'];
                $group_str = str_replace($this->_vars[$mv]['type'].$mv, $this->_build_params[$mv], $group_str);
                unset($this->_build_params[$mv]);
            }
        }
        
        if ($group_str) {
            foreach ($group as $subgroup) {
                $subpart = $subgroup['part'];
                $subrpl  = $this->_check_optional_groups($subgroup);
                # For workaround
                if (!$subrpl)
                    array_shift($group);
                $group_str = str_replace($subpart, $subrpl, $group_str);
            }
        }
        /**
         * Here comes the workaround. If the group has only one var
         * and it's "action" and its value is "index" and there are no
         * group memebers left (i.e., this current group is just something with
         * 'index', remove it).
         */
        if (count($main_vars) == 1 && $main_vars[0] == 'action' && $action == 'index' && !$group) {
            $group_str = '';
        }
        return $group_str;
    }
    
    private function _match_root($url)
    {
        if ($url === $this->_root_url()) {
            $this->_parse_token();
            return true;
        }
        return false;
    }
    
    /**
     * Doing this because of the options for the
     * :format var, such as setting it to true or false.
     * By default, it's always added by the end of the
     * route as an optional var.
     */
    private function _check_format_var()
    {
        if ($this->_format !== false && !$this->_is_root() && !preg_match('/(:|\*)format[^\w]?/', $this->_url)) {
            if ($this->_format === true)
                $this->_url .= '.:format';
            else
                $this->_url .= '(.:format)';
        }
    }
    
    private function _escape_url()
    {
        $this->_escaped_url = str_replace(array(
            '(',
            ')',
            '.',
            '/',
        ), array(
            '(?:',
            ')?',
            '\.',
            '\/'
        ), $this->_url);
        
        $repls = $subjs = array();
        
        foreach ($this->_vars as $name => $var) {
            if ($name == 'format')
                $repl = '([a-zA-Z0-9]{1,5})';
            elseif ($var['type'] == '*')
                $repl = '(.*?)';
            else
                $repl = '([^\/]+?)';
            $repls[] = $repl;
            $type    = $var['type'] == '*' ? '\*' : $var['type'];
            $subjs[] = '/' . $type . $name . '/';
        }
        
        $this->_escaped_url = preg_replace($subjs, $repls, $this->_escaped_url);
        if (!$this->_assets_route)
            $this->_escaped_url .= '\/?';
    }
    
    private function _find_vars($url_part = null)
    {
        $vars = array();
        if ($url_part === null) {
            $url_part = $this->_url;
        } else {
             $vars['part'] = $url_part;
            # Remove parentheses.
            $url_part = substr($url_part, 1, -1);
        }
        
        $parts    = $this->_extract_groups($url_part);
        $url_part = str_replace($parts, '%', $url_part);
        
        if (preg_match_all('/(\*|:)(\w+)/', $url_part, $ms)) {
            foreach ($ms[1] as $k => $type) {
                $var_name = $ms[2][$k];
                
                # Get constraint from properties.
                $constraint = isset($this->_constraints[$var_name]) ? $this->_constraints[$var_name] : null;
                
                # Get default from properties.
                $default = isset($this->_defaults[$var_name]) ? $this->_defaults[$var_name] : null;
                
                $this->_vars_names[] = $var_name;
                
                $this->_vars[$var_name] = array(
                    'type'        => $type,
                    'constraint'  => $constraint,
                    'default'     => $default,
                    'value'       => null,
                    'optional'    => false
                );
            }
            $vars[] = $ms[2];
        } else
            $vars[] = [];
        
        foreach ($parts as $subpart) {
            if ($c = $this->_find_vars($subpart))
                $vars[] = $c;
        }
        return $vars;
    }

    private function _extract_groups($str)
    {
        $parts = array();
        while ($part = $this->_extract_group($str)) {
            $str = substr($str, strpos($str, $part) + strlen($part));
            $parts[] = $part;
        }
        return $parts;
    }
    
    private function _extract_group($str)
    {
        if (false === ($pos = strpos($str, '(')))
            return;
        $group = '';
        $open = 1;
        while ($open > 0) {
            $str = substr($str, $pos+1);
            $close_pos = strpos($str, ')');
            $part = substr($str, 0, $close_pos+1);
            if (is_bool(strpos($part, '('))) {
                $pos = $close_pos;
                $group .= $part;
                $open--;
            } else {
                $pos = strpos($str, '(');
                $group .= substr($str, 0, $pos+1);
                $open++;
            }
        }
        return '(' . $group;
    }
    
    /**
     * Extracts var names and also replaces them in the regexp with their
     * corresponding constraints.
     */
    private function _extract_vars()
    {
        $this->_optional_groups = $this->_find_vars();
        $this->_constraints = [];
    }
    
    /**
     * Set index = action by default.
     */
    private function _set_default_vars_values()
    {
        $default_action = 'index';
        
        if (!$this->_assets_route) {
            # Action defaulted to "index" even thought _to said
            # otherwise.
            if ($this->_to) {
                try {
                    $token = new UrlToken($this->_to);
                    list($controller, $default_action) = $token->parts();
                } catch (Exception $e) {
                }
            }
        }
        
        if (!isset($this->_defaults['action']))
            $this->_defaults['action'] = $default_action;
        
        foreach ($this->_defaults as $var => $val) {
            if ($var == 'action') {
                if ($val == ':action') {
                    $val = 'index';
                }
                $this->_action = $val;
            } elseif ($var == 'controller')
                $this->_controller = $val;
            $this->_vars[$var]['value'] = $val;
        }
        
        if (!isset($this->_vars['format']['value']) || $this->_vars['format']['value'] === '')
            $this->_vars['format']['value'] = 'html';
        $this->_format = $this->_vars['format']['value'];
        
        $this->_defaults = null;
    }
    
    /**
     * Check if the controller actually exists after
     * matching the controller and action.
     */
    // private function _path_exists()
    // {
        // if ($this->rails_admin())
            // return true;
        // return is_file($this->controller_file());
    // }
    
    /**
     * Parses the "to" property, which is actually a UrlToken.
     * This isn't necessary to do if the route is like ':controller/:action'.
     */
    private function _parse_token()
    {
        if (!$this->_controller || !$this->_action) {
            if (!$this->_action && isset($this->_vars['action']))
                $this->_action = $this->_vars['action']['value'];
            
            if (!$this->_to) {
                if (!isset($this->_vars['controller']) || !isset($this->_vars['action']))
                    return false;
                $this->_controller = $this->_vars['controller']['value'];
                $this->_action     = $this->_vars['action']['value'];
            } else {
                $to = $this->_has_variable_to() ? $this->_replace_variable_to($this->_controller, $this->_action) : $this->_to;
                try {
                    $token = new UrlToken($to);
                    list($this->_controller, $this->_action) = $token->parts();
                } catch (Exception $e) {
                    return false;
                }
            }
        }
        return true;
    }
    
    private function _is_root()
    {
        return $this->_url === $this->_root_url();
    }
    
    private function _root_url()
    {
        return Mapper::ROOT_URL;
    }
    
    private function _replace_variable_to($controller, $action)
    {
        $parts = explode('#', $this->_to);
        empty($parts[1]) && $parts[1] = 'index';
        
        $part = is_int(strpos($parts[0], ':')) ? $parts[0] : $parts[1];
        
        preg_match('/:(\w+)/', $part, $m);
        $type = $m[1];
        return str_replace(':'.$type, $$type, $this->_to);
    }
    
    /**
     * Returns the variable part of the _to attribute.
     */
    private function _parse_variable_to()
    {
        $parts = explode('#', $this->_to);
        empty($parts[1]) && $parts[1] = 'index';
        
        $part = is_int(strpos($parts[0], ':')) ? $parts[0] : $parts[1];
        
        preg_match('/:(\w+)/', $part, $m);
        $type = $m[1];
        
        return $type;
    }
    
    private function _set_alias()
    {
        if (!$this->_as) {
            if ($this->_is_root())
                $this->_as = 'root';
            elseif ($this->rails_admin() && $this->_rails_panel) {
                $this->_as = 'rails_panel';
			}
            // elseif ($this->_controller && $this->_action)
                // $this->_as = (($namespace = $this->_namespaces) ? implode('_', $namespace) . '_' : '') . $this->_controller . '_' . $this->_action;
        }
    }
    
    private function _has_variable_to()
    {
        return is_int(strpos($this->_to, ':'));
    }
}