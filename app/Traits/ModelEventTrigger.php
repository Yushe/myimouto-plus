<?php

namespace MyImouto\Traits;

use Illuminate\Support\Facades\Event;
use Exception;

/**
 * Must implement $triggerEvents static property
 * with names of the events.
 */
trait ModelEventTrigger
{
    protected static function bootModelEventTrigger()
    {
        $className = strtolower(substr(static::class, strrpos(static::class, '\\') + 1));
        
        foreach (static::$triggerEvents as $eventName) {
            static::$eventName(function ($model) use ($eventName, $className) {
                Event::fire($className . '.' . $eventName, $model);
            });
        }
    }
} 