<?php
namespace Rails\ActiveRecord\Adapter\Sqlite;

use Rails\ActiveRecord\Adapter\Exception;
use Rails\ActiveRecord\Adapter\AbstractQueryBuilder;

class QueryBuilder extends AbstractQueryBuilder
{
    public function calculate_found_rows()
    {
        $this->query->except('limit')->except('offset')->except('select')->select('COUNT(*) as count_all');
        $this->_build_sql();
        
        if ($stmt = $this->connection->executeSql($this->_params)) {
            $rows = $stmt->fetchAll();
            if (isset($rows[0]['count_all']))
                return $rows[0]['count_all'];
        }
    }
    
    protected function _build_sql()
    {
        $query = $this->query;
        
        $params = [];
        
        $sql = 'SELECT';
        
        if ($query->distinct)
            $sql .= ' DISTINCT';
        
        $sql .= ' ' . ($query->select ? implode(', ', $query->select) : '`' . $query->from . '`.*');
        $sql .= " FROM `" . $query->from . "`";
        $sql .= ' ' . implode(' ', $query->joins);
        
        if ($where = $this->_build_where_clause($query)) {
            $sql .= " WHERE " . $where[0];
            $params = $where[1];
            unset($where);
        }
        
        if ($query->group)
            $sql .= ' GROUP BY ' . implode(', ', $query->group);
        
        if ($query->order)
            $sql .= ' ORDER BY ' . implode(', ', $query->order);
        
        if ($query->having) {
            $sql .= ' HAVING ' . implode(' ', $query->having);
            $params = array_merge($params, $query->having_params);
        }
        
        if ($query->offset && $query->limit)
            $sql .= ' LIMIT ' . $query->offset . ', ' . $query->limit;
        elseif ($query->limit)
            $sql .= ' LIMIT ' . $query->limit;
        
        array_unshift($params, $sql);
        $this->_params = $params;
    }
    
    private function _build_where_clause($query) {
        if (!$query->where)
            return;
        
        $where = $where_params = [];
        $param_count = 0;
        
        foreach ($query->where as $condition) {
            # Case: ["foo" => $foo, "bar" => $bar];
            if (is_array($condition)) {
                foreach ($condition as $column => $value) {
                    $where[] = '`' . $column . '` = ?';
                    $where_params[] = $value;
                }
            } else {
                if ($count = substr_count($condition, '?')) {
                    foreach (range(0, $count - 1) as $i) {
                        if (!isset($query->where_params[$param_count]))
                            throw new Exception\RuntimeException(sprintf("Value for question mark placeholder for WHERE clause part wasn't found (%s)", $condition));
                        
                        if (is_array($query->where_params[$param_count])) {
                            $condition = preg_replace('/\?/', implode(', ', array_fill(0, count($query->where_params[$param_count]), '?')), $condition, 1);
                        }
                        
                        $where_params[] = $query->where_params[$param_count];
                        $param_count++;
                    }
                } elseif ($count = substr_count($condition, ':')) {
                    $where_params = $query->where_params;
                }
                
                $where[] = $condition;
            }
        }
        
        return [implode(' AND ', $where), $where_params];
    }
}