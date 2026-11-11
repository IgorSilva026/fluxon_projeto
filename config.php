<?php
// Segredos ficam em .env (ou config.local.php). Ambos são ignorados pelo Git.
// Ordem: valores padrão < config.local.php < .env
$defaults = [
    'db' => [
        // Supabase: copie host/usuário da tela Connect > Session pooler.
        'dsn' => 'pgsql:host=localhost;port=5432;dbname=postgres;sslmode=require',
        'user' => 'postgres',
        'pass' => '',
    ],
    'base' => 'http://localhost/fluxon_projeto-main/index.php',
    'google' => ['id' => '', 'secret' => ''],
    'smtp' => [
        'host' => '',
        'port' => 587,
        'user' => '',
        'pass' => '',
        'secure' => 'tls',
    ],
    'from' => '',
];
$local = __DIR__ . '/config.local.php';
$cfg = is_file($local)
    ? array_replace_recursive($defaults, require $local)
    : $defaults;

// Leitor mínimo de .env: CHAVE=valor, # comentários, aspas opcionais.
$env = [];
$arquivoEnv = __DIR__ . '/.env';
if (is_file($arquivoEnv)) {
    foreach (file($arquivoEnv, FILE_IGNORE_NEW_LINES) ?: [] as $i => $linha) {
        if ($i === 0) {
            $linha = preg_replace('/^\xEF\xBB\xBF/', '', $linha); // BOM do Bloco de Notas
        }
        $linha = trim($linha);
        if (
            $linha === '' ||
            $linha[0] === '#' ||
            !preg_match('/^(?:export\s+)?([A-Za-z0-9_]+)\s*=\s*(.*)$/', $linha, $m)
        ) {
            continue;
        }
        $v = trim($m[2]);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
            $v = substr($v, 1, -1);
        } else {
            $v = trim(preg_replace('/\s+#.*$/', '', $v));
        }
        $env[$m[1]] = $v;
    }
}
$tem = fn($k) => isset($env[$k]) && $env[$k] !== '';

if ($tem('DB_HOST')) {
    $cfg['db']['dsn'] = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        $env['DB_HOST'],
        $tem('DB_PORT') ? $env['DB_PORT'] : '5432',
        $tem('DB_NAME') ? $env['DB_NAME'] : 'postgres',
        $tem('DB_SSLMODE') ? $env['DB_SSLMODE'] : 'require',
    );
}
if ($tem('DB_USER')) {
    $cfg['db']['user'] = $env['DB_USER'];
}
if (isset($env['DB_PASS'])) {
    $cfg['db']['pass'] = $env['DB_PASS']; // não precisa codificar caracteres especiais
}
if ($tem('APP_BASE_URL')) {
    $cfg['base'] = $env['APP_BASE_URL'];
}
foreach (['GOOGLE_CLIENT_ID' => 'id', 'GOOGLE_CLIENT_SECRET' => 'secret'] as $k => $c) {
    if ($tem($k)) {
        $cfg['google'][$c] = $env[$k];
    }
}
foreach (
    ['SMTP_HOST' => 'host', 'SMTP_PORT' => 'port', 'SMTP_USER' => 'user', 'SMTP_PASS' => 'pass']
    as $k => $c
) {
    if ($tem($k)) {
        $cfg['smtp'][$c] = $k === 'SMTP_PORT' ? (int) $env[$k] : $env[$k];
    }
}
if (isset($env['SMTP_SECURE'])) {
    $cfg['smtp']['secure'] = $env['SMTP_SECURE']; // tls, ssl ou vazio
}
if ($tem('MAIL_FROM')) {
    $cfg['from'] = $env['MAIL_FROM'];
}
return $cfg;
