<?php
namespace Rails\ActiveRecord\Adapter;

use Rails\ActiveRecord\ActiveRecord;
use Rails\ActiveRecord\Connection;
use Rails\ActiveRecord\Relation;
use Rails\ActiveRecord\Relation\AbstractRelation;

abstract class AbstractQueryBuilder extends Relation
{
    protected
        $_sql,
        $_params,
        $_stmt,
        $_row_count,
        $_will_paginate,
        $complete_sql,
        $query,
        $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    public function executeSql()
    {
        $this->_stmt = $this->connection->executeSql($this->_params);
        if ($this->will_paginate)
            $this->calculate_found_rows();
    }
    
    abstract protected function calculate_found_rows();
    
    abstract protected function _build_sql();
    
    public function build_sql(AbstractRelation $query)
    {
        $complete_sql = $query->complete_sql();
        $this->query = $query;
        
        if ($complete_sql) {
            list ($sql, $params) = $complete_sql;
            array_unshift($params, $sql);
            $this->_params = $params;
            $this->will_paginate = $query->will_paginate();
        } else {
            $this->will_paginate = $query->will_paginate();
            $this->_build_sql();
        }
    }
    
    public function stmt()
    {
        return $this->_stmt;
    }
    
    public function row_count()
    {
        return $this->_row_count;
    }
}