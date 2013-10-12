<?php
namespace Rails\ActiveRecord\Relation;

use Rails\ActiveRecord\ActiveRecord;
use Rails\ActiveRecord\Relation;

class Association extends AbstractRelation
{
    protected
        $query,
        $params,
        $parent_model;
    
    public function __construct(array $params, $parent_model)
    {
        $this->params = $params;
        $this->parent_model = $parent_model;
    }
    
    public function get_query()
    {
        return $this->query;
    }
    
    public function build_query()
    {
        $params = $this->params;
        
        if (empty($params['foreign_key'])) {
            $inflector = \Rails::services()->get('inflector');
            $cn = get_class($this->parent_model);
            $params['foreign_key'] = $inflector->singularize($cn::tableName()).'_id';
        }
        
        $query = new Relation($params['class_name'], $params['class_name']::tableName());
        
        $query->where('`' . $params['foreign_key'] . "` = ?", $this->parent_model->id);
        
        # params[0], if present, it's an anonymous function to customize the relation.
        # The function is binded to the relation object.
        if (isset($this->params[0])) {
            $lambda = array_shift($this->params);
            $lambda = $lambda->bindTo($query);
            $lambda();
        }
        
        $this->query = $query;
    }
}