<?php

namespace MyImouto\Listeners;

use MyImouto\Post;
use MyImouto\Post\TagProcessor;

class PostModelEvents
{
    protected static $tagProcessor;

    public function subscribe($events)
    {
        $events->listen('post.creating', self::class . '@creating');
        $events->listen('post.created', self::class . '@created');
        $events->listen('post.saving', self::class . '@saving');
        $events->listen('post.saved', self::class . '@saved');
    }
    
    public function creating(Post $post)
    {
        $post->index_timestamp = gmdate('Y-m-d H:i:s');
        
        if (!$post->rating) {
            $post->rating = config('myimouto.conf.default_rating_upload');
        }
    }

    public function created($post)
    {
    }

    public function saving($post)
    {
        self::$tagProcessor = new TagProcessor($post);
        
        self::$tagProcessor->parseTags();
        
        self::$tagProcessor->applyPreMetaTags();
    }

    public function saved()
    {
        self::$tagProcessor->applyPostMetaTags();
        
        self::$tagProcessor->saveTags();
    }
}