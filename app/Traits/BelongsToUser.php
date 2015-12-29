<?php

namespace MyImouto\Traits;

trait BelongsToUser
{
    public function user()
    {
        return $this->belongsTo('MyImouto\User');
    }   
}
