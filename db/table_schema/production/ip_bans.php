<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'int(11) unsigned',
      'default' => NULL,
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => '0000-00-00 00:00:00',
    ),
    'expires_at' => 
    array (
      'type' => 'datetime',
      'default' => '0000-00-00 00:00:00',
    ),
    'ip_addr' => 
    array (
      'type' => 'varchar(15)',
      'default' => NULL,
    ),
    'reason' => 
    array (
      'type' => 'text',
      'default' => NULL,
    ),
    'banned_by' => 
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