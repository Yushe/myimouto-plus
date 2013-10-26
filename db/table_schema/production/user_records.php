<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'bigint(20)',
      'default' => NULL,
    ),
    'user_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'reported_by' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'is_positive' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '1',
    ),
    'body' => 
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