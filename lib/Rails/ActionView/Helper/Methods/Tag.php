<?php
namespace Rails\ActionView\Helper\Methods;

trait Tag
{
    public function tag($name, array $options = array(), $open = false, $escape = false)
    {
        return '<' . $name . ' ' . $this->_options($options, $escape) . ($open ? '>' : ' />');
    }
    
    /**
     * $content could be a Closure that can either return a string or
     * echo the contents itself.
     */
    public function contentTag($name, $content, array $options = array(), $escape = false)
    {
        if ($content instanceof \Closure) {
            ob_start();
            $tmpContent = $content();
            $content = ob_get_clean();
            if ($tmpContent !== null) {
                $content = $tmpContent;
                unset($tmpContent);
            }
        }
        return $this->_content_tag_string($name, $content, $options, $escape);
    }
    
    protected function _options(array $options = array(), $escape = false)
    {
        $opts = array();
        if (isset($options['allow_blank_attrs'])) {
            $allow_blank_attrs = true;
            unset($options['allow_blank_attrs']);
        } else
            $allow_blank_attrs = false;
        
        foreach ($options as $opt => $val) {
            # "class" attribute allows array.
            if ($opt == 'class' && is_array($val)) {
                $val = implode(' ', \Rails\Toolbox\ArrayTools::flatten($val));
            }
            
            if (is_array($val))
                $val = implode(' ', $val);
            
            if ((string)$val === '' && !$allow_blank_attrs)
                continue;
            
            if (is_int($opt))
                $opts[] = $val;
            else {
                $escape && $val = htmlentities($val);
                $opts[] = $opt . '="' . $val . '"';
            }
        }
        return implode(' ', $opts);
    }
    
    protected function _parse_size(&$attrs)
    {
        if (is_int(strpos($attrs['size'], 'x'))) {
            list ($attrs['width'], $attrs['height']) = explode('x', $attrs['size']);
            unset($attrs['size']);
        }
    }
    
    private function _content_tag_string($name, $content, array $options, $escape = false)
    {
        return '<' . $name . ' ' . $this->_options($options) . '>' . ($escape ? $this->h($content) : $content) . '</' . $name . '>';
    }
}
