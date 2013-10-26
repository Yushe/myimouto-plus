<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'user_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'ip_addr' => 
    array (
      'type' => 'varchar(64)',
      'default' => NULL,
    ),
    'file_size' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'md5' => 
    array (
      'type' => 'varchar(32)',
      'default' => NULL,
    ),
    'last_commented_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'file_ext' => 
    array (
      'type' => 'varchar(4)',
      'default' => NULL,
    ),
    'last_noted_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'source' => 
    array (
      'type' => 'varchar(249)',
      'default' => NULL,
    ),
    'cached_tags' => 
    array (
      'type' => 'text',
      'default' => NULL,
    ),
    'width' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'height' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'rating' => 
    array (
      'type' => 'char(1)',
      'default' => 'q',
    ),
    'preview_width' => 
    array (
      'type' => 'int(3)',
      'default' => NULL,
    ),
    'preview_height' => 
    array (
      'type' => 'int(3)',
      'default' => NULL,
    ),
    'actual_preview_width' => 
    array (
      'type' => 'int(3)',
      'default' => NULL,
    ),
    'actual_preview_height' => 
    array (
      'type' => 'int(3)',
      'default' => NULL,
    ),
    'score' => 
    array (
      'type' => 'int(3)',
      'default' => '0',
    ),
    'is_shown_in_index' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '1',
    ),
    'is_held' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'has_children' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'status' => 
    array (
      'type' => 'enum(\'deleted\',\'flagged\',\'pending\',\'active\')',
      'default' => 'active',
      'enum_values' => 
      array (
        0 => 'deleted',
        1 => 'flagged',
        2 => 'pending',
        3 => 'active',
      ),
    ),
    'is_rating_locked' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'is_note_locked' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'parent_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'sample_width' => 
    array (
      'type' => 'int(5)',
      'default' => NULL,
    ),
    'sample_height' => 
    array (
      'type' => 'int(5)',
      'default' => NULL,
    ),
    'sample_size' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'index_timestamp' => 
    array (
      'type' => 'datetime',
      'default' => '0000-00-00 00:00:00',
    ),
    'jpeg_width' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'jpeg_height' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'jpeg_size' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'random' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'approver_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
  ),
  1 => 
  array (
    'pri' => 
    array (
      0 => 'id',
    ),
  ),
)
;