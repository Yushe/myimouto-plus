<?php
namespace Moebooru\Versioning;

use Moebooru\Versioning\Versioned;
use History;
use HistoryChange;
use PostTagHistory;
use NoteVersion;

class Versioning
{
    static protected $VERSIONED_CLASSES = ['Pool', 'PoolPost', 'Post', 'Tag', 'Note'];
    
    static protected $list = [];
    
    protected $versioned_attributes;
    
    protected $versioning_group_by;
    
    protected $versioning_aux_callback;
    
    protected $versioned_parent;
    
    protected $model_class;
    
    static public function get_versioned_classes()
    {
        return self::$VERSIONED_CLASSES;
    }
    
    static public function is_versioned_class($class)
    {
        return in_array($class, self::$VERSIONED_CLASSES);
    }
    
    static public function get($className)
    {
        if (!isset(self::$list[$className])) {
            $v = new self();
            $v->model_class = $className;
            $className::init_versioning($v);
            
            if (!$v->versioning_group_by) {
                $v->versioning_group_by();
            }
            
            self::$list[$className] = $v;
        }
        return self::$list[$className];
    }
    
    # Called at the start of each request to reset the history object, so we don't reuse an
    # object between requests on the same thread.
    static public function init_history()
    {
        $_SESSION['versioning_history'] = null;
    }
    
    # Configure the history display.
    #
    # :class => Group displayed changes with another class.
    # :foreign_key => Key within :class to display.
    # :controller, :action => Route for displaying the grouped class.
    #
    #   versioning_group_by :class => :pool
    #   versioning_group_by :class => :pool, :foreign_key => :pool_id, :action => "show"
    #   versioning_group_by :class => :pool, :foreign_key => :pool_id, :controller => Post
    public function versioning_group_by(array $options = [])
    {
        $calledClass = $this->model_class;
        $inflector = \Rails::services()->get('inflector');
        
        $opt = array_merge([
            'class' => $calledClass,
            'controller' => $calledClass,
            'action' => 'show',
        ], $options);
        
        if (empty($opt['foreign_key'])) {
            if ($opt['class'] == $calledClass) {
                $opt['foreign_key'] = 'id';
            } else {
                $opt['foreign_key'] = $inflector->underscore($opt['class']) . '_id';
            }
        }
        
        $this->versioning_group_by = $opt;
        return $this;
    }
    
    # Configure a callback to fill in auxilliary history information.
    #
    # The callback must return a hash; its contents will be serialized into aux.  This
    # is used where we need additional data for a particular type of history.
    public function versioning_aux_callback($func)
    {
        $this->versioning_aux_callback = $func;
    }
    
    public function get_versioning_aux_callback()
    {
        return $this->versioning_aux_callback;
    }
    
    public function get_versioned_default($name)
    {
        $attrs = $this->get_versioned_attributes();
        if (!isset($attrs[$name]) || !array_key_exists('default', $attrs[$name])) {
            return [null, false];
        }
        return [$attrs[$name]['default'], true];
    }
    
    public function get_versioning_group_by()
    {
        return $this->versioning_group_by;
    }
    
    public function get_group_by_class()
    {
        $cl = \Rails::services()->get('inflector')->classify($this->versioning_group_by['class']);
        return $cl;
    }
    
    public function get_group_by_table_name()
    {
        $cn = $this->get_group_by_class();
        return $cn::tableName();
    }
    
    public function get_group_by_foreign_key()
    {
        return $this->versioning_group_by['foreign_key'];
    }
    
    # Specify a parent table.  After a change is undone in this table, the
    # parent class will also receive an after_undo message.  If multiple
    # changes are undone together, changes to parent tables will always
    # be undone after changes to child tables.
    public function versioned_parent($c, array $options = [])
    {
        if (isset($options['foreign_key'])) {
            $foreign_key = $options['foreign_key'];
        } else {
            $foreign_key = \Rails::services()->get('inflector')->underscore($c) . '_id';
        }
        $this->versioned_parent = [
            'class' => $c,
            'foreign_key' => $foreign_key
        ];
        return $this;
    }
    
    public function get_versioned_parent()
    {
        return $this->versioned_parent;
    }

    /**
     * :default => If a default value is specified, initial changes (created with the
     * object) that set the value to the default will not be displayed in the UI.
     * This is also used by :allow_reverting_to_default.  This value can be set
     * to nil, which will match NULL.  Be sure at least one property has no default,
     * or initial changes will show up as a blank line in the UI.
     *
     * :allow_reverting_to_default => By default, initial changes.  Fields with
     * :allow_reverting_to_default => true can be undone; the default value will
     * be treated as the previous value.
     */
    public function versioned_attributes(array $attrs)
    {
        $fixed = [];
        foreach ($attrs as $k => $row) {
            if (is_int($k)) {
                $fixed[$row] = [];
            } else {
                $fixed[$k] = $row;
            }
        }
        $this->versioned_attributes = $fixed;
        return $this;
    }
    
    public function get_versioned_attributes()
    {
        return $this->versioned_attributes;
    }
    
    public function get_versioned_attribute_options($field)
    {
        $attrs = $this->get_versioned_attributes();
        if (array_key_exists($field, $attrs)) {
            return $attrs[$field];
        }
    }
    
    # Add default histories for any new versioned properties.  Group the new fields
    # with existing histories for the same object, if any, so new properties don't
    # fill up the history as if they were new properties.
    #
    # options:
    #
    # :attrs => [:column_name, :column_name2]
    # If set, specifies the attributes to import.  Otherwise, all versioned attributes
    # with no records are imported.
    #
    # :allow_missing => true
    # If set, don't throw an error if we're trying to import a property that doesn't exist
    # in the database.  This is used for initial import, where the versioned properties
    # in the codebase correspond to columns that will be added and imported in a later
    # migration.  This is not used for explicit imports done later, because we want to catch
    # errors (versioned properties that don't match up with column names).
    public function update_versioned_tables($c, array $options = [])
    {
        $table_name = $c::tableName();
        // p "Updating " . $table_name;
        
        // # Our schema doesn't allow us to apply single ON DELETE constraints, so use
        // # a rule to do it.  This is Postgresql-specific.
        // self::connection()->executeSql("
          // CREATE OR REPLACE RULE delete_histories AS ON DELETE TO ${table_name}
          // DO (
            // DELETE FROM history_changes WHERE remote_id = OLD.id AND table_name = '${table_name}';
            // DELETE FROM histories WHERE group_by_id = OLD.id AND group_by_table = '${table_name}';
          // );
        // ");
        
        $attributes_to_update = [];
        
        if (!empty($options['attrs'])) {
            $attrs = $options['attrs'];
            
            # Verify that the attributes we were told to update are actually versioned.
            $missing_attributes = array_diff($attrs, array_keys($c::versioning()->get_versioned_attributes()));
            // p $c::versioning()->get_versioned_attributes();
            if ($missing_attributes) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Tried to add versioned propertes for table \"%s\" that aren't versioned: %s",
                        $table_name, implode(' ', $missing_attributes)
                    )
                );
            }
        } else {
            $attrs = $c::versioning()->get_versioned_attributes();
        }
        
        foreach ($attrs as $att => $opt) {
            # If any histories already exist for this attribute, assume that it's already been updated.
            if (HistoryChange::where("table_name = ? AND column_name = ?", $table_name, $att)->first()) {
                continue;
            }
            $attributes_to_update[] = $att;
        }
        
        if (!$attributes_to_update) {
            return;
        }
        
        $attributes_to_update = array_filter(array_map(function($att) use ($c, $table_name) {
            $column_exists = $c::connection()->selectValue(
                "SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ?",
                $table_name, $att
            );
            if ($column_exists) {
                return $att;
            } else {
                if (empty($options['allow_missing'])) {
                    throw new Exception\RuntimeException(
                        sprintf(
                            "Expected to add versioned property \"%s\" for table \"%s\", but that column doesn't exist in the database",
                            $att, $table_name
                        )
                    );
                }
            }
        }, $attributes_to_update));
        if (!$attributes_to_update) {
            return;
        }
        
        // $c::transaction(function() use ($c, $attributes_to_update, $table_name) {
            $current = 1;
            $count = $c::connection()->selectValue("SELECT COUNT(*) FROM `" . $c::tableName() . "`");
            foreach ($c::order('id')->take() as $item) {
                // p $current . '/' . $count;
                $current++;
                
                $group_by_table = $c::versioning()->get_group_by_table_name();
                $group_by_id = $item->get_group_by_id();
                
                $history = History::order('id ASC')
                                    ->where("group_by_table = ? AND group_by_id = ?", $group_by_table, $group_by_id)
                                    ->first();
                
                if (!$history) {
                    $options = [
                        'group_by_table' => $group_by_table,
                        'group_by_id' => $group_by_id
                    ];
                    if ($c::isAttribute('user_id')) {
                        $options['user_id'] = $item->user_id;
                    } else {
                        $options['user_id'] = 1;
                    }
                    $history = History::create($options);
                }
                
                $to_crate = [];
                foreach ($attributes_to_update as $att) {
                    $value = $item->$att;
                    $options = [
                        'column_name' => $att,
                        'value' => $value,
                        'table_name' => $table_name,
                        'remote_id' => $item->id,
                        'history_id' => $history->id,
                    ];
                    
                    $to_create[] = $options;
                    
                    // $escaped_options = [];
                    // foreach ($options as $key => $value) {
                        // if ($value === null) {
                            // $escaped_options[$key] = 'NULL';
                        // } else {
                            // $column = HistoryChange::
                        // }
                    // }
                }
                
                $columns = array_keys($to_create[0]);
                
                $values = [];
                $marks = [];
                foreach ($to_create as $row) {
                    $outrow = [];
                    foreach ($columns as $col) {
                        // $val = $row[$col];
                        $outrow[] = $row[$col];
                    }
                    $marks[] = '(' . implode(', ', array_fill(0, count($outrow), '?')) . ')';
                    $values = array_merge($values, $outrow);
                }
                
                $sql = 'INSERT INTO history_changes (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $marks);
                // try {
                $stmt = $c::connection()->resource()->prepare($sql);
                $stmt->execute($values);
                // } catch (\Exception $e) {
                    // vpe($sql, $values);
                // }
            }
        // });
    }
    
    public function import_post_tag_history()
    {
        // $count = self::connection()->selectValue("SELECT COUNT(*) FROM post_tag_histories");
        $current = 1;
        foreach (PostTagHistory::order('id ASC')->take() as $tag_history) {
            // p $current . '/' . $count;
            $current++;
            
            $prev = $tag_history->previous();
            
            $tags = explode(' ', $tag_history->tags);
            $metatags = [];
            $tags_tmp = [];
            foreach ($tags as $x) {
                if (strpos($x, 'rating:') === 0) {
                    $metatags[] = $x;
                } else {
                    $tags_tmp[] = $x;
                }
            }
            sort($tags_tmp);
            $tags = implode(' ', $tags_tmp);
            unset($tags_tmp);
            
            $rating = '';
            $prev_rating = '';
            foreach ($metatags as $metatag) {
                if (preg_match('/^rating:([qse])/', $metatag, $m)) {
                    $rating = $m[1];
                }
            }
            
            if ($prev) {
                $prev_tags = explode(' ', $prev->tags);
                
                $prev_metatags = [];
                $prev_tags_tmp = [];
                foreach ($prev_tags as $x) {
                    if (preg_match('/^(?:-pool|pool|rating|parent):/', $x)) {
                        $prev_metatags[] = $x;
                    } else {
                        $prev_tags_tmp[] = $x;
                    }
                }
                sort($prev_tags_tmp);
                $prev_tags = implode(' ', $prev_tags_tmp);
                unset($prev_tags_tmp);
                
                foreach ($prev_metatags as $metatag) {
                    if (preg_match('/^rating:([qse])/', $metatag, $m)) {
                        $prev_rating = $m[1];
                    }
                }
                
                $changed = false;
                if ($tags != $prev_tags || $rating != $prev_rating) {
                    $h = History::create([
                        'group_by_table' => 'posts',
                        'group_by_id' => $tag_history->post_id,
                        'user_id' => $tag_history->user_id || $tag_history->post->user_id,
                        'created_at' => $tag_history->created_at
                    ]);
                }
                if ($tags != $prev_tags) {
                    HistoryChange::create([
                        'history_id' => $h->id,
                        'table_name' => 'posts',
                        'remote_id' => $tag_history->post_id,
                        'column_name' => 'cached_tags',
                        'value' => $tags,
                    ]);
                }
                
                if ($rating != $prev_rating) {
                    $c = HistoryChange::create([
                        'history_id' => $h->id,
                        'table_name' => 'posts',
                        'remote_id' => $tag_history->post_id,
                        'column_name' => 'rating',
                        'value' => $rating,
                    ]);
                }
            }
        }
    }
    
    public function import_note_history()
    {
        $count = NoteVersion::count();

        $current = 1;
        foreach (NoteVersion::order('id ASC')->take() as $ver) {
            // p $current . '/' . $count;
            $current++;
            
            if ($ver->version == 1) {
                $prev = null;
            } else {
                $prev = NoteVersion::where(
                    "post_id = ? and note_id = ? and version = ?",
                    $ver->post_id, $ver->note_id, $ver->version - 1
                )->first();
            }
            
            $fields = [];
            
            foreach (['is_active', 'body', 'x', 'y', 'width', 'height'] as $field) {
                $value = $ver->$field;
                if ($prev) {
                    $prev_value = $prev->$field;
                    if ($value == $prev_value)
                        continue;
                }
                $fields[] = [$field, $value];
            }
            
            # Only create the History if we actually found any changes.
            if ($fields) {
                $h = History::create([
                    'group_by_table' => 'posts',
                    'group_by_id' => $ver->post_id,
                    'user_id' => $ver->user_id ?: $ver->post->user_id,
                    'created_at' => $ver->created_at,
                    'aux' => ['note_body' => $prev ? $prev->body : $ver->body]
                ]);
                
                foreach ($fields as $f) {
                    HistoryChange::create([
                        'history_id' => $h->id,
                        'table_name' => 'notes',
                        'remote_id' => $ver->note_id,
                        'column_name' => $f[0],
                        'value' => $f[1]
                    ]);
                }
            }
        }
    }
    
    # Add base history values for newly-added properties.
    #
    # This is only used for importing initial histories.  When adding new versioned properties,
    # call update_versioned_tables directly with the table and attributes to update.
    public function update_all_versioned_tables()
    {
        foreach (self::get_versioned_classes() as $cls) {
            $this->update_versioned_tables($cls, ['allow_missing' => true]);
        }
    }
}
