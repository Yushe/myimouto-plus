<?php
namespace Rails\Toolbox;

class ClassTools
{
    /**
     * Returns all parents of a class.
     *
     * @return array
     */
    static public function getParents($className)
    {
        $parents = [];
        $parent = new \ReflectionClass($className);
        while (true) {
            $parent = $parent->getParentClass();
            if (!$parent) {
                break;
            } else {
                $parents[] = $parent->getName();
            }
        }
        return $parents;
    }
}