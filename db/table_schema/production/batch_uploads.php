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
    'ip' => 
    array (
      'type' => 'varchar(15)',
      'default' => NULL,
    ),
    'url' => 
    array (
      'type' => 'varchar(512)',
      'default' => NULL,
    ),
    'tags' => 
    array (
      'type' => 'text',
      'default' => NULL,
    ),
    'active' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'status' => 
    array (
      'type' => 'varchar(32)',
      'default' => 'pending',
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'data_as_json' => 
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