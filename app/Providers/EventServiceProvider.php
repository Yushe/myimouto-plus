<?php

namespace MyImouto\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        // This is how you would create a custom listener to
        // post.creating event.
        // 'post.creating' => [
            // 'MyImouto\Listeners\CustomCreatingListener',
        // ]
    ];
    
    protected $subscribe = [
        'MyImouto\Listeners\PostModelEvents'
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
