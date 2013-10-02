<?php
namespace Rails\ActionView\Helper\Methods;

trait JavaScript
{
    static private $JS_ESCAPE_MAP = [
        '\\'    => '\\\\',
        '</'    => '<\/',
        "\r\n"  => '\n',
        "\n"    => '\n',
        "\r"    => '\n',
        '"'     => '\\"',
        "'"     => "\\'"
    ];
    
    static private $JSON_ESCAPE = [
        '&' => '\u0026',
        '>' => '\u003E',
        '<' => '\u003C'
    ];
    
    public function linkToFunction($link, $function, array $attrs = array())
    {
        $attrs['href'] = '#';
        if ($function) {
            $function = trim($function);
            if (strpos($function, -1) != ';')
                $function .= ';';
            $function .= ' return false;';
        } else
            $function = 'return false;';
        $attrs['onclick'] = $function;
        
        return $this->contentTag('a', $link, $attrs);
    }
    
    public function buttonToFunction($name, $function, array $attrs = array())
    {
        $attrs['href'] = '#';
        $function = trim($function);
        if (strpos($function, -1) != ';')
            $function .= ';';
        $function .= ' return false;';
        $attrs['onclick'] = $function;
        $attrs['type'] = 'button';
        $attrs['value'] = $name;
        return $this->tag('input', $attrs);
    }
    
    public function j($str)
    {
        return $this->escapeJavascript($str);
    }
    
    public function escapeJavascript($str)
    {
        return str_replace(array_keys(self::$JS_ESCAPE_MAP), self::$JS_ESCAPE_MAP, $str);
    }
    
    /**
     * $this->javascriptTag('alert("foo")', ['defer' => 'defer']);
     * $this->javascriptTag(['defer' => 'defer'], function() { ... });
     * $this->javascriptTag(function() { ... });
     */
    public function javascriptTag($contentOrOptionWithBlock, $htmlOptions = null)
    {
        if ($contentOrOptionWithBlock instanceof \Closure) {
            ob_start();
            $contentOrOptionWithBlock();
            $contents = ob_get_clean();
            $htmlOptions = [];
        } elseif ($htmlOptions instanceof \Closure) {
            ob_start();
            $htmlOptions();
            $contents = ob_get_clean();
            $htmlOptions = $contentOrOptionWithBlock;
        } elseif (is_string($contentOrOptionWithBlock)) {
            $contents = $contentOrOptionWithBlock;
            if (!is_array($htmlOptions)) {
                $htmlOptions = [];
            }
        }
        
        return $this->contentTag('script', $contents, $htmlOptions);
    }
    
    public function jsonEscape($str)
    {
        return str_replace(array_keys(self::$JSON_ESCAPE), self::$JSON_ESCAPE, $str);
    }
}
