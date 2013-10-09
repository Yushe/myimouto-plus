<?php
namespace Rails\ActiveRecord\Base\Methods;

use Rails\ActiveRecord\Exception;

trait AssociationMethods
{
    /**
     * An array where loaded associations will
     * be stored.
     */
    private $loadedAssociations = [];
    
    protected function associations()
    {
        return [];
    }
    
    public function getAssociation($name)
    {
        if (isset($this->loadedAssociations[$name])) {
            return $this->loadedAssociations[$name];
        } elseif ($assoc = $this->get_association_data($name)) {
            $model = $this->_load_association($name, $assoc[0], $assoc[1]);
            $this->loadedAssociations[$name] = $model;
            return $this->loadedAssociations[$name];
        }
    }
    
    protected function setAssociation($name, $object)
    {
        if (!in_array($name, $this->_associations_names())) {
            throw new Exception\RuntimeException(
                sprintf("Tried to set unknown association: %s", $name)
            );
        }
        $this->loadedAssociations[$name] = $object;
    }
    
    # Returns association property names.
    private function _associations_names()
    {
        $associations = array();
        foreach ($this->associations() as $assocs) {
            foreach ($assocs as $k => $v)
                $associations[] = is_int($k) ? $v : $k;
        }
        return $associations;
    }
    
    /**
     * @param array|Closure $params - Additional parameters to customize the query for the association
     */
    private function _load_association($prop, $type, $params)
    {
        return $this->{'_find_' . $type}($prop, $params);
    }
    
    private function get_association_data($prop)
    {
        if ($assocs = $this->associations()) {
            foreach ($assocs as $type => $assoc) {
                foreach ($assoc as $name => $params) {
                    if (is_int($name)) {
                        $name = $params;
                        $params = array();
                    }
                    
                    if ($name == $prop) {
                        return array($type, $params);
                    }
                }
            }
        }
        return false;
    }
}
