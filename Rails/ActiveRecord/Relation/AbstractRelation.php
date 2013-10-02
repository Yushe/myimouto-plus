<?php
namespace Rails\ActiveRecord\Relation;

use ReflectionClass;
use ReflectionException;
use Rails;
use Rails\ActiveRecord\ActiveRecord;
use Rails\Toolbox;
use Rails\ActiveRecord\Exception;

abstract class AbstractRelation
{
    public
        $builder;
    protected
        $select = [],
        $distinct,
        $from,
        $joins = [],
        $where = [],
        $where_params = [],
        $order = [],
        $group = [],
        $having = [],
        $having_params = [],
        $offset,
        $limit,
        $page,
        $per_page,
        
        $pages,
        $rows,
        
        $complete_sql,
        
        $will_paginate,
        $model_name,
        $previous_connection_name;
    
    /**
     * Should be used only by ActiveRecord_Base.
     */
    public function __construct($model_name = null, $table_name = null)
    {
        if ($model_name) {
            $this->model_name = $model_name;
            $this->from($table_name);
        }
    }
    
    public function __call($method, $params)
    {
        /**
         * Check if method is a scope method in the model class.
         */
        $cn = $this->model_name;
        
        if ($this->merge_with_scope($method, $params))
            return $this;
        
        throw new Exception\BadMethodCallException(
            sprintf("Call to undefined method %s::%s()", __CLASS__, $method)
        );
    }
    
    /**
     * Excecutes the query.
     * Returns all records.
     *
     * @return ActiveRecord\Collection
     */
    public function take($limit = null)
    {
        if ($limit !== null) {
            $this->limit($limit);
        }
        $this->executeSql();
        $cn = $this->model_name;
        return $cn::createModelsFromQuery($this);
    }
    
    /**
     * Executes a pagination query, that will calculate found rows.
     * Both parameters (page and per_page) must be set, else an Exception will be thrown.
     * Parameters can be set on the fly or using page() and per_page().
     *
     * @param int $page      Current page number
     * @param int $per_page  Results per page
     * @throw                Exception
     * @see page()
     * @see per_page()
     */
    public function paginate($page = null, $per_page = null)
    {
        if ($page !== null)
            $this->page($page);
        
        if ($per_page !== null)
            $this->perPage($per_page);
        
        if ($this->page === null)
            throw new Exception\BadMethodCallException("Missing page parameter for pagination");
        elseif ($this->limit === null || $this->offset === null)
            throw new Exception\BadMethodCallException("Missing per_page parameter for pagination");
        
        $this->will_paginate = true;
        
        return $this->take();
    }
    
    /**
     * For this to work, all :where parameters must have been passed like this:
     * Model::where(["foo" => $foo, "bar" => $bar])->where(["baz" => $baz]);
     */
    public function firstOrInitialize()
    {
        $model = $this->first();
        
        if ($model)
            return $model;
        else {
            $cn = $this->model_name;
            $model = new $cn();
            
            foreach ($this->where as $params) {
                if (!is_array($params))
                    throw new Exception\InvalidArgumentException("Invalid 'where' parameters passed for firstOrInitialize");
                
                foreach ($params as $column => $value)
                    $model->$column = $value;
            }
            return $model;
        }
    }
    
    /**
     * For this to work, all :where parameters must have been passed like this:
     * Model::where(["foo" => $foo, "bar" => $bar])->where(["baz" => $baz]);
     *
     * A closure object may be passed to perform any additional actions (like adding
     * additional properties to the model) before it is created.
     */
    public function firstOrCreate(Closure $block = null)
    {
        $model = $this->first();
        
        if ($model) {
            return $model;
        } else {
            $cn = $this->model_name;
            $model = new $cn();
            
            foreach ($this->where as $params) {
                if (!is_array($params)) {
                    throw new Exception\InvalidArgumentException("Invalid 'where' parameters passed for firstOrCreate");
                }
                
                foreach ($params as $column => $value)
                    $model->$column = $value;
            }
            
            if ($block)
                $block($model);
            
            $model->save();
            return $model;
        }
    }
    
    /**
     * @return null|ActiveRecord_Base   The records found or null
     */
    public function first($limit = 1)
    {
        $this->limit = $limit;
        
        $collection = $this->take();
        
        if ($limit == 1) {
            if ($collection->any())
                return $collection[0];
        } else
            return $collection;
    }
    
    /**
     * Accepts more than 1 argument.
     */
    public function pluck($column)
    {
        $columns = func_get_args();
        $argv_count = func_num_args();
        
        $collection = $this->take();
        $values = [];
        
        if ($collection->any()) {
            if ($argv_count == 1) {
                foreach ($collection as $model)
                    $values[] = $model->$column;
            } else {
                foreach ($collection as $model) {
                    $row_values = [];
                    foreach ($columns as $column) {
                        $row_values[] = $model->$column;
                    }
                    $values[] = $row_values;
                }
            }
        }
        
        return $values;
    }
    
    public function count()
    {
        $this->select('COUNT(*) as count_all');
        $this->executeSql();
        if (($rows = $this->builder->stmt()->fetchAll()) && isset($rows[0]['count_all']))
            return (int)$rows[0]['count_all'];
    }
    
    public function exists()
    {
        return (bool)$this->count();
    }
    
    public function last()
    {
        $this->limit = 1;
        
        $collection = $this->take();
        if ($collection->any())
            return last($collection->members());
    }
    
    /**
     * "Unsets" a clause.
     * If the clause is invalid, it's just ignored.
     * This method has a complementary method called "only",
     * not yet implemented.
     */
    public function except($clause)
    {
        // $prop = '_' . $clause;
        
        switch ($clause) {
            case 'distinct':
            case 'from':
            case 'offset':
            case 'limit':
            case 'page':
            case 'per_page':
                $this->$clause = null;
                break;
            
            case 'select':
            case 'joins':
            case 'where':
            case 'where_params':
            case 'order':
            case 'group':
            case 'having':
            case 'having_params':
                $this->$clause = [];
                break;
        }
        return $this;
    }
    
    /**
     * Methods that modify the query {
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }
    
    public function group()
    {
        $this->group = array_merge($this->group, func_get_args());
        return $this;
    }

    /**
     * How to use:
     * $query->having("foo > ? AND bar < ?", $foo, $bar);
     * $query->having("foo > :foo AND bar < :bar", ['foo' => $foo, 'bar' => $bar]);
     * $query->having("foo > 15");
     */
    public function having()
    {
        $args = func_get_args();
        $count = count($args);
        
        if ($count == 1) {
            $this->having[] = $args[0];
        } else {
            $this->having[] = array_shift($args);
            
            if ($count == 2 && is_array($args[0]))
                $args = $args[0];
            
            if ($args)
                $this->having_params = array_merge($this->having_params, $args);
        }
        return $this;
    }
    
    public function joins($params)
    {
        if (!is_array($params))
            $params = [$params];
        $this->joins = array_merge($this->joins, $params);
        
        return $this;
    }
    
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function order()
    {
        $this->order = array_merge($this->order, func_get_args());
        return $this;
    }

    public function select($params)
    {
        if (!is_array($params))
            $params = [$params];
        $this->select = array_merge($this->select, $params);
        
        return $this;
    }
    
    public function distinct($value = true)
    {
        $this->distinct = $value;
        return $this;
    }

    /**
     * $query->where("foo = ? AND bar = ?", $foo, $bar);
     * $query->where("foo = ? AND bar = ?", $foo, [$bar, $baz]);
     * $query->where("foo = :foo AND bar = :bar", ['foo' => $foo, 'bar' => $bar]);
     * $query->where("foo = true");
     * 
     * Following 2 methods are basically the same, first is recommended over the second:
     * $query->where(["foo" => $foo, "bar" => $bar]);
     * $query->where("foo", true, "bar", $bar);
     *
     * Can't do:
     * $query->where("foo = ? AND bar = ?", [$foo, $bar]);
     * $query->where(["foo = ? AND bar = ?", $foo, $bar]);
     * $query->where(["foo", $foo]);
     */
    public function where()
    {
        $args = func_get_args();
        $count = count($args);
        
        if ($count == 1) {
            if (is_array($args[0])) {
                # This is expected to be a column => value associated array.
                # In this case, the array is stored as is.
                $this->where[] = $args[0];
            } else {
                # This is expected to be a string.
                $this->where[] = $args[0];
            }
        } elseif ($count) {
            # Case: $query->where("foo", true, "bar_baz", $bar);
            if ($count >= 2 && is_int($count / 2) && (!is_array($args[1]) || Toolbox\ArrayTools::isIndexed($args[1])) && is_bool(strpos($args[0], ' '))) {
                $where = [];
                foreach ($args as $key => $value) {
                    $key++;
                    if ($key && !($key % 2)) {
                        $where[$next_key] = $value;
                    } else {
                        $next_key = $value;
                    }
                }
                $this->where[] = $where;
            } else {
                $this->where[] = array_shift($args);
                
                # Case: $query->where('foo => :foo', ['foo' => $foo]);
                if ($count == 2 && is_array($args[0]) && !Toolbox\ArrayTools::isIndexed($args[0]))
                    $args = $args[0];
                
                if ($args)
                    $this->where_params = array_merge($this->where_params, $args);
            }
        }
        return $this;
    }
    /**
     * }
     */
    
    public function page($page)
    {
        $this->page = $page;
        return $this;
    }
    
    /**
     * Note that for this to work correctly, $page must be set.
     */
    public function perPage($per_page)
    {
        $this->limit = $per_page;
        $this->offset = ($this->page - 1) * $per_page;
        return $this;
    }
    
    /**
     * The following public methods are supposed to be used only by Rails.
     */
    public function will_paginate()
    {
        return $this->will_paginate;
    }
    
    /**
     * TODO: this method should be changed.
     * Expected to be used only by the system, when calling find_by_sql().
     */
    public function complete_sql($sql = null, array $params = [], array $extra_params = [])
    {
        if (func_num_args()) {
            if (isset($extra_params['page']))
                $this->page = $extra_params['page'];
            if (isset($extra_params['perPage']))
                $this->limit = $extra_params['perPage'];
            if (isset($extra_params['offset']))
                $this->offset = $extra_params['offset'];
            if ($this->page && $this->limit)
                $this->will_paginate = true;
                
            $this->complete_sql = [$sql, $params];
            return $this;
        } else
            return $this->complete_sql;
    }
    
    public function get_page()
    {
        return $this->page;
    }
    
    public function get_per_page()
    {
        return $this->limit;
    }
    
    public function get_offset()
    {
        return $this->offset;
    }
    
    public function get_row_count()
    {
        return $this->builder->row_count();
    }
    
    public function get_results()
    {
        return $this->builder->stmt();
    }
    
    public function merge(self $other)
    {
        $scalars = [
            'distinct',
            'from',
            'offset',
            'limit',
            'page',
            'per_page',
        ];
        
        $arrays = [
             'select',
             'joins',
             'where',
             'where_params',
             'order',
             'group',
             'having',
             'having_params',
        ];
        
        foreach ($scalars as $scalar) {
            if ($other->$scalar !== null) {
                $this->$scalar = $other->$scalar;
            }
        }
        
        foreach ($arrays as $array) {
            $this->$array = array_merge($this->$array, $other->$array);
        }
        return $this;
    }
    
    # This method is expected to be used only by Rails.
    public function default_scoped($value)
    {
        $this->default_scoped = (bool)$value;
        return $this;
    }
    
    private function _set_model_connection()
    {
        if (defined($this->model_name . '::connection')) {
            $this->previous_connection_name = ActiveRecord::activeConnectionName();
            $model_name = $this->model_name;
            ActiveRecord::setConnection($model_name::connection);
            return true;
        }
    }
    
    private function _restore_previous_connection()
    {
        if ($this->previous_connection_name) {
            ActiveRecord::setConnection($this->previous_connection_name);
            $this->previous_connection_name = null;
        }
    }
    
    protected function executeSql()
    {
        $this->_set_model_connection();
        $builder_class = $this->_get_builder_class();
        
        $model_name = $this->model_name;
        $model_connection = $model_name::connection();
        $this->builder = new $builder_class($model_connection);
        
        $this->builder->build_sql($this);
        $this->builder->executeSql();
        
        $this->_restore_previous_connection();
    }
    
    private function merge_with_scope($name, array $params)
    {
        $cn = $this->model_name;
        
        if ($relation = $cn::scope($name, $params)) {
            $this->merge($relation);
            return true;
        }
        
        return false;
    }
    
    private function _get_builder_class()
    {
        $cn = $this->model_name;
        $class  = '\Rails\ActiveRecord\Adapter\\';
        $class .= ActiveRecord::proper_adapter_name($cn::connection()->adapterName());
        $class .= '\QueryBuilder';
        return $class;
    }
}