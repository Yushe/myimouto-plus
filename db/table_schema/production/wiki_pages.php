<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'updated_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'version' => 
    array (
      'type' => 'int(11)',
      'default' => '1',
    ),
    'title' => 
    array (
      'type' => 'varchar(64)',
      'default' => NULL,
    ),
    'body' => 
    array (
      'type' => 'text',
      'default' => NULL,
    ),
    'user_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'ip_addr' => 
    array (
      'type' => 'varchar(15)',
      'default' => NULL,
    ),
    'is_locked' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'text_search_index' => 
    array (
      'type' => 'text',
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