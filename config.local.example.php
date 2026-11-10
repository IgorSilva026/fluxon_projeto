<?php
return [
    'db' => [
        // Supabase > Connect > "Session pooler" (funciona em IPv4).
        // host: aws-0-REGIAO.pooler.supabase.com | porta 5432 | usuário: postgres.SEU_PROJECT_REF
        'dsn' => 'pgsql:host=aws-0-sa-east-1.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require',
        'user' => 'postgres.SEU_PROJECT_REF',
        'pass' => 'SENHA_DO_BANCO_DEFINIDA_NO_SUPABASE',
    ],
    'base' => 'http://localhost/fluxon_projeto-main/index.php',
    'google' => ['id' => '', 'secret' => ''],
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'secure' => 'tls',
    ],
    'from' => '',
];
