<?php

namespace MyImouto\Post;

class Query
{
    /**
     * Ranged conditions. They're arrays of the form:
     * [ 'gt', $value ]
     */
    public $id = [];
    
    public $mPixels = [];
    
    public $width = [];
    
    public $height = [];
    
    public $score = [];
    
    public $date = [];
    
    /**
     * A couple special cases
     */
    public $order;
    
    public $tags = [
        'include'   => [],
        'related'   => [],
        'exclude'   => [],
        'wildcards' => []
    ];
    
    /**
     * Strings
     */
    public $md5;
    
    public $fileExt;
    
    public $source;
    
    public $userFavorites;
    
    public $userName;
    
    public $excludePoolNames;
    
    public $poolName;
    
    public $rating;
    
    public $excludeRating;
    
    public $status;
    
    public $holds;
    
    /**
     * Integers
     */
    public $parentId;
    
    public $userId;
    
    public $excludePoolIds;
    
    public $poolId;
    
    public $limit;
    
    public $tagCount = 0;
    
    /**
     * Booleans
     */
    public $showDeleted;
    
    public $noParent;
    
    public $ratingLocked;
    
    public $noteLocked;
    
    public $statusLocked;
    
    public $shownInIndex;
}
