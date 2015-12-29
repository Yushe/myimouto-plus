<?php

namespace MyImouto\Policies;

use MyImouto\Post;
use MyImouto\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostsPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
    }
    
    public function uploadLimit(User $user)
    {
        if (
            config('myimouto.conf.upload_limit')['max'] &&
            $user->level <= config('myimouto.conf.upload_limit')['user_level']
        ) {
            $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));
            
            $postCount = Post::where('user_id', '=', $user->id)
                ->where('created_at', '>', $oneDayAgo)
                ->count();
            
            if ($postCount >= config('myimouto.conf.upload_limit')['max']) {
                return false;
            }
        }
        
        return true;
    }
}
