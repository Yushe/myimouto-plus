<?php

namespace MyImouto\Logical;

use MyImouto\Post\Query;
use MyImouto\Post;
use MyImouto\Tag;

class TagQueryParser
{
    protected $metaTagsRegexp;
    
    public function __construct()
    {
        $this->metaTagsRegexp = '/\A(' . implode('|', Tag::META_TAGS) . '):(.+)\Z/i';
    }
    
    public function scanQuery(string $query): array
    {
        return array_unique( array_filter( explode(' ', strtolower($query)) ) );
    }
    
    public function parse(string $query): Query
    {
        $postQuery = new Query();
        
        foreach ($this->scanQuery($query) as $token) {
            if ($this->parseRating($token, $postQuery)) {
                continue;
            }
            
            if ($this->parseMetaTag($token, $postQuery)) {
                continue;
            }
            
            $this->parseTag($token, $postQuery);
        }
        
        return $postQuery;
    }
    
    protected function parseTag(string $tag, Query $postQuery)
    {
        $tag = strtolower($tag);
        
        if ($tag[0] == '-' && strlen($tag) > 1) {
            $postQuery->tags['exclude'][] = substr($tag, 1);
        } elseif ($tag[0] == '~' && strlen($tag) > 1) {
            $postQuery->tags['include'][] = substr($tag, 1);
        } elseif (is_int(strpos($tag, '*'))) {
            $postQuery->tags['wildcards'][] = $tag;
        } else {
            $postQuery->tags['related'][] = $tag;
        }
    }
    
    protected function parseRating(string $token, Query $query): bool
    {
        if (in_array($token, Post::RATINGS)) {
            $query->rating = $token;
            
            return true;
        }
        
        return false;
    }
    
    protected function parseRange($range, $type = 'int')
    {
        if (preg_match('/^(.+?)\.\.(.+)/', $range, $m)) {
            return [ 'between', $this->castType($m[1], $type), $this->castType($m[2], $type) ];
            
        } elseif (preg_match('/^<=(.+)|^\.\.(.+)/', $range, $m)) {
            return [ 'lte', $this->castType($m[1], $type) ];
            
        } elseif (preg_match('/^<(.+)/', $range, $m)) {
            return [ 'lt', $this->castType($m[1], $type) ];
            
        } elseif (preg_match('/^>=(.+)|^(.+)\.\.$/', $range, $m)) {
            return [ 'gte', $this->castType($m[1], $type) ];
            
        } elseif (preg_match('/^>(.+)/', $range, $m)) {
            return [ 'gt', $this->castType($m[1], $type) ];
            
        } elseif (is_int(strpos($range, ','))) {
            $items = explode(',', $range);
            
            foreach ($items as &$value) {
                $value = $this->castType($value, $type);
            }
            
            return [ 'in', $items ];
        } else {
            return [ 'eq', $this->castType($range, $type) ];
        }
    }
    
    protected function castType($var, $type)
    {
        if ($type == 'int') {
            return (int)$var;
        } elseif ($type == 'float') {
            return (float)$var;
        } elseif ($type == 'date') {
            try {
                return new DateTime($var);
            } catch (Exception $e) {
                return null;
            }
        }
    }
    
    protected function parseMetaTag(string $token, Query $query): bool
    {
        preg_match($this->metaTagsRegexp, $token, $m);
        
        if (!$m) {
            return false;
        }
        
        $metaTag = $m[1];
        $value   = $m[2];
        
        switch ($metaTag) {
            case 'user':
                if ((int)$value > 0) {
                    $query->userId = (int)$value;
                } else {
                    $query->userName = $value;
                }
                
                break;
            
            case 'rating':
                $query->rating = $value;
                
                break;
            
            case 'limit':
                $query->limit = $value;
                
                break;
            
            case 'width':
                $query->width = $this->parseRange($value);
                
                break;
            
            case 'height':
                $query->height = $this->parseRange($value);
                
                break;
            
            case 'id':
                $query->id = $this->parseRange($value);
                
                break;
            
            case 'score':
                $query->score = $this->parseRange($value);
                
                break;
            
            case 'mpixels':
                $query->mPixels = $this->parseRange($value, 'float');
                
                break;
        }
        
        return true;
    }
}
