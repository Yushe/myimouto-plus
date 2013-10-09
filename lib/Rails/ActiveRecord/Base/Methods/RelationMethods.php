<?php
namespace Rails\ActiveRecord\Base\Methods;

use PDO;
use Rails;
use Rails\ActiveRecord\Relation;
use Rails\ActiveRecord\Relation\AbstractRelation;
use Rails\ActiveRecord\Relation\Association;

trait RelationMethods
{
    /**
     * Find a model by id.
     * @return Rails\ActiveRecord\Base
     * @raises ActiveRecord\RecordNotFound
     */
    static public function find($id)
    {
        $id = (int)$id;
        if ($model = self::where(['id' => $id])->first())
            return $model;
        throw new Rails\ActiveRecord\Exception\RecordNotFoundException(
            sprintf("Couldn't find %s with id = %d.", self::cn(), $id)
        );
    }
    
    /**
     * "Instantiator" methods {
     */
    static public function from()
    {
        return self::createRelation('select', func_get_args());
    }
    
    static public function group()
    {
        return self::createRelation('select', func_get_args());
    }
    
    static public function having()
    {
        return self::createRelation('having', func_get_args());
    }
    
    static public function joins()
    {
        return self::createRelation('joins', func_get_args());
    }
    
    static public function limit()
    {
        return self::createRelation('limit', func_get_args());
    }

    # TODO
    static public function unscoped()
    {
        return new Relation(get_called_class(), static::tableName());
    }
    
    /**
     * This is the correct method to instantiate an empty relation.
     */
    static public function none()
    {
        // $cn = self::cn();
        // return new Relation($cn, static::tableName());
        return self::createRelation();
    }
    
    static public function offset()
    {
        return self::createRelation('offset', func_get_args());
    }
    
    static public function order()
    {
        return self::createRelation('order', func_get_args());
    }
    
    static public function select()
    {
        return self::createRelation('select', func_get_args());
    }
    
    static public function distinct()
    {
        return self::createRelation('distinct', func_get_args());
    }
    
    static public function where()
    {
        return self::createRelation('where', func_get_args());
    }
    /**
     * }
     */
    
    /**
     * Take all records.
     */
    static public function all()
    {
        return self::none()->take();
    }
    
    /**
     * For directly pagination without conditions.
     */
    static public function paginate($page, $per_page)
    {
        $query = self::createRelation();
        return $query->paginate($page, $per_page);
    }
    
    /**
     * @return ActiveRecord\Collection
     */
    static public function createModelsFromQuery(AbstractRelation $query)
    {
        if ($query->will_paginate()) {
            $params = [
                'page'      => $query->get_page(),
                'perPage'   => $query->get_per_page(),
                'offset'    => $query->get_offset(),
                'totalRows' => $query->get_row_count()
            ];
        } else
            $params = [];
        
        return self::_create_collection($query->get_results()->fetchAll(PDO::FETCH_ASSOC), $params);
    }
    
    static protected function createRelation($init_method = null, array $init_args = [])
    {
        $cn = self::cn();
        $query = new Relation($cn, static::tableName());
        if ($init_method)
            call_user_func_array([$query, $init_method], $init_args);
        return $query;
    }
    
    /**
     * When calling this method for pagination, how do we tell it
     * the values for page and per_page? That's what extra_params is for...
     * Although maybe it's not the most elegant solution.
     * extra_params accepts 'page' and 'per_page', they will be sent to Query
     * where they will be parsed.
     */
    static public function findBySql($sql, array $params = array(), array $extra_params = array())
    {
        $query = self::createRelation();
        
        $query->complete_sql($sql, $params, $extra_params);
        
        return $query->take();
    }
    
    private function _find_has_one($prop, array $params)
    {
        empty($params['class_name']) && $params['class_name'] = Rails::services()->get('inflector')->camelize($prop);
        
        $builder = new Association($params, $this);
        $builder->build_query();
        return $builder->get_query()->first() ?: false;
    }
    
    /**
     * @param array $params - Additional parameters to customize the query for the association
     */
    private function _find_has_many($prop, $params)
    {
        $inflector = \Rails::services()->get('inflector');
        
        empty($params['class_name']) && $params['class_name'] = $inflector->camelize($inflector->singularize($prop));
        
        $builder = new Association($params, $this);
        $builder->build_query();
        
        return $builder->get_query()->take() ?: false;
    }
    
    private function _find_belongs_to($prop, array $params)
    {
        empty($params['class_name']) && $params['class_name'] = ucfirst($prop);
        
        $foreign_key = !empty($params['foreign_key']) ? $params['foreign_key'] : Rails::services()->get('inflector')->underscore($prop) . '_id';
        
        if ($this->getAttribute($foreign_key)) {
            return $params['class_name']::where(['id' => $this->getAttribute($foreign_key)])->first() ?: false;
        } else {
            return false;
        }
    }
    
    private function _find_has_and_belongs_to_many($prop, array $params)
    {
        $find_params = [];
        
        empty($params['class_name']) && $params['class_name'] = ucfirst(Rails::services()->get('inflector')->singularize($prop));
        
        $find_params['class_name'] = $params['class_name'];
        $find_params['from'] = $find_params['class_name']::tableName();
        
        empty($find_params['join_table']) && $find_params['join_table'] = $find_params['class_name']::tableName() . '_' . $find_params['from'];
        
        empty($find_params['join_type']) && $find_params['join_type'] = 'join';
        empty($find_params['join_table_key']) && $find_params['join_table_key'] = 'id';
        empty($find_params['association_foreign_key']) && $find_params['association_foreign_key'] = substr($find_params['from'], 0, -1) . '_id';
        
        $joins = strtoupper($find_params['join_type']) . ' `' . $find_params['join_table'] . '` ON `' . $find_params['from'] . '`.`' . $find_params['join_table_key'] . '` = `' . $find_params['join_table'] . '`.`' . $find_params['association_foreign_key'] . '`';
        
        ## Having Post model with has_and_belongs_to_many => tags (Tag model):
        // 'join_table'              => posts_tags [not this => (or tags_posts)]
        // 'join_table_key'          => id
        // 'foreign_key'             => post_id
        // 'association_foreign_key' => tag_id
        
        # Needed SQL params
        // 'select'     => posts.*
        // 'joins'      => JOIN posts_tags ON posts.id = posts_tags.post_id',
        // 'conditions' => array('posts_tags.post_id = ?', $this->id)
        
        /**
        
        'has_and_belongs_to_many' => [
            'tags' => [
                'join_type'         => 'join',
                'join_table_key'    => 'id',
                'foreign_key'       => 'post_id',
                'association_foreign_key' => 'tag_id',
                'select'            => 'posts.*'
            ]
        ]
        
        */
        $relation = new Association($find_params, $this);
        $relation->build_query();
        $relation->get_query()->joins($joins);
        return $relation->get_query()->take() ?: false;
    }
}