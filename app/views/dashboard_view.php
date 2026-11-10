<?php
$M = fn(int $c, bool $s = false) => TransactionService::moeda($c, $s);
$descSemanas = implode('; ', array_map(
    fn($s) => 'semana de ' . $s['rotulo'] . ': ' . $M($s['total']),
    $semanas,
));
ob_start();
?>
<link rel="stylesheet" href="assets/css/settings.css">
<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="settings-shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="workspace">
        <header class="topbar">
            <h1>Dashboard</h1>
            <div>
                <a class="bell" href="<?= $atrasados ? '?p=transacoes&amp;filtro=atrasados' : '?p=transacoes' ?>"
                   aria-label="<?= $atrasados ? e($atrasados . ' lançamento(s) atrasado(s)') : 'Sem lançamentos atrasados' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                    <?php if ($atrasados): ?><i class="dot"></i><?php endif; ?>
                </a>
                <a class="mini-avatar" href="?p=perfil" aria-label="Abrir configurações"><?= e($sb['iniciais']) ?></a>
            </div>
        </header>

        <main class="dash-content">
            <div class="dash-top">
                <section class="hero" aria-label="Saldo disponível">
                    <span class="lbl">Saldo disponível</span>
                    <strong class="saldo"><?= e($M($saldo)) ?></strong>
                    <div class="hero-cols">
                        <div><span class="lbl">Entradas do mês</span><b class="pos"><?= e($M($entradasMes, true)) ?></b></div>
                        <div><span class="lbl">Saídas do mês</span><b class="neg"><?= e($M(-$saidasMes)) ?></b></div>
                    </div>
                </section>

                <section class="dash-card reserva" aria-label="Reserva fiscal">
                    <div class="reserva-head">
                        <div>
                            <span class="lbl">Reserva Fiscal (DAS)</span>
                            <strong class="pct"><?= (int) $reserva['pct'] ?>%</strong>
                        </div>
                        <span class="ico" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M12 11v5M9.5 13.5h5"/></svg>
                        </span>
                    </div>
                    <div class="bar-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $reserva['pct'] ?>" aria-label="Reserva fiscal">
                        <i style="width: <?= (int) $reserva['pct'] ?>%"></i>
                    </div>
                    <?php if ($reserva['tem_meta']): ?>
                        <small>Meta: <b><?= e($M($reserva['meta'])) ?></b> até <?= e($reserva['data']) ?></small>
                        <small class="det">Reservado: <?= e($M($reserva['reservado'])) ?> (<?= e(rtrim(rtrim(number_format($reserva['aliquota'], 2, ',', ''), '0'), ',')) ?>% das entradas do mês)</small>
                    <?php else: ?>
                        <small>Sem imposto agendado. Cadastre o DAS em Transações como saída marcada como imposto.</small>
                    <?php endif; ?>
                </section>
            </div>

            <section class="dash-card chart" aria-labelledby="t-fluxo">
                <h2 id="t-fluxo">Fluxo de caixa — próximos 30 dias</h2>
                <p class="sub">Entradas projetadas por semana</p>
                <div class="chart-body" role="img" aria-label="<?= e('Entradas projetadas por semana. ' . $descSemanas) ?>">
                    <div class="y-axis" aria-hidden="true">
                        <?php foreach (array_reverse($escala['marcas']) as $m): ?>
                            <span><?= e(TransactionService::rotuloEixo($m)) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="cols" style="--n: <?= count($semanas) ?>">
                        <?php foreach ($semanas as $s):
                            $h = $s['total'] > 0 ? max(3, round($s['total'] * 100 / $escala['topo'], 1)) : 0; ?>
                            <div class="col-bar">
                                <i class="bar<?= $s['atual'] ? ' now' : '' ?>" style="--h: <?= e($h) ?>" title="<?= e($M($s['total'])) ?>"></i>
                                <span><?= e($s['rotulo']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="list-head">
                <h2>Próximos lançamentos</h2>
                <span>
                    <a href="?p=transacoes">Ver todos</a>
                    <a class="cta" href="?p=transacoes#novo">+ Novo lançamento</a>
                </span>
            </div>
            <?php if (!$lancamentos): ?>
                <section class="dash-card vazio">
                    <strong>Você ainda não tem lançamentos.</strong>
                    <p>Cadastre suas entradas e saídas para ver o saldo, o fluxo de caixa e a reserva fiscal aqui.</p>
                    <a class="cta" href="?p=transacoes#novo">+ Novo lançamento</a>
                </section>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="lanc">
                        <thead><tr><th>Cliente / Descrição</th><th>Data</th><th>Valor</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($lancamentos as $l):
                            [$rotulo, $cls] = TransactionService::status($l, $hojeStr);
                            $c = TransactionService::centavos($l['valor']); ?>
                            <tr>
                                <td><?= e($l['descricao']) ?></td>
                                <td class="mut"><?= e((new DateTimeImmutable($l['data']))->format('d/m')) ?></td>
                                <td class="<?= $l['tipo'] === 'entrada' ? 'pos' : 'neg-t' ?>"><?= e($l['tipo'] === 'entrada' ? $M($c, true) : $M(-$c)) ?></td>
                                <td><span class="pill <?= e($cls) ?>"><?= e($rotulo) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<a class="fab" href="?p=suporte" aria-label="Abrir suporte">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
</a>

<?php view('Dashboard', ob_get_clean());
