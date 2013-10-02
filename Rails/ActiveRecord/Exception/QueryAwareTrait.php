<?php
namespace Rails\ActiveRecord\Exception;

trait QueryAwareTrait
{
    protected $stmt;
    
    protected $values;
    
    public function setStatement(\PDOStatement $stmt, array $values = [])
    {
        $this->stmt = $stmt;
        $this->values = $values;
        return $this;
    }
    
    public function postMessage()
    {
        $msg = '';
        if ($this->stmt) {
            $msg .= "\nQuery: " . $this->stmt->queryString;
            if ($this->values)
                $msg .= "\nValues: " . var_export($this->values, true);
        }
        return $msg;
    }
}
