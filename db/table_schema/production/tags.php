<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
    'name' => 
    array (
      'type' => 'varchar(64)',
      'default' => NULL,
    ),
    'post_count' => 
    array (
      'type' => 'int(11)',
      'default' => '0',
    ),
    'cached_related' => 
    array (
      'type' => 'text',
      'default' => NULL,
    ),
    'cached_related_expires_on' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'tag_type' => 
    array (
      'type' => 'smallint(6)',
      'default' => NULL,
    ),
    'is_ambiguous' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
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