<?php

namespace MyImouto\Providers;

use MyImouto\Post;
use MyImouto\Policies;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Post::class => Policies\PostsPolicy::class,
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        /* Since uploadLimit policy only needs the $user argument, Laravel won't
         * recognize it as a Policy, so we need to register it as an Ability.
         */
        $gate->define('upload-limit', Policies\PostsPolicy::class . '@uploadLimit');
    }
}
