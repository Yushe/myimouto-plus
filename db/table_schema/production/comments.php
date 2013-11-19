<?php
return array (
  0 => 
  array (
    'id' => 
    array (
      'type' => 'int(11)',
      'default' => NULL,
    ),
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
    'ip_addr' => 
    array (
      'type' => 'varchar(16)',
      'default' => NULL,
    ),
    'created_at' => 
    array (
      'type' => 'datetime',
      'default' => '0000-00-00 00:00:00',
    ),
    'body' => 
    array (
      'type' => 'text',
      'default' => NULL,
    ),
    'updated_at' => 
    array (
      'type' => 'datetime',
      'default' => NULL,
    ),
    'is_spam' => 
    array (
      'type' => 'tinyint(1)',
      'default' => '0',
    ),
    'updater_ip_addr' => 
    array (
      'type' => 'varchar(46)',
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