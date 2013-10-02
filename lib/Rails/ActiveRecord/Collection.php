<?php
namespace Rails\ActiveRecord;

use Closure;
use Rails;
use Rails\ActiveModel\Collection as ModelCollection;

class Collection extends ModelCollection
{
    protected $page;
    
    protected $perPage;
    
    protected $totalPages;
    
    protected $offset;
    
    protected $totalRows;

    public function __construct(array $members = array(), array $data = null)
    {
        $this->members = $members;
        $this->set_extra_params($data);
    }
    
    public function currentPage()
    {
        return $this->page;
    }
    
    public function perPage()
    {
        return $this->perPage;
    }
    
    public function offset()
    {
        return $this->offset;
    }
    
    public function totalPages()
    {
        return $this->totalPages;
    }
    
    public function totalRows()
    {
        return $this->totalRows;
    }
    
    public function previousPage()
    {
        return $this->page - 1 ?: false;
    }
    
    public function nextPage()
    {
        return $this->page + 1 > $this->totalPages ? false : $this->page + 1;
    }
    
    private function set_extra_params($params)
    {
        if ($params) {
            $params = array_intersect_key($params, array_fill_keys(array('page', 'perPage', 'offset', 'totalRows'), null));
            foreach($params as $k => $v) {
                $this->$k = (int)$v;
            }
        }
        if ($this->totalRows && $this->perPage) {
            $this->totalPages = (int)ceil($this->totalRows / $this->perPage);
        }
    }
}