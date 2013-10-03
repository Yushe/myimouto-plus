<?php
namespace Rails\ActiveRecord\Base\Methods;

use Rails;
use Rails\ActiveRecord\Exception;

/**
 * Attributes are properties that correspond to a column in the table.
 * However, these "properties" are actually stored in the actual instance
 * property $attributes.
 *
 * Models *should* define getters and setters for each attribute, but they
 * can be called overloadingly (see Rails\ActiveRecord\Base::__call()).
 * I say *should* because it is said that overloading is bad for performance.
 *
 * For convenience (I'd say), in the case of getter methods, the "get" prefix is
 * omitted (except for methods that require a parameter, like getAttribute($attrName)),
 * so the expected name of the getter methods is the camel-cased name of the corresponding attribute,
 * for example createdAt(). This method can either check itself if the index for the attribute exists
 * in the $attributes array and return it, or simply return getAttribute($attrName).
 *
 * Setter methods have the "set" prefix, and they should set the new value in the $attributes array.
 */
trait AttributeMethods
{
    /**
     * Calling attributes throgh magic methods would be like:
     * $post->createdAt()
     * The corresponding column for this attribute would be "created_at",
     * therefore, the attribute name will be converted.
     * For some cases, to disable the camel to lower conversion,
     * this property can be set to false.
     */
    static protected $convertAttributeNames = true;
    
    /**
     * Expected to hold only the model's attributes.
     */
    protected $attributes = [];
    
    /**
     * Holds data grabbed from the database for models
     * without a primary key, to be able to update them.
     * Hoever, models should always have a primary key.
     */
    private $storedAttributes = array();
    
    private $changedAttributes = array();
    
    static public function convertAttributeNames($value = null)
    {
        if (null !== $value) {
            static::$convertAttributeNames = (bool)$value;
        } else {
            return static::$convertAttributeNames;
        }
    }
    
    static public function isAttribute($name)
    {
        // if (!Rails::config()->ar2) {
            // return static::table()->columnExists(static::properAttrName($name));
        // } else {
            return static::table()->columnExists($name);
        // }
    }
    
    /**
     * This method allows to "overloadingly" get attributes this way:
     * $model->parentId; instead of $model->parent_id.
     */
    static public function properAttrName($name)
    {
        if (static::convertAttributeNames()) {
            $name = \Rails::services()->get('inflector')->underscore($name);
        }
        return $name;
    }

    /**
     * @throw Exception\InvalidArgumentException
     */
    public function getAttribute($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        // } elseif (!Rails::config()->ar2 && static::table()->columnExists(static::properAttrName($name))) {
            // return null;
        } elseif (static::table()->columnExists($name)) {
            return null;
        }
        
        throw new Exception\InvalidArgumentException(
            sprintf("Trying to get non-attribute '%s' from model %s", $name, get_called_class())
        );
    }
    
    public function setAttribute($name, $value)
    {
        if (!self::isAttribute($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Trying to set non-attribute '%s' for model %s", $name, get_called_class())
            );
        }
        
        if ((string)$this->getAttribute($name) != (string)$value) {
            $this->setChangedAttribute($name, $this->$name);
        }
        $this->attributes[$name] = $value;
        return $this;
    }
    
    public function issetAttribute($name)
    {
        if (!self::isAttribute($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf("'%s' isn't an attribute for model %s", $name, get_called_class())
            );
        }
        
        return isset($this->attributes[$name]);
    }
    
    /**
     * Add/change attributes to model
     *
     * Filters protected attributes of the model.
     * Also calls the "getAttribute()" method, if exists, of the model,
     * in case extra operation is needed when changing certain attribute.
     * It's intended to be an equivalent to "def attribute=(val)" in rails.
     * E.g. "is_held" for post model.
     *
     * @see _run_setter()
     */
    public function assignAttributes(array $attrs, array $options = [])
    {
        if (!$attrs) {
            return;
        }
        
        if (empty($options['without_protection'])) {
            $this->filterProtectedAttributes($attrs);
        }
        
        // if (!Rails::config()->ar2) {
            // foreach ($attrs as $attr => $v) {
                // if ($this->setterExists($attr)) {
                    // $this->_run_setter($attr, $v);
                // } else {
                    // $this->$attr = $v;
                // }
            // }
            // return;
        // }
        
        // $inflector = Rails::services()->get('inflector');
        // $reflection = new \ReflectionClass(get_called_class());
        
        foreach ($attrs as $attrName => $value) {
            if (self::isAttribute($attrName)) {
                $this->setAttribute($attrName, $value);
            } else {
                if ($setterName = $this->setterExists($attrName)) {
                    $this->$setterName($value);
                // $setter = 'set' . $inflector->camelize($attrName);
                } elseif (self::hasPublicProperty($attrName)) {
                    $this->$attrName = $value;
                // if ($reflection->hasMethod($setter) && $reflection->getMethod($setter)->isPublic()) {
                    // $this->$setter($value);
                } else {
                    throw new Exception\RuntimeException(
                        sprintf("Can't write unknown attribute '%s' for model %s", $attrName, get_called_class())
                    );
                }
            }
        }
    }
    
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * The changedAttributes array is filled upon updating a record.
     * When updating, the stored data of the model is retrieved and checked
     * against the data that will be saved. If an attribute changed, the old value
     * is stored in this array.
     *
     * Calling a method that isn't defined, ending in Changed, for example nameChanged() or
     * categoryIdChanged(), is the same as calling attributeChanged('name') or
     * attributeChanged('category_id').
     *
     * @return bool
     * @see attributeWas()
     */
    public function attributeChanged($attr)
    {
        return array_key_exists($attr, $this->changedAttributes);
    }
    
    /**
     * This method returns the previous value of an attribute before updating a record. If
     * it was not changed, returns null.
     */
    public function attributeWas($attr)
    {
        return $this->attributeChanged($attr) ? $this->changedAttributes[$attr] : null;
    }
    
    public function changedAttributes()
    {
        return $this->changedAttributes;
    }
    
    /**
     * List of the attributes that can't be changed in the model through
     * assignAttributes().
     * If both attrAccessible() and attrProtected() are present in the model,
     * only attrAccessible() will be used.
     *
     * Return an empty array so no attributes are protected (except the default ones).
     */
    protected function attrProtected()
    {
        return null;
    }
    
    /**
     * List of the only attributes that can be changed in the model through
     * assignAttributes().
     * If both attrAccessible() and attrProtected() are present in the model,
     * only attrAccessible() will be used.
     *
     * Return an empty array so no attributes are accessible.
     */
    protected function attrAccessible()
    {
        return null;
    }
    
    protected function setChangedAttribute($attr, $oldValue)
    {
        $this->changedAttributes[$attr] = $oldValue;
    }
    
    private function filterProtectedAttributes(&$attributes)
    {
        # Default protected attributes
        $default_columns = ['created_at', 'updated_at', 'created_on', 'updated_on'];
        
        if ($pk = static::table()->primaryKey()) {
            $default_columns[] = $pk;
        }
        
        $default_protected = array_fill_keys(array_merge($default_columns, $this->_associations_names()), true);
        $attributes = array_diff_key($attributes, $default_protected);
        
        if (is_array($attrs = $this->attrAccessible())) {
            $attributes = array_intersect_key($attributes, array_fill_keys($attrs, true));
        } elseif (is_array($attrs = $this->attrProtected())) {
            $attributes = array_diff_key($attributes, array_fill_keys($attrs, true));
        }
    }
}