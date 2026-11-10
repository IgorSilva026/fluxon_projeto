<?php
// A sessão é verificada antes de disponibilizar as telas internas de suporte.
if (empty($_SESSION['uid'])) {
    go('login');
}
try {
    $q = $db->prepare(
        'SELECT email_verificado_em,versao_sessao FROM users WHERE id=?',
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
} catch (Throwable $ex) {
    error_log('Fluxon: falha ao consultar sessão de suporte.');
    http_response_code(500);
    card(
        'Ajuda e suporte',
        '<p>Não foi possível abrir esta área. Tente novamente mais tarde.</p><a href="?p=painel">Voltar ao painel</a>',
    );
}
require __DIR__ . '/../views/support_view.php';
