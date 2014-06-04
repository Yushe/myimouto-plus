<?php
class SimilarImages
{
    const SEARCH_CACHE_DIR = "/data/search";
    
    static public function get_services($services = null)
    {
        !$services && $services = "local";
        
        if ($services == "all") {
            $services = array_keys(CONFIG()->image_service_list);
        } else {
            $services = explode(',', $services);
        }

        foreach (array_keys($services) as $i) {
            if ($services[$i] == "local")
                $services[$i] = CONFIG()->local_image_service;
        }
        return $services;
    }

    static public function similar_images($options = [])
    {
        $errors = [];
        $local_service = CONFIG()->local_image_service;
        $services = $options['services'];
        $services_by_server = [];
        
        foreach ($services as $service) {
            if (!isset(CONFIG()->image_service_list[$service]) || !($server = CONFIG()->image_service_list[$service])) {
                $errors[] = ['services' => [$service], 'message' => $service . " is an unknown service"];
                continue;
            }
            if (!isset($services_by_server[$server]))
                $services_by_server[$server] = [];
            $services_by_server[$server][] = $service;
        }
        
        if (!$services_by_server)
            return ['posts' => new Rails\ActiveRecord\Collection(), 'posts_external' => new Rails\ActiveRecord\Collection(), 'similarity' => [], 'services' => [], 'errors' => 'No service selected/no local service'];
        
        # If the source is a local post, read the preview and send it with the request.
        if ($options['type'] == 'post') {
            $source_file = $options['source']->preview_path();
        } elseif ($options['type'] == 'file') {
            $source_file = $options['source'];
        }
        
        $server_threads = [];
        $server_responses = [];
        $curl_opts = [
            CURLOPT_TIMEOUT         => 5,
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true
        ];
        $mh = curl_multi_init();
        $chk = -1;
        
        foreach ($services_by_server as $services_list) {
            $chk++;
            
            $search_url = null;
            
            if ($options['type'] == 'url')
                $search_url = $options['source'];
            if ($options['type'] == 'post' && CONFIG()->image_service_local_searches_use_urls)
                $search_url = $options['source']['preview_url'];

            $params = [];
            if ($search_url) {
                $params['url'] = $search_url;
            } else {
                if (function_exists('curl_file_create')) { // PHP v5.5.* fix
                    $params['file'] = curl_file_create($source_file);
                } else {
                    $params['file'] = '@' . $source_file;
                }
            }
            
            foreach ($services_list as $k => $s)
                $params["service[$k]"] = $s;
            
            $chn = 'ch' . $chk;
            $$chn = curl_init($server);
            
            curl_setopt_array($$chn, $curl_opts);
            curl_setopt($$chn, CURLOPT_POSTFIELDS, $params);
            
            curl_setopt($$chn, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($$chn, CURLOPT_HTTPHEADER, ['Host: ' . parse_url($server)['host']]);
            
            curl_multi_add_handle($mh, $$chn);
        }
        $ch_count = $chk;
        
        $active = null;
        
        do {
            $ret = curl_multi_exec($mh, $active);
        } while ($ret == CURLM_CALL_MULTI_PERFORM);

        
        while ($active && $ret == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                usleep(100);
            }
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        
        $posts = new Rails\ActiveRecord\Collection();
        $posts_external = new Rails\ActiveRecord\Collection();
        $similarity = [];
        $preview_url = "";
        $next_id = 1;
        $server_list = array_keys($services_by_server);
        
        /**
         * Is there a class for PHP that can nicely handle XML?
         */
        $get_attr = function($xml, $attr) {
            $obj = $xml->attributes()->$attr;
            if ($obj) {
                $obj = (array)$obj;
                return $obj[0];
            }
            return null;
        };
        
        foreach(range(0, $ch_count) as $i) {
            $chn = 'ch' . $i;
            $server = $server_list[$i];
            
            $resp = curl_multi_getcontent($$chn);
            
            if (!$resp) {
                $curl_err = curl_error($$chn);
                if (preg_match('/^Operation timed out/', $curl_err)) {
                    $err_msg = 'timed out';
                    Rails::log()->notice(
                        "[SimilarImages] cURL timed out: " . $curl_err
                    );
                } else {
                    $err_msg = 'empty response';
                    Rails::log()->warning(sprintf(
                        "[SimilarImages] cURL error: (%s) %s", curl_errno($$chn), $curl_err
                    ));
                }
                $errors[$server] = [ 'message' => $err_msg ];
                continue;
            }
            
            try {
                $doc = new SimpleXMLElement($resp);
            } catch (Exception $e) {
                ob_start();
                var_dump(curl_getinfo($$chn));
                $info = ob_get_clean();
                Rails::log()->error("Similar Images Error\ncURL Error: " . curl_error($$chn) . "\ncURL Info:\n" . $info);
                
                Rails::log()->exception($e);
                $errors[$server] = [ 'message' => 'parse error' ];
                continue;
            }
            
            if ($doc->getName() == 'error') {
                $errors[$server] = [ 'message' => $doc->message ];
                continue;
            } elseif ($doc->getName() != 'matches') {
                $errors[$server] = [ 'message' => 'invalid response' ];
                continue;
            }
            
            $threshold = !empty($options['threshold']) ? $options['threshold'] : (float)$get_attr($doc, 'threshold');
            
            foreach ($doc->match as $element) {
                $sim = (float)$get_attr($element, 'sim');
                
                if ($sim >= $threshold and $sim > 0) {
                    $service = $get_attr($element, 'service');
                    $image = $element->post;
                    
                    $id = $get_attr($image, 'id');
                    $md5 = $get_attr($image, 'md5');
                    
                    if ($service == $local_service) {
                        $post = Post::where('id = ?', $id);
                        if ($post && is_object($options['source']) && $post->id != $options['source']->id) {
                            $posts[] = $post;
                            $similarity[spl_object_hash($post)] = $sim;
                        }
                    } elseif ($service) {
                        $post = new ExternalPost();
                        $post->id = (string)$next_id;
                        $next_id++;
                        $post->md5 = $md5;
                        $post->preview_url = $get_attr($element, 'preview');
                        if ($service == 'gelbooru.com') # hack
                            $post->url = "http://" . $service . "/index.php?page=post&s=view&id=" . $id;
                        elseif ($service == "e-shuushuu.net") # hack
                            $post->url = "http://" . $service . "/image/" . $id . "/";
                        else
                            $post->url = "http://" . $service . "/post/show/" . $id;
                        $post->sample_url = $get_attr($image, 'sample_url') ?: $post->url;
                        $post->service = $service;
                        $post->width = $get_attr($image, 'width');
                        $post->height = $get_attr($image, 'height');
                        $post->tags = $get_attr($image, 'tags') ?: '';
                        
                        if (empty($options['data_search']))
                            $post->rating = $get_attr($image, 'rating') ?: 's';
                        else 
                            $post->rating = $get_attr($image, 'rating') ?: false;
                        
                        # Extra attributes.
                        if (!empty($options['data_search'])) {
                            $post->original_preview_url = $get_attr($image, 'preview_url');
                            $post->id = $get_attr($image, 'id');
                            $post->author = $get_attr($image, 'author');
                            $post->created_at = $get_attr($image, 'created_at');
                            $post->creator_id = $get_attr($image, 'creator_id');
                            $post->file_size = $get_attr($image, 'file_size');
                            $post->file_url = $get_attr($image, 'file_url');
                            $post->score = $get_attr($image, 'score');
                            $post->source = $get_attr($image, 'source');
                            $post->icon_path = ExternalPost::get_service_icon($service);
                            if (preg_match('/\.png$/', $post->file_url))
                                $post->has_png = true;
                        }
                        
                        $posts_external[] = $post;
                        
                        $similarity[spl_object_hash($post)] = $sim;
                    }
                }
            }
        }
        
        $posts->sort(function($a, $b) {
            $aid = spl_object_hash($a);
            $bid = spl_object_hash($b);
            if ($similarity[$aid] == $similarity[$bid])
                return 0;
            elseif ($similarity[$aid] > $similarity[$bid])
                return 1;
            return -1;
        });
        
        foreach ($errors as $server => $error) {
            if (empty($error['services']))
                $error['services'] = !empty($services_by_server[$server]) ? $services_by_server[$server] : $server;
        }
        $ret = ['posts' => $posts, 'posts_external' => $posts_external, 'similarity' => $similarity, 'services' => $services, 'errors' => $errors];
        if ($options['type'] == 'post') {
            $ret['source'] = $options['source'];
            $ret['similarity'][spl_object_hash($options['source'])] = 'Original';
            $ret['search_id'] = $ret['source']->id;
        } else {
            $post = new ExternalPost();
            # $post->md5 = $md5;
            $post->preview_url = $options['source_thumb'];
            if (!empty($options['full_url']))
                $post->url = $options['full_url'];
            elseif (!empty($options['url']))
                $post->url = $options['url'];
            elseif (!empty($options['source_thumb']))
                $post->url =  $options['source_thumb'];
            $post->id = 'source';
            $post->rating = 'q';
            $ret['search_id'] = 'source';
            
            # Don't include the source URL if it's a data: url; it can be very large and isn't useful.
            if (substr($post->url, 0, 5) == "data:")
                $post->url = "";

            list ($source_width, $source_height) = getimagesize($source_file);

            # Since we lose access to the original image when we redirect to a saved search,
            # the original dimensions can be passed as parameters so we can still display
            # the original size.    This can also be used by user scripts to include the
            # size of the real image when a thumbnail is passed.
            $post->width = !empty($options['width']) ? $options['width'] : $source_width;
            $post->height = !empty($options['height']) ? $options['height'] : $source_height;

            $ret['external_source'] = $post;
            $ret['similarity'][spl_object_hash($post)] = "Original";
        }

        return $ret;
    }

    # Save a file locally to be searched for.    Returns the path to the saved file, and
    # the search ID which can be passed to find_saved_search.
    #
    # MyImouto: this method receives the file contents, not a path to a file.
    static public function save_search($file_contents)
    {
        $tempfile_path_resize = $tempfile_path = $file_path = null;
        try {
            if (!is_dir(self::search_cache_dir()))
                mkdir(self::search_cache_dir());

            while (true) {
                $tempfile_path = self::search_cache_dir() . "/" . uniqid('', true) . ".upload";
                if (!is_file($tempfile_path))
                    break;
            }
            $fh = fopen($tempfile_path, 'a');
            fclose($fh);
            
            file_put_contents($tempfile_path, $file_contents);
            
            # Use the resizer to validate the file and convert it to a thumbnail-size JPEG.
            $imgsize = getimagesize($tempfile_path);
            
            $exts = [
                false,
                'gif',
                'jpg',
                'png',
                'swf',
                'psd',
                'bmp',
                'tiff',
                'tiff',
                'jpc',
                'jp2',
                'jpx',
                'jb2',
                'swc',
                'iff',
                'wbmp',
                'xbm'
            ];
            
            if (!$imgsize || !$imgsize[2] || !isset($exts[$imgsize[2]])) {
                throw new Moebooru\Exception\ResizeErrorException("Unrecognized image format");
            }

            $ret = [];
            $ret['original_width'] = $imgsize[0];
            $ret['original_height'] = $imgsize[1];
            $size = Moebooru\Resizer::reduce_to(['width' => $ret['original_width'], 'height' => $ret['original_height']], ['width' => 150, 'height' => 150]);
            $ext = $exts[$imgsize[2]];

            $tempfile_path_resize = $tempfile_path . ".2";
            Moebooru\Resizer::resize($ext, $tempfile_path, $tempfile_path_resize, $size, 95);
            rename($tempfile_path_resize, $tempfile_path);
            
            $md5 = md5_file($tempfile_path);
            $id = $md5 . "." . $ext;
            $file_path = self::search_cache_dir() . "/" . $id;

            rename($tempfile_path, $file_path);
            
            # Finally block
            if (is_dir($tempfile_path))
                rmdir($tempfile_path);
            if (is_file($tempfile_path_resize))
                rmdir($tempfile_path_resize);
            
            // chmod($file_path, 0664);
        } catch (Exception $e) {
            # Finally block
            if (is_dir($tempfile_path))
                rmdir($tempfile_path);
            if (is_file($tempfile_path_resize))
                rmdir($tempfile_path_resize);
            
            if (is_dir($file_path))
                rmdir($file_path);
            throw $e;
        }
        /*
        TODO:
        finally {
            if (is_dir($tempfile_path))
                rmdir($tempfile_path);
            if (is_file($tempfile_path_resize))
                rmdir($tempfile_path_resize);
        }
        */
        $ret['file_path'] = $file_path;
        $ret['search_id'] = $id;
        return $ret;
    }

    static public function valid_saved_search($id)
    {
        return (bool)preg_match('/\A[a-zA-Z0-9]{32}\.[a-z]+\Z/', $id);
    }

    # Find a saved file.
    static public function find_saved_search($id)
    {
        if (!self::valid_saved_search($id))
            return;

        $file_path = self::search_cache_dir() . "/" . $id;
        if (!is_file($file_path))
            return;

        # Touch the file to delay its deletion.
        fopen($file_path, 'a');
        return $file_path;
    }

    # Delete old searches.
    static public function cull_old_searches()
    {
        $dh = opendir(self::search_cache_dir());
        
        while (false !== ($path = readdir($dh))) {
            if ($path == '.' || $path == '..' || !self::valid_saved_search($path))
                continue;
            $file = self::search_cache_dir() . '/' . $path;
            $mtime = Rails\Toolbox\FileTools::modTime($file);
            $age = time() - $mtime;
            if ($age > 60*60*24)
                unlink($file);
        }
        
        closedir($dh);
    }
    
    static public function search_cache_dir()
    {
        return Rails::publicPath() . self::SEARCH_CACHE_DIR;
    }
}
