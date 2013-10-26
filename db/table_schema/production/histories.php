<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'bigint(20) unsigned',
      'default' => NULL,
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'user_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'group_by_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'group_by_table' => 
    array (
      'type' => 'varchar(12)',
      'default' => NULL,
    ),
    'aux_as_json' => 
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