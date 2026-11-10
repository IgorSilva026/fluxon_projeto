<?php
require_once __DIR__ . '/../services/AccountDeletionService.php';
if (empty($_SESSION['uid'])) {
    go('login');
}
try {
    $q = $db->prepare(
        'SELECT email_verificado_em,versao_sessao FROM users WHERE id=?',
    );
    $q->execute([$_SESSION['uid']]);
    $u = $q->fetch();
    if (
        !$u ||
        !$u['email_verificado_em'] ||
        (int) $u['versao_sessao'] !== ($_SESSION['versao_sessao'] ?? -1)
    ) {
        session_destroy();
        go('login');
    }
    $owner = (int) $_SESSION['uid'];
    $version = (int) $_SESSION['versao_sessao'];
    $service = new AccountDeletionService($db);
    if ($POST) {
        chk();
        try {
            $action = $_POST['acao'] ?? '';
            if ($action === 'enviar') {
                $issued = $service->issue($owner, $version);
                if (
                    !enviar(
                        $issued['email'],
                        'Confirmação de exclusão da conta Fluxon',
                        "Você solicitou excluir sua conta Fluxon.\nCódigo de confirmação: {$issued['codigo']}\nVálido por dez minutos e até cinco tentativas.\nVolte à tela Excluir conta, informe o código e confirme a exclusão.\nSe não solicitou, não compartilhe este código e contate a equipe.",
                    )
                ) {
                    $service->cancelDelivery($owner, $issued['hash']);
                    throw new InvalidArgumentException(
                        'Não foi possível enviar o código. A conta foi mantida. Tente novamente mais tarde.',
                    );
                }
                flash(
                    'ok',
                    'Código enviado ao e-mail cadastrado. Ele vale por dez minutos.',
                );
                go('excluir-conta');
            } elseif ($action === 'cancelar') {
                $service->cancel($owner);
                flash('ok', 'Solicitação cancelada. Sua conta foi mantida.');
                go('painel');
            } elseif ($action === 'excluir') {
                $code = AccountDeletionService::validate($_POST);
                $service->delete($owner, $version, $code);
                $_SESSION = [];
                session_regenerate_id(true);
                flash(
                    'ok',
                    'Conta excluída. Os dados vinculados foram removidos do banco ativo.',
                );
                go('inicio');
            } else {
                throw new InvalidArgumentException('Ação inválida.');
            }
        } catch (InvalidArgumentException $ex) {
            $err = $ex->getMessage();
        }
    }
    require __DIR__ . '/../views/delete_account_view.php';
} catch (Throwable $ex) {
    error_log('Fluxon: falha ao processar exclusão de conta.');
    http_response_code(500);
    card(
        'Excluir conta',
        '<p>Não foi possível concluir a operação. Tente novamente mais tarde ou procure a equipe de suporte.</p><a href="?p=painel">Voltar ao painel</a>',
    );
}
