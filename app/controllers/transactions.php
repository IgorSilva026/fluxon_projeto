<?php
require_once __DIR__ . '/../repositories/ProfileRepository.php';
require_once __DIR__ . '/../repositories/TransactionRepository.php';
require_once __DIR__ . '/../services/TransactionService.php';

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

// O dono vem sempre da sessão, nunca do formulário.
$uid = (int) $_SESSION['uid'];
$hoje = new DateTimeImmutable('today');
$hojeStr = $hoje->format('Y-m-d');
$repo = new TransactionRepository($db);

$filtro = $_GET['filtro'] ?? 'todos';
if (!is_string($filtro) || !in_array($filtro, TransactionService::FILTROS, true)) {
    $filtro = 'todos';
}
$pagina = max(1, (int) ($_GET['pg'] ?? 1));
$volta = fn() => 'transacoes&filtro=' . $filtro . '&pg=' . $pagina;
$old = [];

if ($POST) {
    chk();
    try {
        $acao = $_POST['acao'] ?? '';
        if ($acao === 'novo') {
            $repo->add($uid, TransactionService::validate($_POST, $hoje));
            flash('ok', 'Lançamento adicionado.');
            go($volta());
        }
        $id = (int) ($_POST['id'] ?? 0);
        if ($acao === 'alternar') {
            $ok = $repo->alternar($uid, $id);
            flash($ok ? 'ok' : 'err', $ok ? 'Situação atualizada.' : 'Lançamento não encontrado.');
            go($volta());
        }
        if ($acao === 'excluir') {
            $ok = $repo->excluir($uid, $id);
            flash($ok ? 'ok' : 'err', $ok ? 'Lançamento excluído.' : 'Lançamento não encontrado.');
            go($volta());
        }
        throw new InvalidArgumentException('Ação inválida.');
    } catch (InvalidArgumentException $e) {
        $err = $e->getMessage();
        $old = $_POST;
    } catch (PDOException $e) {
        error_log('Fluxon: falha ao gravar lançamento.');
        $err = 'Não foi possível salvar agora. Tente novamente.';
        $old = $_POST;
    }
}

$total = $repo->total($uid, $filtro, $hojeStr);
$paginas = max(1, (int) ceil($total / TransactionRepository::POR_PAGINA));
$pagina = min($pagina, $paginas);
$linhas = $repo->listar($uid, $filtro, $hojeStr, $pagina);
$perfil = (new ProfileRepository($db))->find($uid);
$tipos = ['autonomo' => 'Autônomo', 'mei' => 'MEI', 'simples' => 'Simples Nacional', 'presumido' => 'Lucro presumido', 'real' => 'Lucro real'];
$sb = [
    'ativo' => 'transacoes',
    'iniciais' => TransactionService::iniciais($perfil['nome'] ?? ''),
    'nome' => $perfil['nome'] ?? '',
    'sub' => implode(' · ', array_filter([
        $perfil['ocupacao'] ?? '',
        $tipos[$perfil['tipo_empresa'] ?? ''] ?? '',
    ])) ?: 'Minha conta',
];
require __DIR__ . '/../views/transactions_view.php';
