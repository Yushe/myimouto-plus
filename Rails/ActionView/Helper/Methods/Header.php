<?php
namespace Rails\ActionView\Helper\Methods;

use Rails;

trait Header
{
    public function stylesheetLinkTag($url, array $attrs = array())
    {
        empty($attrs['type']) && $attrs['type'] = 'text/css';
        empty($attrs['rel'])  && $attrs['rel']  = 'stylesheet';
        
        $assets_config = Rails::application()->config()->assets;
        
        # If assets are enabled and fails to find the wanted file, normal behaviour of
        # this method will be executed.
        if ($assets_config->enabled) {
            if (Rails::application()->config()->serve_static_assets) {
                if ($fileUrl = Rails::assets()->findCompiledFile($url . '.css')) {
                    // $attrs['href'] = Rails::assets()->prefix() . '/' . $file;
                    $attrs['href'] = $fileUrl;
                    return $this->tag('link', $attrs);
                }
            } else {
                $asset_file = $url . '.css';
                if ($assets_config->concat) {
                    if ($href = Rails::assets()->getFileUrl($asset_file )) {
                        $attrs['href'] = $href;
                        return $this->tag('link', $attrs);
                    }
                } elseif ($paths = Rails::assets()->getFileUrls($asset_file)) {
                    $tags = [];
                    foreach ($paths as $path) {
                        $attrs['href'] = $path;
                        $tags[] = $this->tag('link', $attrs);
                    }
                    return implode("\n", $tags);
                }
            }
        }
        
        $attrs['href'] = $this->_parse_url($url, '/stylesheets/', 'css');
        return $this->tag('link', $attrs);
    }
    
    public function javascriptIncludeTag($url, array $attrs = array())
    {
        empty($attrs['type']) && $attrs['type'] = 'text/javascript';
        
        $assets_config = Rails::application()->config()->assets;
        
        # If assets are enabled and fails to find the wanted file, normal behaviour of
        # this method will be executed.
        if ($assets_config->enabled) {
            if (Rails::application()->config()->serve_static_assets) {
                if ($fileUrl = Rails::assets()->findCompiledFile($url . '.js')) {
                    // $attrs['src'] = Rails::assets()->prefix() . '/' . $file;
                    $attrs['src'] = $fileUrl;
                    return $this->contentTag('script', '', $attrs);
                }
            } else {
                $asset_file = $url . '.js';
                if ($assets_config->concat) {
                    if ($src = Rails::assets()->getFileUrl($asset_file)) {
                        $attrs['src'] = $src;
                        return $this->contentTag('script', '', $attrs);
                    }
                } elseif ($paths = Rails::assets()->getFileUrls($asset_file)) {
                    $tags = [];
                    
                    foreach ($paths as $path) {
                        $attrs['src'] = $path;
                        $tags[] = $this->contentTag('script', '', $attrs);
                    }
                    return implode("\n", $tags);
                }
            }
        }
        
        $attrs['src'] = $this->_parse_url($url, '/javascripts/', 'js');
        return $this->contentTag('script', '', $attrs);
    }
    
    private function _parse_url($url, $default_base_url, $ext)
    {
        $base_path = Rails::application()->router()->basePath();
        
        if (strpos($url, '/') === 0) {
            $url = $base_path . $url;
        } elseif (strpos($url, 'http') !== 0 && strpos($url, 'www') !== 0) {
            $url = $base_path . $default_base_url . $url . '.' . $ext;
        }
        return $url;
    }
}