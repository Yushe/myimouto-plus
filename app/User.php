<?php

namespace MyImouto;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;

    const LEVEL_BLOCKED     = 10;
    
    const LEVEL_MEMBER      = 20;
    
    const LEVEL_PRIVILEGED  = 30;
    
    const LEVEL_CONTRIBUTOR = 33;
    
    const LEVEL_JANITOR     = 35;
    
    const LEVEL_MODERATOR   = 40;
    
    const LEVEL_ADMIN       = 50;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
    
    // public function isBlocked()
    // {
        // return $this->level >= self:LEVEL_BLOCKED;
    // }
    
    // public function isMember()
    // {
        // return $this->level >= self:LEVEL_MEMBER;
    // }
    
    // public function isPrivileged()
    // {
        // return $this->level >= self:LEVEL_PRIVILEGED;
    // }
    
    // public function isMember()
    // {
        // return $this->level >= self:LEVEL_MEMBER;
    // }
    
    // public function isContributor()
    // {
        // return $this->level >= self:LEVEL_CONTRIBUTOR;
    // }
    
    // public function isJanitor()
    // {
        // return $this->level >= self:LEVEL_JANITOR;
    // }
    
    // public function isModerator()
    // {
        // return $this->level >= self:LEVEL_MODERATOR;
    // }
    
    // public function isAdmin()
    // {
        // return $this->level >= self:LEVEL_ADMIN;
    // }
}
