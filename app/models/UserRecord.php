<?php
class UserRecord extends Rails\ActiveRecord\Base
{
    protected function associations()
    {
        return [
            'belongs_to' => [
                'user',
                'reporter' => ['foreign_key' => "reported_by", 'class_name' => "User"]
            ]
        ];
    }
    
    protected function validations()
    {
        return [
            'user_id'     => ['presence' => true],
            'reported_by' => ['presence' => true]
        ];
    }
    
    protected function setUser($name)
    {
        $this->user_id = ($user = User::where(['name' => $name])->first()) ? $user->id : null;
    }
}