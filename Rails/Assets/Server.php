<?php
namespace Rails\Assets;

use Rails;

class Server
{
    public function dispatch_request()
    {
        $path = $this->request()->path();
        
        if ($base_path = $this->base_path())
            $path = str_replace($base_path, '', $path);
        
        $request = str_replace(Rails::assets()->prefix() . '/', '', $path);
        $this->serve_file($request);
    }
    
    public function serve_file($file_path)
    {
        $file = Rails::assets()->findFile($file_path);
        
        if (!$file) {
            $this->set_404_headers();
            return;
        }
        
        $ext = $file->type();
        $parser = null;
        
        switch ($ext) {
            case 'js':
                $parser = new Parser\Base($file);
                $this->set_javascript_headers();
                break;
            
            case 'css':
                $parser = new Parser\Base($file);
                $this->set_stylesheet_headers();
                break;
            
            case 'jpeg':
                $this->headers()->contentType('image/jpeg');
                break;
                
            case 'jpg':
                $this->headers()->contentType('image/jpeg');
                break;
                
            case 'png':
                $this->headers()->contentType('image/png');
                break;
                
            case 'gif':
                $this->headers()->contentType('image/gif');
                break;
                
            case 'svg':
                $this->headers()->contentType('image/svg+xml');
                break;
                
            case 'ttf':
                $this->headers()->contentType('application/x-font-ttf');
                break;
                
            case 'woff':
                $this->headers()->contentType('application/font-woff');
                break;
            
            default:
                $this->headers()->contentType('application/octet-stream');
                return;
        }
        
        if ($parser) {
            if ($this->params()->body) {
                $parseType = Parser\Base::PARSE_TYPE_NONE;
            } else {
                $parseType = Parser\Base::PARSE_TYPE_FULL;
            }
            
            $parser->parse($parseType);
            $file_contents = $parser->parsed_file();
        } else {
            $file_contents = file_get_contents($file->full_path());
        }
        
        $etag = md5($file_contents);
        $date = date('D, d M Y H:i:s e');
        $last_modified = Rails::cache()->fetch('assets.last_mod.' . $file->full_path(), function() use ($date){
            return $date;
        });
        
        if ($this->file_modified($etag, $last_modified)) {
            $this->headers()->add("Last-Modified", $last_modified);
            $this->headers()->add("Cache-Control", 'public, max-age=31536000');
            $this->headers()->add("ETag", $etag);
            Rails::application()->dispatcher()->response()->body($file_contents);
        } else {
            $this->headers()->status('HTTP/1.1 304 Not Modified');
            Rails::application()->dispatcher()->response()->body('');
        }
    }
    
    public function base_path()
    {
        return Rails::application()->router()->basePath();
    }
    
    public function request()
    {
        return Rails::application()->dispatcher()->request();
    }
    
    public function params()
    {
        return Rails::application()->dispatcher()->parameters();
    }
    
    public function headers()
    {
        return Rails::application()->dispatcher()->headers();
    }
    
    private function file_modified($etag, $last_modified)
    {
        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
        
        if ((($if_none_match && $if_none_match == $etag) || !$if_none_match) &&
            ($if_modified_since && $if_modified_since == $last_modified))
        {
            return false;
        } else {
            return true;
        }
    }
    
    private function set_404_headers()
    {
        $this->headers()->status(404);
    }
    
    private function set_javascript_headers()
    {
        $this->headers()->contentType('application/javascript');
    }
    
    private function set_stylesheet_headers()
    {
        $this->headers()->contentType('text/css');
    }
}