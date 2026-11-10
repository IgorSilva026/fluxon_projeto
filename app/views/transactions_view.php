<?php
$M = fn(int $c, bool $s = false) => TransactionService::moeda($c, $s);
$val = fn(string $k, string $d = '') => e($old[$k] ?? $d);
$abas = ['todos' => 'Todos', 'pendentes' => 'Pendentes', 'atrasados' => 'Atrasados', 'concluidos' => 'Concluídos'];
ob_start();
?>
<link rel="stylesheet" href="assets/css/settings.css">
<link rel="stylesheet" href="assets/css/dashboard.css">

<div class="settings-shell">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <div class="workspace">
        <header class="topbar">
            <h1>Transações</h1>
            <div>
                <a class="mini-avatar" href="?p=perfil" aria-label="Abrir configurações"><?= e($sb['iniciais']) ?></a>
            </div>
        </header>

        <main class="dash-content">
            <section class="dash-card" id="novo">
                <h2 class="card-title">Novo lançamento</h2>
                <form method="post" action="?p=transacoes&amp;filtro=<?= e($filtro) ?>&amp;pg=<?= (int) $pagina ?>" class="lanc-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="acao" value="novo">
                    <label class="f-desc">Descrição
                        <input name="descricao" maxlength="120" required placeholder="Ex.: Cliente Horizonte Design" value="<?= $val('descricao') ?>">
                    </label>
                    <label>Tipo
                        <select name="tipo">
                            <option value="entrada" <?= ($old['tipo'] ?? 'entrada') === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                            <option value="saida" <?= ($old['tipo'] ?? '') === 'saida' ? 'selected' : '' ?>>Saída</option>
                        </select>
                    </label>
                    <label>Valor (R$)
                        <input name="valor" inputmode="decimal" required placeholder="0,00" value="<?= $val('valor') ?>">
                    </label>
                    <label>Data
                        <input type="date" name="data" required value="<?= $val('data', $hoje->format('Y-m-d')) ?>">
                    </label>
                    <label>Situação
                        <select name="concluido">
                            <option value="0" <?= ($old['concluido'] ?? '0') === '0' ? 'selected' : '' ?>>Pendente</option>
                            <option value="1" <?= ($old['concluido'] ?? '') === '1' ? 'selected' : '' ?>>Já recebido / pago</option>
                        </select>
                    </label>
                    <label class="chk">
                        <input type="checkbox" name="imposto" value="1" <?= ($old['imposto'] ?? '') === '1' ? 'checked' : '' ?>>
                        É imposto (ex.: DAS). Vale só para saídas.
                    </label>
                    <button class="save" type="submit">Adicionar</button>
                </form>
            </section>

            <nav class="tabs" aria-label="Filtrar lançamentos">
                <?php foreach ($abas as $k => $rotulo): ?>
                    <a href="?p=transacoes&amp;filtro=<?= e($k) ?>" <?= $k === $filtro ? 'class="on" aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php if (!$linhas): ?>
                <section class="dash-card vazio">
                    <strong>Nenhum lançamento <?= $filtro === 'todos' ? 'ainda' : 'neste filtro' ?>.</strong>
                    <p>Use o formulário acima para cadastrar.</p>
                </section>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="lanc">
                        <thead><tr><th>Descrição</th><th>Data</th><th>Valor</th><th>Status</th><th><span class="sr">Ações</span></th></tr></thead>
                        <tbody>
                        <?php foreach ($linhas as $l):
                            [$rotulo, $cls] = TransactionService::status($l, $hojeStr);
                            $c = TransactionService::centavos($l['valor']);
                            $entrada = $l['tipo'] === 'entrada';
                            $acaoRotulo = (int) $l['concluido'] === 1 ? 'Reabrir' : ($entrada ? 'Marcar recebido' : 'Marcar pago'); ?>
                            <tr>
                                <td><?= e($l['descricao']) ?><?php if ($l['categoria'] === 'imposto'): ?> <span class="tag">Imposto</span><?php endif; ?></td>
                                <td class="mut"><?= e((new DateTimeImmutable($l['data']))->format('d/m/Y')) ?></td>
                                <td class="<?= $entrada ? 'pos' : 'neg-t' ?>"><?= e($entrada ? $M($c, true) : $M(-$c)) ?></td>
                                <td><span class="pill <?= e($cls) ?>"><?= e($rotulo) ?></span></td>
                                <td class="acoes">
                                    <form method="post" action="?p=transacoes&amp;filtro=<?= e($filtro) ?>&amp;pg=<?= (int) $pagina ?>">
                                        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                        <button class="lnk" name="acao" value="alternar"><?= e($acaoRotulo) ?></button>
                                        <button class="lnk del" name="acao" value="excluir" onclick="return confirm('Excluir este lançamento?')">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($paginas > 1): ?>
                    <p class="pager">
                        <?php if ($pagina > 1): ?><a href="?p=transacoes&amp;filtro=<?= e($filtro) ?>&amp;pg=<?= $pagina - 1 ?>">← Anterior</a><?php endif; ?>
                        <span>Página <?= (int) $pagina ?> de <?= (int) $paginas ?></span>
                        <?php if ($pagina < $paginas): ?><a href="?p=transacoes&amp;filtro=<?= e($filtro) ?>&amp;pg=<?= $pagina + 1 ?>">Próxima →</a><?php endif; ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php view('Transações', ob_get_clean());
