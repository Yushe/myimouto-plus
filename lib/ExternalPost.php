<?php
class ExternalPost
{
    # These mimic the equivalent attributes in Post directly.
    public $md5, $url, $preview_url, $sample_url, $width, $height, $tags, $rating, $id, $similarity, $parent_id, $has_children;
    
    public $author, $created_at, $creator_id, $file_size, $file_url, $score, $source, $original_preview_url;
    
    private $_parsed_cached_tags;

    static public function get_service_icon($service)
    {
        if ($service == CONFIG()->local_image_service)
            $url = "/favicon.ico";
        elseif ($service == "gelbooru.com") # hack
            $url = "/favicon-" . $service . ".png";
        else
            $url = "/favicon-" . $service . ".ico";
        return $url;
    }
    
    public function __get($prop)
    {
        if ($prop == 'parsed_cached_tags') {
            if ($this->_parsed_cached_tags === null)
                $this->_parsed_cached_tags = explode(' ', $this->tags);
            return $this->_parsed_cached_tags;
        }
    }
    
    public function service_icon()
    {
        return ExternalPost::get_service_icon($this->service);
    }
    
    public function ext()
    {
        return true;
    }
    
    public function cached_tags()
    {
        return $this->tags;
    }
    
    public function tags()
    {
        return explode(' ', $this->tags);
    }

    public function to_xml(array $options = [])
    {
        $attrs = ['md5' => $this->md5, 'url' => $this->url, 'preview_url' => $this->preview_url, 'service' => $this->service];
        $params = ['root' => "external-post"];
        $xml = new Rails_Xml($attrs, $params);
        return $xml->output();
    }

    public function preview_dimensions()
    {
        $dim = Moebooru\Resizer::reduce_to(['width' => $this->width, 'height' => $this->height], ['width' => 150, 'height' => 150]);
        return [$dim['width'], $dim['height']];
    }

    public function use_jpeg($user)
    {
        return false;
    }
    
    public function has_jpeg()
    {
        return false;
    }

    public function is_flagged()
    {
        return false;
    }
    
    public function has_children()
    {
        return false;
    }
    
    public function is_pending()
    {
        return false;
    }
    
    public function parent_id()
    {
        return null;
    }

    public function preview_url()
    {
        return $this->preview_url;
    }
    
    # For external posts, we only link to the page containing the image, not directly
    # to the image itself, so url and file_url are the same.
    public function file_url()
    {
        return $this->url;
    }
    
    public function service()
    {
    }
}