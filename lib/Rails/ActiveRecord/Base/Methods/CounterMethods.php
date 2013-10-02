<?php
namespace Rails\ActiveRecord\Base\Methods;

trait CounterMethods
{
    static public function incrementCounter($counter_name, $id)
    {
        return self::updateCounters([$id], [$counter_name => 1]);
    }
    
    static public function decrementCounter($counter_name, $id)
    {
        return self::updateCounters([$id], [$counter_name => -1]);
    }
    
    static public function updateCounters(array $ids, array $counters)
    {
        if (!is_array($ids))
            $ids = [$ids];
        $values = [];
        foreach ($counters as $name => $value)
            $values[] = "`" . $name . "` = `".$name."` " . ($value > 0 ? '+' : '-') . " 1";
        $sql = "UPDATE `".self::tableName()."` SET ".implode(', ', $values)." WHERE id IN (?)";
        return self::connection()->executeSql($sql, $ids);
    }
}
