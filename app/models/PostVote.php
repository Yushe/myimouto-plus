<?php
class PostVote extends Rails\ActiveRecord\Base
{
    protected function associations()
    {
        return array(
            'belongs_to' => array(
                  'post',
                  'user'
            )
        );
    }
}