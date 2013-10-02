<?php
namespace Rails\ActionView\Helper\Methods;

use Rails;

trait Html
{
    private $_form_attrs;
    
    public function linkTo($link, $url_params, array $attrs = array())
    {
        $url_to = $this->parseUrlParams($url_params);
        $onclick = '';
        
        if (isset($attrs['method'])) {
            $onclick = "var f = document.createElement('form'); f.style.display = 'none'; this.parentNode.appendChild(f); f.method = 'post';";
            
            if ($attrs['method'] != 'post') {
                $onclick .= "var m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = '".$attrs['method']."'; f.appendChild(m);";
            }
            
            $onclick .= "f.action = this.href;f.submit();return false;";
            // $attrs['data-method'] = $attrs['method'];
            unset($attrs['method']);
        }
        
        if (isset($attrs['confirm'])) {
            if (!$onclick)
                $onclick = "if (!confirm('".$attrs['confirm']."')) return false;";
            else
                $onclick = 'if (confirm(\''.$attrs['confirm'].'\')) {'.$onclick.'}; return false;';
            unset($attrs['confirm']);
        }
        
        if ($onclick)
            $attrs['onclick'] = $onclick;
        
        $attrs['href'] = $url_to;
        
        return $this->contentTag('a', $link, $attrs);
    }
    
    public function linkToIf($condition, $link, $url_params, array $attrs = array())
    {
        if ($condition)
            return $this->linkTo($link, $url_params, $attrs);
        else
            return $link;
    }
    
    public function autoDiscoveryLinkTag($type = 'rss', $url_params = null, array $attrs = array())
    {
        if (!$url_params) {
            $url_params = Rails::application()->dispatcher()->router()->route()->controller . '#' .
                          Rails::application()->dispatcher()->router()->route()->action;
        }
        $attrs['href'] = $this->parseUrlParams($url_params);
        
        empty($attrs['type'])  && $attrs['type']  = 'application/' . strtolower($type) . '+xml';
        empty($attrs['title']) && $attrs['title'] = strtoupper($type);
        
        return $this->tag('link', $attrs);
    }
    
    public function imageTag($source, array $attrs = array())
    {
        $source = $this->assetPath($source);
        
        if (!isset($attrs['alt']))
            $attrs['alt'] = $this->humanize(pathinfo($source, PATHINFO_FILENAME));
        if (isset($attrs['size']))
            $this->_parse_size($attrs);
        $attrs['src'] = $source;
        return $this->tag('img', $attrs);
    }
    
    public function mailTo($address, $name = null, array $options = array())
    {
        if ($name === null) {
            $name = $address;
            if (isset($options['replace_at']))
                $name = str_replace('@', $options['replace_at'], $address);
            if (isset($options['replace_dot']))
                $name = str_replace('.', $options['replace_dot'], $address);
        }
        $encode = isset($options['encode']) ? $options['encode'] : false;
        
        if ($encode == 'hex') {
            $address = $this->hexEncode($address . '.');
            $address = str_replace(['%40', '%2e'], ['@', '.'], $address);
        }
        
        $address_options = array('subject', 'body', 'cc', 'bcc');
        
        $query = array_intersect_key($options, array_fill_keys($address_options, null));
        if ($query)
            $query = '?' . http_build_query($query);
        else
            $query = '';
        
        $address .= $query;
        
        $attrs = array_diff_key($options, $address_options, array_fill_keys(array('replace_at', 'replace_dot', 'encode'), null));
        $attrs['href'] = 'mailto:' . $address;
        
        $tag = $this->contentTag('a', $name, $attrs);
        
        if ($encode = 'javascript') {
            $tag = "document.write('" . $tag . "');";
            return $this->javascriptTag('eval(decodeURIComponent(\'' . $this->hexEncode($tag) . '\'))');
        } else
            return $tag;
    }
}