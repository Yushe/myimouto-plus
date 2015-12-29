<?php

namespace MyImouto;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    const META_TAGS = [
        'unlocked', 'deleted', 'ext', 'user',
        'sub', 'vote', '-vote', 'fav', 'md5',
        '-rating', 'rating', 'width', 'height',
        'mpixels', 'score', 'source', 'id', 'date',
        'pool', '-pool', 'parent', 'order', 'change',
        'holds', 'pending', 'shown', 'limit'
    ];
    
    public $fillable = ['name'];
    
    public $timestamps = false;
    
    public static function compactTags($tags, $maxLen)
    {
        if (strlen($tags) < $maxLen) {
            return $tags;
        }

        $splitTags = explode(' ', $tags);

        # Put long tags first, so we don't remove every tag because of one very long one.
        usort($splitTags, function($a, $b) {
            $aLen = strlen($a);
            $bLen = strlen($b);
            
            if ($aLen == $bLen) {
                return 0;
            }
            return $aLen > $bLen ? 1 : -1;
        });

        # Tag types that we're allowed to remove:
        $length = strlen($tags);
        
        foreach ($splitTags as $i => $tag) {
            $length -= strlen($tag) + 1;
            
            if ($length <= $maxLen) {
                break;
            }
        }
        
        sort($splitTags);
        return trim(implode(' ', $splitTags));
    }
    
    /**
     * @param string $tags
     */
    public static function scanTags(string $tags): array
    {
        // Exploding an empty string will result in an array with
        // an empty string as element.
        return array_unique(array_filter(explode(' ', str_replace(['%', ','], '', strtolower($tags)))));
    }
}
