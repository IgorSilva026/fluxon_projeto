<?php
require_once __DIR__ . '/../services/PasswordService.php';
if (empty($_SESSION['uid'])) {
    go('login');
}
try {
    $q = $db->prepare(
        'SELECT senha_hash,email_verificado_em,versao_sessao FROM users WHERE id=?',
    );
    $q->execute([$_SESSION['uid']]);
    $account = $q->fetch();
    if (
        !$account ||
        !$account['email_verificado_em'] ||
        (int) $account['versao_sessao'] !== ($_SESSION['versao_sessao'] ?? -1)
    ) {
        session_destroy();
        go('login');
    }
    if ($POST) {
        chk();
        try {
            foreach (['atual', 'nova', 'confirmacao'] as $key) {
                if (
                    !isset($_POST[$key]) ||
                    !is_string($_POST[$key]) ||
                    $_POST[$key] === '' ||
                    strlen($_POST[$key]) > 1024
                ) {
                    throw new InvalidArgumentException(
                        'Preencha os três campos de senha corretamente.',
                    );
                }
            }
            PasswordService::change(
                $db,
                (int) $_SESSION['uid'],
                (int) $_SESSION['versao_sessao'],
                $_POST['atual'],
                $_POST['nova'],
                $_POST['confirmacao'],
            );
            $_SESSION = [];
            session_regenerate_id(true);
            flash(
                'ok',
                'Senha alterada. Entre novamente com a nova senha e confirme o código enviado ao seu e-mail.',
            );
            go('login');
        } catch (InvalidArgumentException $e) {
            $err = $e->getMessage();
        }
    }
    require __DIR__ . '/../views/security_view.php';
} catch (Throwable $e) {
    error_log('Fluxon: falha ao acessar ou atualizar senha.');
    http_response_code(500);
    card(
        'Segurança',
        '<p>Não foi possível concluir a operação. Tente novamente mais tarde.</p><a href="?p=painel">Voltar ao painel</a>',
    );
}
