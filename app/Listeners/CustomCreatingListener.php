<?php

namespace MyImouto\Listeners;

class CustomCreatingListener
{
    public function handle($post)
    {
        var_dump("OMG POST!", get_class($post));
        // dd($ev);
    }
}
