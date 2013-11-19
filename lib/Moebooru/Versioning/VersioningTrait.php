<?php
namespace Moebooru\Versioning;

use Moebooru\Versioning\Versioning as Holder;
use History;
use HistoryChange;

trait VersioningTrait
{
    protected $object_is_new;
    
    /**
     * Returns the corresponding Versioning object for this model.
     */
    static public function versioning()
    {
        return Holder::get(get_called_class());
    }
    
    /**
     * This method receives the corresponding Versioning object to which
     * configuration is set through the following methods:
     *  - versioned_attributes, sets attributes that will be versioned.
     *  - versioning_group_by, configure the history display.
     *  - versioned_parent, to set a parent table.
     * More information can be found in each of the methods' description.
     */
    static public function init_versioning($versioning)
    {
    }
    
    protected function remember_new()
    {
        $this->object_is_new = true;
    }
    
    public function get_group_by_id()
    {
        $method = self::versioning()->get_group_by_foreign_key();
        return $this->getAttribute($method);
    }
    
    private function get_current_history()
    {
        $history = isset($_SESSION['versioning_history']) ?
                    $_SESSION['versioning_history'] :
                    null;
        
        if ($history) {
            if ($history->group_by_table != self::versioning()->get_group_by_table_name() ||
                $history->group_by_id != $this->get_group_by_id()
            ) {
                $_SESSION['versioning_history'] = null;
                $history = null;
            }
        }
        
        if (!$history) {
            $options = [
                'group_by_table' => self::versioning()->get_group_by_table_name(),
                'group_by_id' => $this->get_group_by_id(),
                'user_id' => current_user()->id
            ];
        
            $cb = self::versioning()->get_versioning_aux_callback();
            if ($cb) {
                $options['aux'] = $this->$cb();
            }
            $history = new \History($options);
            $history->save();
            
            $_SESSION['versioning_history'] = $history;
        }
        
        return $history;
    }
    
    protected function save_versioned_attributes()
    {
        self::transaction(function() {
            foreach (self::versioning()->get_versioned_attributes() as $att => $options) {
                /**
                 * MI: Upon creating a new PoolPost row, Moebooru shows "+post #xxx" in
                 * History. But this seems to be possible only by using the get_versioned_default method
                 * in Versioning, BUT that method is never called in original versioning.rb.
                 */
                $default = self::versioning()->get_versioned_default($att);
                if ($default[1]) {
                    if ($this->$att == $default[0] && !$this->attributeChanged($att))
                        continue;
                }
                
                # MI: If attribute wasn't changed, then just skip it?
                if (!$this->attributeChanged($att))
                    continue;
            
                # Always save all properties on creation.
                #
                # Don't use _changed?; it'll be true if a field was changed and then changed back,
                # in which case we must not create a change entry.
                $old = $this->attributeWas($att);
                $new = $this->$att;
                
                if ($old == $new && !$this->object_is_new)
                    continue;
                
                $history = $this->get_current_history();
                $h = new HistoryChange([
                    'table_name' => self::tableName(),
                    'remote_id' => $this->id,
                    'column_name' => $att,
                    'value' => $new,
                    'history_id' => $history->id
                ]);
                $h->save();
            }
        });
        
        # The object has been saved, so don't treat it as new if it's saved again.
        $this->object_is_new = false;
        
        return true;
    }
    
    public function versioned_master_object()
    {
        $parent = self::versioning()->get_versioned_parent();
        if (!$parent) {
            return null;
        }
        $type = \Rails::services()->get('inflector')->classify($parent['class']);
        $foreign_key = $parent['foreign_key'];
        $id = $this->$foreign_key;
        return $type::find($id);
    }
    
    protected function delete_history()
    {
        $sql = "DELETE FROM histories WHERE group_by_table = ? AND group_by_id = ?";
        self::connection()->executeSql($sql, static::tableName(), $this->id);
    }
    
    public function moeVersioningCallbacks()
    {
        return [
            'after_save' => [
                'save_versioned_attributes'
            ],
            'after_create' => [
                'remember_new'
            ],
            'after_destroy' => [
                'delete_history'
            ]
        ];
    }
}
