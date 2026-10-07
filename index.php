<?php
session_start(['use_strict_mode' => 1, 'cookie_httponly' => true,
  'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'cookie_samesite' => 'Lax']);
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
$C = require __DIR__ . '/config.php';
$db = new PDO($C['db']['dsn'], $C['db']['user'], $C['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$p = $_GET['p'] ?? 'splash';
$err = '';
$POST = $_SERVER['REQUEST_METHOD'] === 'POST';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function go($p) { global $C; header("Location: {$C['base']}?p=$p"); exit; }
function flash($k, $m) { $_SESSION[$k] = $m; }
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function chk() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('CSRF inválido'); }
function forte($s) { return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $s); }
function f($n, $l, $t = 'text', $v = '') { return "<label>$l<input name=\"$n\" type=\"$t\" value=\"" . e($v) . "\" required></label>"; }
function form($in, $btn) { return '<form method="post"><input type="hidden" name="csrf" value="' . csrf() . '">' . $in . "<button class=\"btn\">$btn</button></form>"; }

function enviar($to, $sub, $body) {
  global $C;
  try {
    if (!is_file(__DIR__ . '/vendor/autoload.php')) throw new RuntimeException('Dependências ausentes');
    require_once __DIR__ . '/vendor/autoload.php';
    $m = new \PHPMailer\PHPMailer\PHPMailer(true);
    $m->isSMTP(); $m->Host = $C['smtp']['host'];
    $m->Port = $C['smtp']['port']; $m->SMTPAuth = true;
    $m->Username = $C['smtp']['user']; $m->Password = $C['smtp']['pass'];
    $m->SMTPSecure = $C['smtp']['secure']; $m->Timeout = 15;
    $m->CharSet = 'UTF-8'; $m->setFrom($C['from'], 'Fluxon Analytics');
    $m->addAddress($to); $m->Subject = $sub; $m->Body = $body;
    return $m->send();
  } catch (Throwable $e) {
    error_log('Fluxon: falha no envio SMTP. Verifique a configuração do serviço.');
    return false;
  }
}
function token($uid, $tipo, $cod) {
  global $db;
  $db->prepare('UPDATE tokens SET usado=1 WHERE user_id=? AND tipo=?')->execute([$uid, $tipo]);
  $db->prepare('INSERT INTO tokens(user_id,tipo,hash,expira) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL ? MINUTE))')
     ->execute([$uid, $tipo, hash('sha256', $cod), $tipo === 'reset' ? 60 : 10]);
}
// 2º nível: código de 6 dígitos por e-mail, válido por 10 min, máx. 5 tentativas
function inicia2fa($uid) {
  global $db;
  $q = $db->prepare('SELECT email FROM users WHERE id=?'); $q->execute([$uid]);
  $cod = (string)random_int(100000, 999999);
  token($uid, '2fa', $cod);
  if (!enviar($q->fetch()['email'], 'Seu código de verificação Fluxon', "Código: $cod (válido por 10 minutos)")) {
    $db->prepare("UPDATE tokens SET usado=1 WHERE user_id=? AND tipo='2fa'")->execute([$uid]);
    unset($_SESSION['pre']); flash('err', 'Não foi possível enviar o código. Tente novamente mais tarde.'); go('login');
  }
  $_SESSION['pre'] = $uid;
  go('2fa');
}
function http($url, $post = null, $auth = null) {
  $c = curl_init($url);
  curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 10]);
  if ($post) { curl_setopt($c, CURLOPT_POST, 1); curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post)); }
  if ($auth) curl_setopt($c, CURLOPT_HTTPHEADER, ["Authorization: Bearer $auth"]);
  $body = curl_exec($c); $status = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
  if ($body === false || $status < 200 || $status >= 300) return [];
  $data = json_decode($body, true); return is_array($data) ? $data : [];
}
function view($t, $b) {
  global $C, $err;
  $x = '';
  foreach (['ok', 'err'] as $k) if (!empty($_SESSION[$k])) { $x .= "<p class=\"$k\">" . e($_SESSION[$k]) . '</p>'; unset($_SESSION[$k]); }
  if ($err) $x .= '<p class="err">' . e($err) . '</p>';
  echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($t) . ' · Fluxon</title><link rel="stylesheet" href="assets/css/style.css"></head><body>' . ($x ? '<div class="col">' . $x . $b . '</div>' : $b) . '</body></html>';
  exit;
}
function card($t, $in) { global $err; return view($t, '<div class="card"><div class="logo">Fluxon<span>Analytics</span></div><h1>' . e($t) . '</h1>' . '' . $in . '</div>'); }

switch ($p) {
  case 'splash':
    view('Fluxon', '<main class="splash"><div><div class="marca">Fluxon Analytics</div><p class="slogan">Sua tesouraria, no piloto automático.</p></div></main><script>setTimeout(()=>location="?p=inicio",1800)</script>');

  case 'inicio':
    $g = $C['google']['id'] ? '<a class="btn sec" href="?p=google">Continuar com Google</a>' : '';
    card('Fluxon Analytics', '<p>Sua tesouraria, no piloto automático.</p><a class="btn" href="?p=login">Entrar</a><a class="btn sec" href="?p=cadastro">Criar conta</a>' . $g);

  case 'cadastro':
    if ($POST) {
      chk();
      $n = trim($_POST['nome']); $m = strtolower(trim($_POST['email'])); $s = $_POST['senha'];
      if (!$n || !filter_var($m, FILTER_VALIDATE_EMAIL)) $err = 'Dados inválidos.';
      elseif (!forte($s)) $err = 'Senha fraca: mínimo 8 caracteres com maiúscula, minúscula, número e símbolo.';
      elseif ($s !== $_POST['senha2']) $err = 'As senhas não coincidem.';
      else {
        $q = $db->prepare('SELECT 1 FROM users WHERE email=?'); $q->execute([$m]);
        if ($q->fetch()) $err = 'E-mail já cadastrado.';
        else {
          $db->prepare('INSERT INTO users(nome,email,senha_hash) VALUES(?,?,?)')->execute([$n, $m, password_hash($s, PASSWORD_DEFAULT)]);
          flash('ok', 'Cadastro recebido. Confirme seu e-mail para concluir o acesso.'); inicia2fa($db->lastInsertId());
        }
      }
    }
    card('Criar conta', form(f('nome', 'Nome', 'text', $_POST['nome'] ?? '') . f('email', 'E-mail', 'email', $_POST['email'] ?? '') . f('senha', 'Senha', 'password') . f('senha2', 'Confirmar senha', 'password'), 'Cadastrar') . '<p class="links"><a href="?p=login">Já tenho conta</a></p>');

  case 'login':
    if ($POST) {
      chk();
      $m = strtolower(trim($_POST['email']));
      $q = $db->prepare('SELECT * FROM users WHERE email=?'); $q->execute([$m]); $u = $q->fetch();
      if ($u && $u['bloqueado_ate'] && strtotime($u['bloqueado_ate']) > time()) $err = 'Conta bloqueada por 15 min após várias tentativas.';
      elseif (!$u || !$u['senha_hash'] || !password_verify($_POST['senha'], $u['senha_hash'])) {
        if ($u) $db->prepare('UPDATE users SET tentativas=tentativas+1, bloqueado_ate=IF(tentativas>=5,DATE_ADD(NOW(),INTERVAL 15 MINUTE),NULL) WHERE id=?')->execute([$u['id']]);
        $err = 'E-mail ou senha incorretos.';
      } else {
        $db->prepare('UPDATE users SET tentativas=0,bloqueado_ate=NULL WHERE id=?')->execute([$u['id']]);
        inicia2fa($u['id']);
      }
    }
    $g = $C['google']['id'] ? '<a class="btn sec" href="?p=google">Entrar com Google</a>' : '';
    card('Entrar', form(f('email', 'E-mail', 'email') . f('senha', 'Senha', 'password'), 'Entrar') . $g . '<p class="links"><a href="?p=recuperar">Esqueci a senha</a> · <a href="?p=cadastro">Criar conta</a></p>');

  case '2fa':
    $uid = $_SESSION['pre'] ?? 0; if (!$uid) go('login');
    if ($POST) {
      chk();
      $q = $db->prepare("SELECT * FROM tokens WHERE user_id=? AND tipo='2fa' AND usado=0 AND expira>NOW() AND tentativas<5 ORDER BY id DESC LIMIT 1");
      $q->execute([$uid]); $t = $q->fetch();
      if ($t) $db->prepare('UPDATE tokens SET tentativas=tentativas+1 WHERE id=?')->execute([$t['id']]);
      if ($t && hash_equals($t['hash'], hash('sha256', trim($_POST['cod'])))) {
        $db->prepare('UPDATE tokens SET usado=1 WHERE id=?')->execute([$t['id']]);
        $db->prepare('UPDATE users SET email_verificado_em=COALESCE(email_verificado_em,NOW()) WHERE id=?')->execute([$uid]);
        $v = $db->prepare('SELECT versao_sessao FROM users WHERE id=?'); $v->execute([$uid]);
        session_regenerate_id(true); unset($_SESSION['pre']); $_SESSION['uid'] = $uid;
        $_SESSION['versao_sessao'] = (int)$v->fetchColumn(); go('painel');
      }
      $err = 'Código inválido ou expirado.';
    }
    card('Verificação em 2 etapas', '<p>Enviamos um código de 6 dígitos para o seu e-mail.</p>' . form(f('cod', 'Código', 'text'), 'Confirmar') . '<p class="links"><a href="?p=login">Voltar</a></p>');

  case 'recuperar':
    if ($POST) {
      chk();
      $q = $db->prepare('SELECT id,email FROM users WHERE email=?'); $q->execute([strtolower(trim($_POST['email']))]); $u = $q->fetch();
      if ($u) {
        $limit = $db->prepare('UPDATE users SET ultimo_reset=NOW() WHERE id=? AND (ultimo_reset IS NULL OR ultimo_reset < DATE_SUB(NOW(),INTERVAL 60 SECOND))');
        $limit->execute([$u['id']]);
        if ($limit->rowCount() === 1) { $tk = bin2hex(random_bytes(32)); token($u['id'], 'reset', $tk); enviar($u['email'], 'Recuperação de senha Fluxon', "Redefina sua senha (link válido por 1 hora):\n{$C['base']}?p=redefinir&t=$tk"); } }
      flash('ok', 'Se o e-mail existir, enviamos o link de recuperação.'); go('recuperar');
    }
    card('Recuperar senha', form(f('email', 'E-mail cadastrado', 'email'), 'Enviar link') . '<p class="links"><a href="?p=login">Voltar</a></p>');

  case 'redefinir':
    $tk = $_GET['t'] ?? '';
    $q = $db->prepare("SELECT * FROM tokens WHERE hash=? AND tipo='reset' AND usado=0 AND expira>NOW()"); $q->execute([hash('sha256', $tk)]); $t = $q->fetch();
    if (!$t) { flash('err', 'Link inválido ou expirado.'); go('recuperar'); }
    if ($POST) {
      chk();
      if (!forte($_POST['senha'])) $err = 'Senha fraca: mínimo 8 caracteres com maiúscula, minúscula, número e símbolo.';
      elseif ($_POST['senha'] !== $_POST['senha2']) $err = 'As senhas não coincidem.';
      else {
        $db->prepare('UPDATE users SET senha_hash=?,tentativas=0,bloqueado_ate=NULL,versao_sessao=versao_sessao+1 WHERE id=?')->execute([password_hash($_POST['senha'], PASSWORD_DEFAULT), $t['user_id']]);
        $db->prepare('UPDATE tokens SET usado=1 WHERE user_id=?')->execute([$t['user_id']]);
        unset($_SESSION['pre'], $_SESSION['uid'], $_SESSION['versao_sessao']);
        flash('ok', 'Senha alterada! Faça login.'); go('login');
      }
    }
    card('Nova senha', form(f('senha', 'Nova senha', 'password') . f('senha2', 'Confirmar senha', 'password'), 'Salvar'));

  case 'google':
    if (!$C['google']['id']) go('login');
    $_SESSION['st'] = bin2hex(random_bytes(16));
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query(['client_id' => $C['google']['id'], 'redirect_uri' => $C['base'] . '?p=gcb', 'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $_SESSION['st']]));
    exit;

  case 'gcb':
    if (empty($_SESSION['st']) || !hash_equals($_SESSION['st'], $_GET['state'] ?? '') || empty($_GET['code'])) go('login');
    unset($_SESSION['st']);
    $tok = http('https://oauth2.googleapis.com/token', ['code' => $_GET['code'], 'client_id' => $C['google']['id'], 'client_secret' => $C['google']['secret'], 'redirect_uri' => $C['base'] . '?p=gcb', 'grant_type' => 'authorization_code']);
    $i = http('https://openidconnect.googleapis.com/v1/userinfo', null, $tok['access_token'] ?? '');
    if (empty($i['sub']) || empty($i['email']) || ($i['email_verified'] ?? false) !== true) { flash('err', 'Falha no login com Google.'); go('login'); }
    $q = $db->prepare('SELECT id FROM users WHERE email=?'); $q->execute([strtolower($i['email'])]); $u = $q->fetch();
    if ($u) $db->prepare('UPDATE users SET google_id=? WHERE id=?')->execute([$i['sub'], $u['id']]);
    else { $db->prepare('INSERT INTO users(nome,email,google_id) VALUES(?,?,?)')->execute([$i['name'] ?? 'Usuário', strtolower($i['email']), $i['sub']]); $u = ['id' => $db->lastInsertId()]; }
    inicia2fa($u['id']);

  case 'painel':
    if (empty($_SESSION['uid'])) go('login');
    $q = $db->prepare('SELECT nome,email,email_verificado_em,versao_sessao FROM users WHERE id=?'); $q->execute([$_SESSION['uid']]); $u = $q->fetch();
    if (!$u || !$u['email_verificado_em'] || (int)$u['versao_sessao'] !== ($_SESSION['versao_sessao'] ?? -1)) { session_destroy(); go('login'); }
    if ($POST) { chk(); session_destroy(); header("Location: {$C['base']}?p=inicio"); exit; }
    card('Painel', '<p>Olá, <b>' . e($u['nome']) . '</b>!<br>' . e($u['email']) . '</p>' . form('', 'Sair'));

  default: go('splash');
}
