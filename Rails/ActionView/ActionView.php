<?php
namespace Rails\ActionView;

use Closure;
use ReflectionClass;
use Rails;

abstract class ActionView
{
    /**
     * Stores the contents for.
     * Static because contents_for are available
     * for everything.
     */
    private static $_content_for = array();
    
    /**
     * Stores the names of the active contentFor's.
     */
    private static $_content_for_names = array();
    
    protected $_buffer;
    
    static public function clean_buffers()
    {
        if ($status = ob_get_status()) {
            foreach (range(0, $status['level']) as $lvl)
                ob_end_clean();
        }
    }
    
    /**
     * Creates content for $name by calling $block().
     * If no $block is passed, it's checked if content for $name
     * exists.
     */
    public function contentFor($name, Closure $block = null, $prefix = false)
    {
        if (!$block) {
            return isset(self::$_content_for[$name]);
        }
        
        if (!isset(self::$_content_for[$name]))
            self::$_content_for[$name] = '';
        
        ob_start();
        $block();
        $this->_add_content_for($name, ob_get_clean(), $prefix);
    }
    
    public function provide($name, $content)
    {
        $this->_add_content_for($name, $content, false);
    }
    
    public function clear_content_for($name)
    {
        unset(self::$_content_for[$name]);
    }
    
    /**
     * Yield seems to be a reserved keyword in PHP 5.5.
     * Forced to change the name of the yield method to "content".
     */
    public function content($name = null)
    {
        if ($name && isset(self::$_content_for[$name]))
            return self::$_content_for[$name];
    }
    
    /**
     * Passing a closure to do_content_for() will cause it
     * to do the same as end_content_for(): add the buffered
     * content. Thus, this method.
     *
     * @param string $name content's name
     * @param string $value content's body
     * @param bool $prefix to prefix or not the value to the current value
     */
    private function _add_content_for($name, $value, $prefix)
    {
        !array_key_exists($name, self::$_content_for) && self::$_content_for[$name] = '';
        if ($prefix)
            self::$_content_for[$name] = $value . self::$_content_for[$name];
        else
            self::$_content_for[$name] .= $value;
    }
}