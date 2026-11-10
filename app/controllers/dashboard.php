<?php
// Dashboard financeiro. Incluído pela rota "painel" depois da checagem de sessão.
require_once __DIR__ . '/../repositories/ProfileRepository.php';
require_once __DIR__ . '/../repositories/TransactionRepository.php';
require_once __DIR__ . '/../services/TransactionService.php';

$uid = (int) $_SESSION['uid'];
$hoje = new DateTimeImmutable('today');
$hojeStr = $hoje->format('Y-m-d');
$repo = new TransactionRepository($db);
$perfil = (new ProfileRepository($db))->find($uid);

$saldo = TransactionService::centavos($repo->saldo($uid));
$mes = $repo->periodo(
    $uid,
    $hoje->modify('first day of this month')->format('Y-m-d'),
    $hoje->modify('last day of this month')->format('Y-m-d'),
);
$entradasMes = TransactionService::centavos($mes['entrada']);
$saidasMes = TransactionService::centavos($mes['saida']);
$reserva = TransactionService::reserva(
    $entradasMes,
    (float) ($perfil['aliquota'] ?? 14),
    $repo->proximoImposto($uid),
);
$semanas = TransactionService::semanas(
    $repo->entradasPendentes($uid, $hoje->modify('+29 days')->format('Y-m-d')),
    $hojeStr,
);
$escala = TransactionService::escala(max(array_column($semanas, 'total')));
$lancamentos = $repo->recentes($uid, 8);
$atrasados = $repo->atrasados($uid, $hojeStr);

$tipos = ['autonomo' => 'Autônomo', 'mei' => 'MEI', 'simples' => 'Simples Nacional', 'presumido' => 'Lucro presumido', 'real' => 'Lucro real'];
$sb = [
    'ativo' => 'painel',
    'iniciais' => TransactionService::iniciais($u['nome']),
    'nome' => $u['nome'],
    'sub' => implode(' · ', array_filter([
        $perfil['ocupacao'] ?? '',
        $tipos[$perfil['tipo_empresa'] ?? ''] ?? '',
    ])) ?: 'Minha conta',
];
require __DIR__ . '/../views/dashboard_view.php';
