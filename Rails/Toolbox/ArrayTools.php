<?php
namespace Rails\Toolbox;

abstract class ArrayTools
{
    /**
     * Flattens an array.
     */
    static public function flatten(array $arr)
    {
        $flat = array();
        foreach ($arr as $v) {
            if (is_array($v))
                $flat = array_merge($flat, self::flatten($v));
            else
                $flat[] = $v;
        }
        return $flat;
    }
    
    /**
     * Checks if an array is indexed.
     */
    static public function isIndexed(array $array)
    {
        $i = 0;
        foreach (array_keys($array) as $k) {
            if ($k !== $i)
                return false;
            $i++;
        }
        return true;
    }
}