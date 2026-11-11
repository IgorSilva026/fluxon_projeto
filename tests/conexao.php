<?php
// Diagnóstico da conexão com o Supabase. Uso (na raiz do projeto):
//   C:\xampp\php\php.exe tests\conexao.php
// Não imprime a senha. Pode colar a saída para pedir ajuda.
$ok = fn($m) => print "[OK]    $m\n";
$aviso = fn($m) => print "[AVISO] $m\n";
$erro = function ($m) {
    print "[ERRO]  $m\n";
    $GLOBALS['falhas'] = ($GLOBALS['falhas'] ?? 0) + 1;
};
$raiz = dirname(__DIR__);
print "== Diagnóstico de conexão Fluxon ==\n";

// 1) Extensão do PHP
$ini = php_ini_loaded_file() ?: '(nenhum php.ini carregado)';
if (!extension_loaded('pdo_pgsql')) {
    $erro("A extensão pdo_pgsql NÃO está ativa neste PHP. Abra $ini, tire o ';' de 'extension=pdo_pgsql' e 'extension=pgsql', salve e reinicie o Apache. Confirme com: php -m | findstr pgsql");
    exit(1);
}
$ok('Extensão pdo_pgsql ativa (' . PHP_VERSION . ')');

// 2) Origem da configuração
$temEnv = is_file("$raiz/.env");
$temLocal = is_file("$raiz/config.local.php");
$temEnv ? $ok('Arquivo .env encontrado') : $aviso('Arquivo .env NÃO encontrado em ' . $raiz . ' (copie .env.example para .env)');
if ($temLocal) {
    $aviso('config.local.php também existe; o .env tem prioridade quando preenchido.');
}
if (is_file("$raiz/.env.txt")) {
    $erro('Existe .env.txt: o Bloco de Notas salvou com extensão errada. Renomeie para .env');
}
$C = require "$raiz/config.php";
$dsn = $C['db']['dsn'];
$user = $C['db']['user'];
$pass = $C['db']['pass'];
$d = [];
foreach (explode(';', substr($dsn, strlen('pgsql:'))) as $par) {
    [$k, $v] = array_pad(explode('=', $par, 2), 2, '');
    $d[$k] = $v;
}
$host = $d['host'] ?? '';
$port = (int) ($d['port'] ?? 5432);
$db = $d['dbname'] ?? '';
print "        host=$host porta=$port banco=$db usuário=$user senha=" . strlen($pass) . " caracteres\n";

// 3) Conferência dos valores (erros comuns)
if ($host === '' || $host === 'localhost') {
    $erro('DB_HOST vazio ou "localhost": preencha com o host do Session pooler (Supabase > Connect).');
}
if (preg_match('~^https?://~i', $host)) {
    $erro('DB_HOST não pode começar com https://. A URL "xxx.supabase.co" é da API, não do banco. Use o host do Session pooler: aws-0-REGIAO.pooler.supabase.com');
}
if (preg_match('~^[a-z0-9]{20}\.supabase\.co$~i', $host)) {
    $erro('Este host é o da API (REF.supabase.co). Para o banco use o host de Connect > Session pooler.');
}
if (preg_match('~^db\.[a-z0-9]+\.supabase\.co$~i', $host)) {
    $aviso('Host de conexão direta: só funciona com IPv6. Se falhar, troque pelo Session pooler (porta 5432).');
}
if (str_ends_with($host, '.pooler.supabase.com') && !str_contains($user, '.')) {
    $erro('No pooler o usuário deve ser "postgres.SEU_PROJECT_REF" (com o ponto e o código do projeto), não só "' . $user . '".');
}
if ($user !== '' && !str_starts_with($user, 'postgres')) {
    $erro('DB_USER deve começar com "postgres" (ex.: postgres.SEU_PROJECT_REF).');
}
if (str_starts_with($pass, 'sb_') || str_starts_with($pass, 'eyJ')) {
    $erro('DB_PASS parece uma CHAVE DE API (sb_... / eyJ...). Use a SENHA DO BANCO (Project Settings > Database > Reset database password).');
}
if ($pass === '' || preg_match('~SENHA_DO_BANCO|SUA_SENHA|COLE_AQUI~', $pass)) {
    $erro('DB_PASS vazio ou ainda com texto de exemplo.');
}
if ($port === 6543) {
    $erro('Porta 6543 é o Transaction pooler e quebra o PDO. Use 5432 (Session pooler).');
}
if ($db !== '' && $db !== 'postgres') {
    $aviso("DB_NAME=$db. No Supabase o banco padrão se chama 'postgres'; só use outro nome se ele existir.");
}
if (!empty($GLOBALS['falhas'])) {
    print "\nCorrija os itens acima no .env e rode de novo.\n";
    exit(1);
}

// 4) Rede
$ip = gethostbyname($host);
if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
    $aaaa = function_exists('dns_get_record') ? @dns_get_record($host, DNS_AAAA) : [];
    $erro($aaaa
        ? "O host $host só tem endereço IPv6 e sua rede/PC não resolve IPv6. Use o host do Session pooler."
        : "Não consegui resolver o host '$host' (DNS). Confira se copiou o host inteiro, sem espaços, e se há internet.");
    exit(1);
}
$ok("Host resolvido ($ip)");
$s = @fsockopen($host, $port, $en, $es, 6);
if (!$s) {
    $erro("Não consegui abrir a porta $port em $host ($es). Verifique firewall/antivírus/rede da faculdade (bloqueio de porta 5432) ou tente outra rede.");
    exit(1);
}
fclose($s);
$ok("Porta $port acessível");

// 5) Login no banco
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
} catch (PDOException $e) {
    $m = str_replace($pass, '***', $e->getMessage());
    print "[ERRO]  Falha ao autenticar: " . trim(preg_replace('~\s+~', ' ', $m)) . "\n";
    if (stripos($m, 'tenant or user not found') !== false) {
        print "        -> Usuário/projeto/região não batem. Confirme DB_USER=postgres.SEU_REF e que o DB_HOST é da MESMA região/projeto (copie de Connect > Session pooler).\n";
    } elseif (stripos($m, 'password authentication failed') !== false || $e->getCode() === '28P01') {
        print "        -> Senha do banco incorreta. Redefina em Project Settings > Database e atualize DB_PASS.\n";
    } elseif (stripos($m, 'does not exist') !== false) {
        print "        -> O banco '$db' não existe. Use DB_NAME=postgres.\n";
    } elseif (stripos($m, 'ssl') !== false) {
        print "        -> Problema de SSL. Mantenha DB_SSLMODE=require.\n";
    } elseif (stripos($m, 'timeout') !== false || stripos($m, 'unreachable') !== false) {
        print "        -> Rede sem acesso (IPv6/firewall). Use o Session pooler e teste em outra rede.\n";
    }
    exit(1);
}
$ok('Conectado como ' . $pdo->query('SELECT current_user')->fetchColumn());

// 6) Tabelas
$esperadas = ['users', 'tokens', 'user_profiles', 'user_reviews', 'account_deletion_tokens'];
$q = $pdo->query("SELECT c.relname, c.relrowsecurity FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace WHERE n.nspname='public' AND c.relkind='r'");
$achadas = [];
foreach ($q as $r) {
    $achadas[$r['relname']] = $r['relrowsecurity'];
}
$faltam = array_diff($esperadas, array_keys($achadas));
if ($faltam) {
    print '[ERRO]  Tabelas ausentes: ' . implode(', ', $faltam) . "\n        -> Cole database/schema.sql no SQL Editor do Supabase e execute.\n";
    exit(1);
}
$ok('Tabelas do Fluxon presentes');
foreach ($esperadas as $t) {
    if (!$achadas[$t]) {
        $aviso("RLS desligado em $t: rode o schema.sql de novo (ele liga o RLS).");
    }
}
print "\nTudo certo: o PHP está conectado ao banco.\n";
