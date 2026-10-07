<?php
// Segredos somente em config.local.php (ignorado pelo Git).
$defaults = [
  'db' => ['dsn' => 'mysql:host=localhost;dbname=fluxon;charset=utf8mb4', 'user' => 'root', 'pass' => ''],
  'base' => 'http://localhost/fluxon_projeto/index.php',
  'google' => ['id' => '', 'secret' => ''],
  'smtp' => ['host' => '', 'port' => 587, 'user' => '', 'pass' => '', 'secure' => 'tls'],
  'from' => '',
];
$local = __DIR__ . '/config.local.php';
return is_file($local) ? array_replace_recursive($defaults, require $local) : $defaults;
