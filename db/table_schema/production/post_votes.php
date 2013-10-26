<?php
return array (
  0 => 
  array (
    'post_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'user_id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'score' => 
    array (
      'type' => 'int(1)',
      'default' => '0',
    ),
    'updated_at' => 
    array (
      'type' => 'datetime',
      'default' => '0000-00-00 00:00:00',
    ),
  ),
  1 => 
  array (
    'uni' => 
    array (
      0 => 'post_id',
      1 => 'user_id',
    ),
  ),
)
;