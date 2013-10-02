<?php
namespace Rails\ActiveRecord\Base\Methods;

use Rails\ActiveRecord\Relation;

trait ScopingMethods
{
    /**
     * Checks if scope exists. If it does, executes it and
     * returns the relation; otherwise, returns false.
     */
    static public function scope($name, array $params)
    {
        $cn = self::cn();
        self::$preventInit = true;
        $scopes = (new $cn())->scopes();
        
        if (isset($scopes[$name])) {
            $lambda = $scopes[$name];
            $relation = new Relation($cn, static::tableName());
            
            $lambda = $lambda->bindTo($relation);
            call_user_func_array($lambda, $params);
            return $relation;
        } else
            return false;
    }
    
    /**
     * Defines scopes.
     *
     * Eg:
     *
        class Book extends ActiveRecord\Base
        {
            static protected function scopes()
            {
                return [
                    'fantasy' => function() {
                        $this->where(['genre' => 'fantasy']);
                    },
                    'available' => function() {
                        $this->where(['status' => 'available']);
                    },
                    'author' => function($author_name, $limit = 0) {
                        $this->where('author_name = ?', $author_name);
                        if ($limit)
                            $this->limit($limit);
                    }
                ];
            }
        }
     * The anonymous function is binded to an ActiveRecord\Relation object.
     *
     * Not supporting default scope for now.
     * Not supporting static methods as scopes, as they couldn't
     * be called from a model instance.
     */
    protected function scopes()
    {
        return [];
    }
}