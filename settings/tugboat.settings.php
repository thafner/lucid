<?php

/**
 * @file
 * Tugboat settings.
 */
echo("RETURN2");
$databases = [
  'default' => [
    'default' => [
      'database' => 'tugboat',
      'username' => 'tugboat',
      'password' => 'tugboat',
      'host' => 'mysql',
      'port' => '3306',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
      'prefix' => '',
    ],
  ],
];
