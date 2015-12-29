<?php

namespace MyImouto\Post;

use DB;
use MyImouto\Post as PostModel;
use MyImouto\Tag;
use MyImouto\Pool;

class TagProcessor
{
    const POST_META_TAGS = [
        '-pool',
        'pool',
        'child',
        'fav',
        '-fav',
    ];
    
    const PRE_META_TAGS = [
        'rating',
        'parent',
        'source',
        'hold',
        'unhold',
        'show',
        'hide'
    ];
    
    const RATINGS = [
        's',
        'q',
        'e',
    ];
    
    protected $post;
    
    protected $preMetaTags = [];
    
    protected $postMetaTags = [];
    
    protected $normalizedTags = [];
    
    public function __construct(PostModel $post)
    {
        $this->post = $post;
    }
    
    protected function filterMetaTags(array $tags): array
    {
        $filtered = [];
        
        foreach ($tags as $tag) {
            // Rating shortcut.
            if (strlen($tag) == 1 && in_array($tag, self::RATINGS)) {
                $this->preMetaTags[] = [
                    'rating',
                    $tag
                ];
                
                continue;
            }
            
            $tagParts = explode(':', $tag, 3);
            
            $metaTag  = array_shift($tagParts);
            $param    = array_shift($tagParts);
            $subParam = array_shift($tagParts);
            
            if (in_array($metaTag, self::PRE_META_TAGS)) {
                $this->preMetaTags[] = [
                    $metaTag,
                    $param
                ];
            } elseif (in_array($metaTag, self::POST_META_TAGS)) {
                $postMetaTags[] = [
                    $metaTag,
                    $param,
                    $subParam
                ];
            } else {
                $filtered[] = $tag;
            }
        }
        
        return $filtered;
    }
    
    public function applyPreMetaTags()
    {
        foreach ($this->preMetaTags as $metaTag) {
            switch ($metaTag[0]) {
                case 'source':
                    $this->post->source = $metaTag[1];
                    
                    break;
                
                case 'hold':
                    $this->post->is_held = true;
                    
                    break;
                
                case 'unhold':
                    $this->post->is_held = false;
                    
                    break;
                
                case 'show':
                    $this->post->is_shown_in_index = true;
                    
                    break;
                
                case 'hide':
                    $this->post->is_shown_in_index = false;
                    
                    break;
                
                case 'rating':
                    $this->post->rating = $metaTag[1];
                    
                    break;
                
                case 'parent':
                    if (is_numeric($metaTag[1])) {
                        $this->post->parent_id = (int)$metaTag[1];
                    }
                    
                    break;
            }
        }
    }
    
    protected function removeNegatedTags(array $tags): array
    {
        $negated = [];
        
        foreach ($tags as $tag) {
            if (substr($tag, 0, 1) == '-') {
                $negated[] = substr($tag, 1);
            }
        }
        
        return array_diff($tags, $negated);
    }
    
    protected function addAutomaticTags($post, array $tags): array
    {
        if ($post->isGif()) {
            $tags[] = 'gif';
        }
        
        return $tags;
    }
    
    public function applyPostMetaTags()
    {
        // '-pool',
        // 'pool',
        // 'child',
        // 'fav',
        // '-fav',
        
        foreach ($this->preMetaTags as $metaTag) {
            switch($metatag) {
                case 'pool':
                    $name       = $metatag[1];
                    $sequence   = $metatag[2];
                    
                    if (ctype_digit($name)) {
                        $pool = Pool::where('id', '=', $name)->first();
                    } else {
                        $pool = Pool::where('name', '=', $name)->first();
                    }
                    
                    if (!$pool and !ctype_digit($name)) {
                        $pool = Pool::create([
                            'name' => $name,
                            'is_public' => false,
                            'user_id' => $post->user->id
                        ]);
                    }
                    
                    if (!$pool || !$pool->canChange($post->user, null)) {
                        continue;
                    }
                    
                    # Set ignoreAlreadyExists, so pool:1:2 can be used to
                    # change the sequence number
                    # of a post that already exists in the pool.
                    $options = [
                        'user' => User::where('id', '=', $post->user->id)->first(),
                        'ignoreAlreadyExists' => true
                    ];
                    
                    if ($sequence) {
                        $options['sequence'] = $sequence;
                    }
                        
                    try {
                        $pool->addPost($post->id, $options);
                    } catch(Pool_PostAlreadyExistsError $e) {
                    } catch (Pool_AccessDeniedError $e) {
                    }
                    
                    break;
                    
                case '-pool':
                    $name = $param;
                    $cmd = $subparam;

                    $pool = Pool::where(['name' => $name])->first();
                    if (!$pool->can_change(current_user(), null))
                        break;

                    if ($cmd == "parent") {
                        # If we have a parent, remove ourself from the pool and add our parent in
                        # our place.    If we have no parent, do nothing and leave us in the pool.
                        if (!empty($post->parent_id)) {
                            $pool->transfer_post_to_parent($post->id, $post->parent_id);
                            break;
                        }
                    }
                    $pool && $pool->remove_post($id);
                    break;
                
                // case 'child':
                    // unset($post->newTags[$k]);
                    
                    // break;
            }
        }
    }
    
    protected function normalizeTags($post): array
    {
        if ($post->tag_string) {
            $normalizedTags = Tag::scanTags($post->tag_string);
            $normalizedTags = $this->filterMetaTags($normalizedTags);
            $normalizedTags = $this->removeNegatedTags($normalizedTags);
        } else {
            $normalizedTags = [];
        }
        
        if (config('myimouto.conf.enable_automatic_tags')) {
            $normalizedTags = $this->addAutomaticTags($post, $normalizedTags);
        }
        
        if (!$normalizedTags) {
            $normalizedTags[] = config('myimouto.conf.default_tag');
        }
        
        sort($normalizedTags);        
        
        return $normalizedTags;
    }
    
    protected function mergeOldChanges($post)
    {
        if (!$post->oldTags) {
            return;
        }
        
        # If someone else committed changes to this post before we did,
        # then try to merge the tag changes together.
        $currentTags = explode(' ', $post->tag_string);
        
        $post->oldTags = explode(' ', $post->oldTags);
        
        $post->newTags =
            array_filter(
                array_merge(
                    array_diff(
                        array_merge($currentTags, $post->newTags),
                        $post->oldTags
                    ),
                    array_intersect($currentTags, $post->newTags)
                )
            );
    }
    
    public function saveTags()
    {
        $tagSet = [];
        
        foreach ($this->normalizedTags as $tag) {
            $tagSet[] = [
                'post_id' => $this->post->id,
                'tag_id'  => Tag::firstOrCreate(['name' => $tag])->id
            ];
        }
        
        DB::table('posts_tags')->where('post_id', $this->post->id)->delete();
        
        DB::table('posts_tags')->insert($tagSet);
    }
    
    public function parseTags()
    {
        $this->mergeOldChanges($this->post);
        
        $normalizedTags = $this->normalizeTags($this->post);
        
        // DB::table('posts_tags')->where('post_id', '=', $post->id)->delete();
        
        $this->post->tag_string = join(' ', array_unique($normalizedTags));
        
        $this->normalizedTags = $normalizedTags;
        
        return;
        //
        
        // $normalizedTags = $this->normalizeTags();
        // $preMetaTags = $this->filterPreMetaTags();
        // $postMetaTags = $this->filterPostMetaTags();
        
        if (!$post->exists || !$post->newTags) {
            return;
        }
        
        if (!$post->newTags) {
            if ($hadMetatags) {
                return;
            }
            
            $post->newTags[] = config('myimouto.conf.default_tag');
        }
        
        // $post->newTags = TagAlias::to_aliased($post->newTags);
        // $post->newTags = array_unique(TagImplication::with_implied($post->newTags));
        
        sort($post->newTags);
        
        # TODO: be more selective in deleting from the join table
        DB::table('posts_tags')->where('post_id', '=', $post->id)->delete();
        
        $newTagsIds   = [];
        $newTagsNames = [];
        
        $post->newTags = array_map(function ($newTag) use (&$newTagsIds, &$newTagsNames) {
            $tag = Tag::firstOrCreate(['name' => $newTag]);
            
            if (!in_array($tag->id, $newTagsIds)) {
                $newTagsIds[]   = $tag->id;
                $newTagsNames[] = $tag->name;
                
                return $tag;
            }
        }, $post->newTags);
        
        $post->newTags = array_filter($post->newTags);
        /*
        # If any tags are newly active, expire the tag cache.
        if ($post->newTags) {
            $anyNewTags = false;
            
            $previous_tags = explode(' ', $post->cached_tags);
            
            foreach ($post->newTags as $tag) {
                # If this tag is in old_tags, then it's already active and we just removed it
                # in the above DELETE, so it's not really a newly activated tag.    (This isn't
                # self.old_tags; that's the tags the user saw before he edited, not the data
                # we're replacing.)
                if ($tag->post_count == 0 && !in_array($tag->name, $previous_tags)) {
                    $anyNewTags = true;
                    
                    break;
                }
            }

            if ($anyNewTags) {
                Moebooru\CacheHelper::expire_tag_version();
            }
        }
        */
        
        sort($newTagsNames);
        
        $post->cached_tags = implode(' ', $newTagsNames);
        
        $tagSet = array_map(
            function($tag) use ($post) {
                return [
                    'post_id' => $post->id,
                    'tag_id'  => $tag->id
                ];
            },
            $post->newTags
        );
        
        DB::table('posts_tags')->insert($tagSet);
        
        # Store the old cached_tags, so we can expire them.
        $post->oldCachedTags = $post->cached_tags;
        
        $post->newTags = null;
    }
}
