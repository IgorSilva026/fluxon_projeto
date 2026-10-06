<?php
return [
  'db'     => ['dsn' => 'mysql:host=localhost;dbname=fluxon;charset=utf8mb4', 'user' => 'root', 'pass' => ''],
  'base'   => 'http://localhost/fluxon_projeto/index.php',
  // Google Cloud Console > Credenciais > ID do cliente OAuth. Redirect URI: <base>?p=gcb
  'google' => ['id' => '', 'secret' => ''],
  // true = e-mails (código 2FA / link de recuperação) são gravados em emails.log e exibidos na tela
  'dev'    => true,
  'from'   => 'no-reply@fluxon.com.br',
];
