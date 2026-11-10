<?php
require_once __DIR__ . '/../repositories/ProfileRepository.php';
require_once __DIR__ . '/../services/ProfileService.php';

if (empty($_SESSION['uid'])) {
    go('login');
}

$q = $db->prepare(
    'SELECT email_verificado_em, versao_sessao FROM users WHERE id=?',
);
$q->execute([$_SESSION['uid']]);
$auth = $q->fetch();

if (
    !$auth ||
    !$auth['email_verificado_em'] ||
    (int) $auth['versao_sessao'] !== ($_SESSION['versao_sessao'] ?? -1)
) {
    session_destroy();
    go('login');
}

$id = (int) $_SESSION['uid'];
$repo = new ProfileRepository($db);

// Compatibilidade com links antigos: abre o fluxo completo.
if (isset($_GET['excluir'])) {
    go('excluir-conta');
}

try {
    if ($p === 'foto') {
        $photo = $repo->photo($id);

        if (!$photo) {
            http_response_code(404);
            exit();
        }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . strlen($photo));
        echo $photo;
        exit();
    }

    $data = $repo->find($id);

    if ($POST) {
        chk();

        // O formulário antigo não pode mais excluir diretamente.
        if (($_POST['acao'] ?? '') === 'excluir_conta') {
            go('excluir-conta');
        }

        try {
            $v = ProfileService::validate($_POST);

            $remove = ($_POST['remover_foto'] ?? '') === '1';

            $photo = $remove
                ? null
                : ProfileService::photo($_FILES['foto'] ?? []);

            $repo->save($id, $v, $photo, $remove);

            flash('ok', 'Perfil e preferências salvos.');
            go('perfil');
        } catch (InvalidArgumentException $ex) {
            $err = $ex->getMessage();

            foreach (
                [
                    'nome',
                    'telefone',
                    'cidade',
                    'ocupacao',
                    'cnpj',
                    'tipo_empresa',
                    'aliquota',
                    'frequencia',
                    'horario',
                ]
                as $key
            ) {
                if (isset($_POST[$key]) && is_string($_POST[$key])) {
                    $data[$key] = $_POST[$key];
                }
            }

            foreach (
                [
                    'notif_app',
                    'notif_email',
                    'reserva_auto',
                    'alertas_vencimento',
                    'lembretes_cobranca',
                ]
                as $key
            ) {
                $data[$key] = ($_POST[$key] ?? '') === '1' ? 1 : 0;
            }
        }
    }

    require __DIR__ . '/../views/profile_view.php';
} catch (Throwable $ex) {
    error_log('Fluxon: falha ao consultar ou salvar perfil.');
    http_response_code(500);

    card(
        'Minha conta',
        '<p>Não foi possível acessar o perfil. ' .
            'Tente novamente ou solicite suporte.</p>' .
            '<a href="?p=painel">Voltar ao painel</a>',
    );
}
