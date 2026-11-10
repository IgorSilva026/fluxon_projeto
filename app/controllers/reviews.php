<?php
require_once __DIR__ . '/../repositories/ReviewRepository.php';
require_once __DIR__ . '/../services/ReviewService.php';
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
    // Nenhum identificador de usuário recebido no formulário é usado.
    $owner = (int) $_SESSION['uid'];
    $repo = new ReviewRepository($db);
    $review = $repo->find($owner);
    if ($POST) {
        chk();
        try {
            $action = $_POST['acao'] ?? '';
            if ($action === 'excluir') {
                if (($_POST['confirmar_exclusao'] ?? '') !== '1') {
                    throw new InvalidArgumentException(
                        'Marque a confirmação para excluir sua avaliação.',
                    );
                }
                $repo->delete($owner);
                flash('ok', 'Sua avaliação foi excluída.');
                go('avaliacao');
            }
            if ($action !== 'salvar') {
                throw new InvalidArgumentException('Ação inválida.');
            }
            $valid = ReviewService::validate($_POST);
            $repo->save($owner, $valid['nota'], $valid['comentario']);
            flash(
                'ok',
                'Sua avaliação foi salva. Você pode editá-la ou excluí-la nesta tela.',
            );
            go('avaliacao');
        } catch (InvalidArgumentException $ex) {
            $err = $ex->getMessage();
        }
    }
    require __DIR__ . '/../views/reviews_view.php';
} catch (Throwable $ex) {
    error_log('Fluxon: falha ao consultar ou salvar avaliação.');
    http_response_code(500);
    card(
        'Avaliar aplicativo',
        '<p>Não foi possível acessar as avaliações. Tente novamente mais tarde. Se persistir, contate a equipe.</p><a href="?p=painel">Voltar ao painel</a>',
    );
}
