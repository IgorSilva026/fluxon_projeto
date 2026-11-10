<?php
$raiz = dirname(__DIR__);
$C = require "$raiz/config.php";
$s = $C['smtp'];
$de = $C['from'];
$para = $argv[1] ?? $s['user'];
print "== Teste de e-mail Fluxon ==\n";
print "        host={$s['host']} porta={$s['port']} seguranca=" . ($s['secure'] === '' ? '(nenhuma)' : $s['secure']) . " usuario={$s['user']} senha=" . strlen($s['pass']) . " caracteres\n        remetente=$de destinatario=$para\n";
$falhou = false;
$erro = function ($m) use (&$falhou) {
    print "[ERRO]  $m\n";
    $falhou = true;
};
if (!is_file("$raiz/.env")) {
    print "[AVISO] Nao ha .env nesta pasta ($raiz).\n";
}
if (!is_file("$raiz/vendor/autoload.php")) {
    $erro('Falta a pasta vendor/. Rode: composer install');
}
foreach (['SMTP_USER' => $s['user'], 'MAIL_FROM' => $de, 'destinatario' => $para] as $k => $v) {
    if ($v === '' || str_contains($v, 'seu.email@') || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
        $erro("$k vazio, invalido ou ainda com o texto de exemplo ('$v').");
    }
}
if ($s['pass'] === '' || str_contains($s['pass'], 'SENHA_DE_APP')) {
    $erro('SMTP_PASS vazio ou ainda com o texto de exemplo. Use a senha de app de 16 letras do Google.');
}
if (str_contains($s['pass'], ' ')) {
    $erro('SMTP_PASS tem espacos. Cole as 16 letras juntas, sem espacos.');
}
if ($s['host'] === '') {
    $erro('SMTP_HOST vazio. Para o Gmail use smtp.gmail.com.');
}
if ((int) $s['port'] === 465 && $s['secure'] !== 'ssl') {
    $erro('Porta 465 exige SMTP_SECURE=ssl. Para tls use a porta 587.');
}
if ((int) $s['port'] === 587 && $s['secure'] !== 'tls') {
    $erro('Porta 587 exige SMTP_SECURE=tls.');
}
if ($falhou) {
    print "\nCorrija o .env e rode de novo.\n";
    exit(1);
}
$x = @fsockopen(($s['secure'] === 'ssl' ? 'ssl://' : '') . $s['host'], (int) $s['port'], $en, $es, 8);
if (!$x) {
    print "[ERRO]  Nao consegui conectar em {$s['host']}:{$s['port']} ($es). Confira host/porta e se a rede bloqueia essa porta.\n";
    exit(1);
}
fclose($x);
print "[OK]    Servidor SMTP acessivel\n";
require "$raiz/vendor/autoload.php";
$m = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $m->isSMTP();
    $m->Host = $s['host'];
    $m->Port = $s['port'];
    $m->SMTPAuth = true;
    $m->Username = $s['user'];
    $m->Password = $s['pass'];
    $m->SMTPSecure = $s['secure'];
    $m->Timeout = 15;
    $m->CharSet = 'UTF-8';
    $m->setFrom($de, 'Fluxon Analytics');
    $m->addAddress($para);
    $m->Subject = 'Teste de e-mail Fluxon';
    $m->Body = "Se voce leu isto, o envio de e-mail do Fluxon esta funcionando.";
    $m->send();
    print "[OK]    E-mail enviado para $para. Confira a caixa de entrada (e o spam).\n";
} catch (Throwable $e) {
    $msg = $m->ErrorInfo ?: $e->getMessage();
    print "[ERRO]  " . trim(preg_replace('~\s+~', ' ', str_replace($s['pass'], '***', $msg))) . "\n";
    if (stripos($msg, 'authenticate') !== false || stripos($msg, '535') !== false) {
        print "        -> O Gmail recusou o login. Use uma SENHA DE APP (myaccount.google.com/apppasswords), nao a senha normal; a verificacao em 2 etapas precisa estar ativa e SMTP_USER deve ser o mesmo e-mail dela.\n";
    } elseif (stripos($msg, 'connect') !== false) {
        print "        -> Nao conectou. Confira SMTP_HOST, SMTP_PORT e SMTP_SECURE.\n";
    } elseif (stripos($msg, 'address') !== false) {
        print "        -> Endereco invalido. Confira MAIL_FROM e o destinatario.\n";
    }
    exit(1);
}
