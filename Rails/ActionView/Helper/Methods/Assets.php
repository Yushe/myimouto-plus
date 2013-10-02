<?php
namespace Rails\ActionView\Helper\Methods;

trait Assets
{
    public function assetPath($source, array $options = [])
    {
        if (strpos($source, '/') !== 0 && strpos($source, 'http') !== 0) {
            if (!isset($options['digest'])) {
                $options['digest'] = true;
            }
            
            if (\Rails::config()->assets->enabled) {
                if (\Rails::config()->serve_static_assets && $options['digest']) {
                    if ($url = \Rails::assets()->findCompiledFile($source)) {
                        return $url;
                    }
                }
                
                if ($file = \Rails::assets()->findFile($source)) {
                    return $file->url();
                }
            }
            
            return \Rails::application()->router()->rootPath() . $source;
        } else {
            return $source;
        }
    }
}
