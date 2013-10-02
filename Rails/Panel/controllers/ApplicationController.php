<?php
class ApplicationController extends Rails\ActionController\Base
{
    final protected function filters()
    {
        return [
            'before' => [
                'isUserAllowed'
            ]
        ];
    }
    
    final protected function isUserAllowed()
    {
        if (!Rails::application()->validateSafeIps()) {
            $this->render(['action' => 'forbidden'], ['status' => 403]);
        }
    }
}