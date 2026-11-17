<?php
// Painel no layout do protótipo, alimentado pelo módulo financeiro (Fase 3).
$money = FinanceReport::money(...);
$available = $balance - $reserved;
$reservedPct = PainelService::percentual($reserved, $balance);
$in = (int) $month['incoming'];
$out = (int) $month['outgoing'];
$peak = max(array_merge([0], array_column($weeks, 'entradas'), array_column($weeks, 'saidas')));
$scale = PainelService::escala($peak);
$short = static fn(string $d): string => (new DateTimeImmutable($d))->format('d/m');
$weeksText = implode('; ', array_map(
    static fn($w) => 'semana de ' . $w['rotulo'] . ': entradas ' . FinanceReport::money($w['entradas']) . ', saídas ' . FinanceReport::money($w['saidas']),
    $weeks,
));
// Linhas da tabela: reservas a vencer primeiro, depois as últimas movimentações.
$rows = [];
foreach ($due as $r) {
    $late = $r['due_date'] < $today;
    $label = stripos($r['purpose'], 'reserva') === 0 ? $r['purpose'] : 'Reserva — ' . $r['purpose'];
    $rows[] = [$label, $short($r['due_date']), $money($r['amount_cents']), 'neutral',
        $late ? ['Vencida', 'late'] : ['A vencer', 'wait']];
}
foreach ($recent as $e) {
    $isIn = $e['kind'] === 'entrada';
    $rows[] = [$e['description'], $short($e['occurred_on']),
        ($isIn ? '+ ' : '− ') . $money($e['amount_cents']), $isIn ? 'in' : 'out',
        $e['reversed_at'] ? ['Estornada', 'wait'] : [$isIn ? 'Recebido' : 'Pago', 'ok']];
}
$catMax = max(array_merge([1], array_map(static fn($r) => (int) $r['outgoing'], $expenses)));
$shellName = $user['nome'];
$shellTitle = 'Dashboard';
$shellActions = '<a class="pn-bell" href="#proximos" aria-label="' .
    e($due ? count($due) . ' reserva(s) a vencer ou vencida(s)' : 'Nenhuma reserva a vencer') . '">' .
    '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>' .
    ($due ? '<i class="pn-dot"></i>' : '') . '</a>';
ob_start();
require __DIR__ . '/dashboard_header.php';
?>
        <main class="dashboard-content pn">
            <?php if (!$accounts): ?>
                <p class="pn-notice">Comece cadastrando uma conta simulada em <a href="<?= e(financeUrl('cadastros', ['grupo' => 'contas'])) ?>">Cadastros</a>. Depois registre movimentações ou importe a demonstração em <a href="<?= e(financeUrl('extrato')) ?>">Extrato</a>.</p>
            <?php elseif (!$weeks || ($in + $out === 0 && !$recent)): ?>
                <p class="pn-notice">Ainda não há movimentações. <a href="<?= e(financeUrl('movimentacao')) ?>">Registre uma movimentação</a> ou <a href="<?= e(financeUrl('extrato')) ?>">importe a demonstração</a> para ver os números aqui.</p>
            <?php endif; ?>
            <?php if (count($accounts) > 1): ?>
                <form method="get" class="pn-account">
                    <input type="hidden" name="p" value="painel">
                    <label>Conta
                        <select name="conta">
                            <option value="">Todas as contas</option>
                            <?php foreach ($accounts as $a): ?>
                                <option value="<?= e($a['id']) ?>" <?= (int) $filters['conta'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="pn-btn" type="submit">Ver</button>
                </form>
            <?php endif; ?>

            <div class="pn-top">
                <section class="pn-hero" aria-labelledby="pn-saldo">
                    <span class="pn-lbl" id="pn-saldo">Saldo disponível</span>
                    <strong class="pn-big"><?= e($money($available)) ?></strong>
                    <span class="pn-sub">Saldo registrado <?= e($money($balance)) ?> − reservas <?= e($money($reserved)) ?></span>
                    <div class="pn-cols2">
                        <div><span class="pn-lbl">Entradas do mês</span><b class="pn-pos"><?= e('+ ' . $money($in)) ?></b></div>
                        <div><span class="pn-lbl">Saídas do mês</span><b class="pn-neg"><?= e('− ' . $money($out)) ?></b></div>
                    </div>
                </section>

                <section class="pn-card pn-reserva" aria-labelledby="pn-res">
                    <div class="pn-reserva-head">
                        <div>
                            <span class="pn-lbl" id="pn-res">Reservas ativas</span>
                            <strong class="pn-pct"><?= (int) $reservedPct ?>%</strong>
                        </div>
                        <span class="pn-ico" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M12 11v5M9.5 13.5h5"/></svg>
                        </span>
                    </div>
                    <div class="pn-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $reservedPct ?>" aria-label="Parcela do saldo registrado reservada">
                        <i style="width: <?= (int) $reservedPct ?>%"></i>
                    </div>
                    <small>Reservado: <b><?= e($money($reserved)) ?></b> do saldo registrado</small>
                    <?php
                    $overdue = array_values(array_filter($due, static fn($r) => $r['due_date'] < $today));
                    $upcoming = array_values(array_filter($due, static fn($r) => $r['due_date'] >= $today));
                    ?>
                    <?php if ($overdue): ?>
                        <small class="pn-warn">Vencida: <b><?= e($money(array_sum(array_column($overdue, 'amount_cents')))) ?></b> (<?= count($overdue) ?> reserva(s))</small>
                    <?php endif; ?>
                    <?php if ($upcoming): ?>
                        <small>Próximo vencimento: <b><?= e($money($upcoming[0]['amount_cents'])) ?></b> em <?= e($short($upcoming[0]['due_date'])) ?></small>
                    <?php elseif (!$overdue): ?>
                        <small>Nenhum vencimento nos próximos 7 dias.</small>
                    <?php endif; ?>
                    <small><a href="<?= e(financeUrl('cadastros', ['grupo' => 'regras'])) ?>">Gerenciar regras de reserva</a></small>
                </section>
            </div>
            <?php if ($available < 0): ?><p role="alert" class="err pn-alert">Saldo disponível negativo: o planejamento supera os recursos registrados.</p><?php endif; ?>
            <?php foreach ($accounts as $a): if (!$a['connected'] && (!$filters['conta'] || $filters['conta'] === $a['id'])): ?>
                <p class="pn-notice"><?= e('Conexão simulada revogada: ' . $a['name'] . '. A importação está bloqueada até reconectar.') ?></p>
            <?php endif; endforeach; ?>

            <section class="pn-card pn-chart" aria-labelledby="pn-fluxo">
                <h2 id="pn-fluxo">Fluxo de caixa — últimas 5 semanas</h2>
                <p class="pn-muted">Entradas e saídas confirmadas por semana</p>
                <p class="pn-legend"><span class="in">Entradas</span><span class="out">Saídas</span></p>
                <div class="pn-plot" role="img" aria-label="<?= e('Entradas e saídas por semana. ' . $weeksText) ?>">
                    <div class="pn-y" aria-hidden="true">
                        <?php foreach (array_reverse($scale['marcas']) as $m): ?><span><?= e(PainelService::rotuloEixo($m)) ?></span><?php endforeach; ?>
                    </div>
                    <div class="pn-weeks" style="--n: <?= count($weeks) ?>">
                        <?php foreach ($weeks as $w): ?>
                            <div class="pn-week<?= $w['atual'] ? ' now' : '' ?>">
                                <div class="pn-pair">
                                    <i class="pn-bar in" style="--h: <?= e(PainelService::altura($w['entradas'], $scale['topo'])) ?>" title="<?= e('Entradas: ' . $money($w['entradas'])) ?>"></i>
                                    <i class="pn-bar out" style="--h: <?= e(PainelService::altura($w['saidas'], $scale['topo'])) ?>" title="<?= e('Saídas: ' . $money($w['saidas'])) ?>"></i>
                                </div>
                                <span><?= e($w['rotulo']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="pn-head" id="proximos">
                <h2>Próximos lançamentos</h2>
                <span>
                    <a href="<?= e(financeUrl('extrato')) ?>">Ver extrato</a>
                    <a class="pn-cta" href="<?= e(financeUrl('movimentacao')) ?>">+ Nova movimentação</a>
                </span>
            </div>
            <?php if (!$rows): ?>
                <p class="pn-card pn-empty">Nada a vencer e nenhuma movimentação ainda.</p>
            <?php else: ?>
                <div class="pn-table-wrap">
                    <table class="pn-table">
                        <caption class="pn-sr">Reservas a vencer e últimas movimentações</caption>
                        <thead><tr><th scope="col">Descrição</th><th scope="col">Data</th><th scope="col">Valor</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as [$desc, $date, $value, $tone, $pill]): ?>
                            <tr>
                                <td><?= e($desc) ?><small class="pn-datem"><?= e($date) ?></small></td>
                                <td class="pn-mut"><?= e($date) ?></td>
                                <td class="pn-v <?= e($tone) ?>"><?= e($value) ?></td>
                                <td><span class="pn-pill <?= e($pill[1]) ?>"><?= e($pill[0]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($expenses): ?>
                <section class="pn-card pn-cats" aria-labelledby="pn-cat">
                    <h2 id="pn-cat">Saídas do mês por categoria</h2>
                    <ul>
                        <?php foreach ($expenses as $c): ?>
                            <li>
                                <span><?= e($c['label']) ?></span>
                                <span class="pn-catbar" aria-hidden="true"><i style="width: <?= e(round((int) $c['outgoing'] / $catMax * 100, 1)) ?>%"></i></span>
                                <b><?= e($money($c['outgoing'])) ?></b>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <p class="pn-foot">As reservas são virtuais. O Fluxon não movimenta dinheiro nem apura ou paga tributos.</p>
            <form method="post" class="pn-logout"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><button class="pn-btn" type="submit">Sair da conta</button></form>
        </main>
    </div>
</div>
<?php view('Painel financeiro', ob_get_clean()); ?>
