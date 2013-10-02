<?php
namespace Rails\ActiveRecord\Base\Methods;

use Rails\ActiveRecord\ModelSchema;

/**
 * These methods offer information about the table corresponding
 * to this model, with which the ModelSchema object will be created.
 */
trait ModelSchemaMethods
{
    static public function table()
    {
        $cn = get_called_class();
        if (!isset(self::$tables[$cn])) {
            $table = static::initTable();
            $table->reloadSchema();
            self::$tables[$cn] = $table;
        }
        return self::$tables[$cn];
    }
    
    static public function tableName()
    {
        $cn = str_replace('\\', '_', get_called_class());
        $inf = \Rails::services()->get('inflector');
        
        $tableName = $inf->underscore($inf->pluralize($cn));
        
        return static::tableNamePrefix() . $tableName . static::tableNameSuffix();
    }
    
    static public function tableNamePrefix()
    {
        return '';
    }
    
    static public function tableNameSuffix()
    {
        return '';
    }

    /**
     * @return ModelSchema
     */
    static protected function initTable()
    {
        return new ModelSchema(static::tableName(), static::connection());
    }
    
}