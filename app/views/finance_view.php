<?php
$money = FinanceReport::money(...);
function fi(string $name,string $label,string $type='text',string $value='',string $attributes=''): void
{
    echo '<label>'.e($label).'<input name="'.e($name).'" type="'.e($type).'" value="'.e($value).'" '.$attributes.'></label>';
}
function fs(string $name,string $label,array $items,?string $selected=null,bool $optional=false): void
{
    echo '<label>'.e($label).'<select name="'.e($name).'" '.($optional?'':'required').'>';
    echo '<option value="">'.($optional?'Todas':'Selecione').'</option>';
    foreach ($items as $item) {
        echo '<option value="'.e($item['id']).'" '.((string) $item['id']===$selected?'selected':'').'>'.e($item['name']).'</option>';
    }
    echo '</select></label>';
}
function ft(): void
{
    echo '<input type="hidden" name="csrf" value="'.e(csrf()).'">';
}
function fh(string $name,string|int $value): void
{
    echo '<input type="hidden" name="'.e($name).'" value="'.e($value).'">';
}
function financeChart(array $data,string $caption): void
{
    echo '<figure class="finance-chart"><figcaption>'.e($caption).'</figcaption>';
    if (!$data) {
        echo '<p>Nenhuma movimentação ativa para os filtros selecionados.</p></figure>';
        return;
    }
    $max = max(1,...array_map(static fn($r)=>max((int)$r['incoming'],(int)$r['outgoing']),$data));
    echo '<p>Entradas: barras claras. Saídas: barras escuras. Valores em reais.</p>';
    foreach ($data as $row) {
        echo '<div class="chart-row"><strong>'.e($row['label']).'</strong>';
        foreach (['incoming'=>'Entradas','outgoing'=>'Saídas'] as $key=>$label) {
            $value = (int) $row[$key];
            echo '<div class="bar-line"><span class="bar '.$key.'" aria-hidden="true" style="width:'.round($value/$max*100,2).'%"></span><span>'.e($label.': '.FinanceReport::money($value)).'</span></div>';
        }
        echo '</div>';
    }
    echo '</figure>';
}
ob_start();
?>
<?php
$shellName = $user['nome'];
$shellTitle = $p === 'painel' ? 'Dashboard' : $title;
require __DIR__ . '/dashboard_header.php';
?>
        <main class="finance-shell dashboard-content">
            <section class="dashboard-welcome">
                <h2>Bem-vindo ao Fluxon</h2>
                <p>Olá, <strong><?= e($user['nome']) ?></strong>!</p>
                <p><?= e($user['email']) ?></p>
                <p>Ambiente acadêmico com contas e movimentações simuladas.</p>
                <p class="finance-note">As reservas são virtuais. O Fluxon não movimenta dinheiro nem apura ou paga tributos.</p>
            </section>
    <?php if ($err !== ''): ?>
        <p>Confira os dados ou a instalação do módulo e tente novamente.</p>
        <a href="<?= e(financeUrl('painel')) ?>">Voltar ao painel</a>
    <?php elseif ($p==='cadastros'): ?>
        <nav class="finance-nav" aria-label="Tipos de cadastro">
            <?php foreach ($groups as $key=>$label): ?>
                <a href="<?= e(financeUrl('cadastros',['grupo'=>$key])) ?>" <?= $group===$key?'aria-current="page"':'' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($detail): ?>
            <section><h2>Detalhamento</h2><dl>
                <dt>Nome</dt><dd><?= e($detail['name']) ?></dd>
                <?php if ($group==='contas'): ?>
                    <dt>Saldo inicial</dt><dd><?= e($money($detail['opening_cents'])) ?></dd>
                    <dt>Conexão simulada</dt><dd><?= $detail['connected']?'Conectada':'Revogada' ?></dd>
                    <dt>Última sincronização</dt><dd><?= e($detail['last_synced_at']?:'Ainda não realizada') ?></dd>
                <?php elseif ($group==='regras'): ?>
                    <dt>Percentual</dt><dd><?= e(number_format($detail['rate_bp']/100,2,',','.')) ?>%</dd>
                    <dt>Vencimento planejado</dt><dd><?= e($detail['due_date'] ?: 'Não informado') ?></dd>
                    <dt>Situação</dt><dd><?= $detail['active']?'Ativa':'Inativa' ?></dd>
                <?php endif; ?>
            </dl></section>
        <?php endif; ?>
        <section>
            <h2><?= $edit?'Editar registro':'Novo registro' ?></h2>
            <form method="post" class="finance-form">
                <?php ft();fh('acao','salvar'); if ($edit) fh('id',$edit['id']); ?>
                <?php fi('nome','Nome','text',$edit['name']??'','required maxlength="80"'); ?>
                <?php if ($group==='contas'): ?>
                    <?php fi('valor','Saldo inicial (R$)','number',isset($edit['opening_cents'])?number_format($edit['opening_cents']/100,2,'.',''):'0.00','required min="0" max="999999999.99" step="0.01"'); ?>
                    <label>Conexão simulada<select name="conectada"><option value="1">Conectada</option><option value="0" <?= $edit && !$edit['connected']?'selected':'' ?>>Revogada</option></select></label>
                    <p>O saldo inicial deve ser anterior às movimentações cadastradas. Alterá-lo modifica a base do saldo atual.</p>
                <?php elseif ($group==='regras'): ?>
                    <?php fi('percentual','Percentual por entrada (%)','number',isset($edit['rate_bp'])?number_format($edit['rate_bp']/100,2,'.',''):'','required min="0.01" max="100" step="0.01"'); ?>
                    <?php fi('vencimento','Vencimento planejado (opcional)','date',$edit['due_date']??''); ?>
                    <label class="check"><input type="checkbox" name="ativa" value="1" <?= (!$edit || $edit['active'])?'checked':'' ?>> Regra ativa</label>
                    <p>A soma das regras ativas não pode exceder 100%. Alterações se aplicam às próximas entradas. O histórico mantém finalidade e percentual originais.</p>
                <?php endif; ?>
                <button class="btn" type="submit">Salvar</button>
                <?php if ($edit): ?><a href="<?= e(financeUrl('cadastros',['grupo'=>$group])) ?>">Cancelar edição</a><?php endif; ?>
            </form>
        </section>
        <form method="get" class="filters">
            <?php fh('p','cadastros');fh('grupo',$group);fi('busca','Buscar nome','search',$search??'','maxlength="80"'); ?>
            <label>Ordem<select name="ordem"><option value="asc">Nome A–Z</option><option value="desc" <?= $get('ordem')==='desc'?'selected':'' ?>>Nome Z–A</option></select></label>
            <button class="btn" type="submit">Consultar</button>
        </form>
        <div class="table-scroll"><table>
            <caption>Registros encontrados: <?= count($items) ?></caption>
            <thead><tr><th scope="col">Nome</th><th scope="col">Informação</th><th scope="col">Ações</th></tr></thead>
            <tbody><?php foreach ($items as $item): ?><tr>
                <td><?= e($item['name']) ?></td>
                <td><?= $group==='contas'?e($money($item['opening_cents'])):($group==='regras'?e(number_format($item['rate_bp']/100,2,',','.').'% • '.($item['active']?'Ativa':'Inativa')):'Categoria de movimentação') ?></td>
                <td>
                    <a href="<?= e(financeUrl('cadastros',['grupo'=>$group,'detalhe'=>$item['id']])) ?>">Detalhes</a>
                    <a href="<?= e(financeUrl('cadastros',['grupo'=>$group,'editar'=>$item['id']])) ?>">Editar</a>
                    <details><summary><?= $group==='regras'?'Desativar':'Excluir' ?></summary>
                        <form method="post"><?php ft();fh('acao','remover');fh('id',$item['id']); ?>
                            <label class="check"><input name="confirmar" type="checkbox" value="1" required>
                                <?= $group==='regras'?'Confirmo a desativação, preservando o histórico.':'Confirmo a exclusão. Registros com movimentações serão bloqueados.' ?>
                            </label>
                            <button type="submit" class="btn sec">Confirmar</button>
                        </form>
                    </details>
                </td>
            </tr><?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="3">Nenhum registro encontrado.</td></tr><?php endif; ?></tbody>
        </table></div>
    <?php elseif ($p==='movimentacao'): ?>
        <?php $editing = !$detail || $get('editar')==='1'; ?>
        <?php if ($detail): ?>
            <section><h2>Detalhes da movimentação</h2><dl>
                <dt>Descrição</dt><dd><?= e($detail['description']) ?></dd>
                <dt>Conta / categoria</dt><dd><?= e($detail['account'].' / '.$detail['category']) ?></dd>
                <dt>Data / tipo / valor</dt><dd><?= e($detail['occurred_on'].' / '.$detail['kind'].' / '.$money($detail['amount_cents'])) ?></dd>
                <dt>Situação / origem</dt><dd><?= $detail['reversed_at']?'Estornada':'Confirmada' ?> / <?= $detail['external_ref']?'Importação simulada':'Cadastro manual' ?></dd>
            </dl>
            <h3>Reservas vinculadas</h3>
            <?php if (!$provisions): ?><p>Esta movimentação não possui reservas.</p><?php endif; ?>
            <?php foreach ($provisions as $reserve): ?>
                <p><?= e($reserve['purpose'].' — '.number_format($reserve['rate_bp']/100,2,',','.').'% — '.$money($reserve['amount_cents']).' — vencimento: '.($reserve['due_date']?:'não informado')) ?><?= $detail['reversed_at']?' (inativa por estorno)':($reserve['released_at']?' (liberada)':' (ativa)') ?></p>
                <?php if (!$detail['reversed_at'] && !$reserve['released_at']): ?>
                    <details><summary>Liberar reserva</summary>
                        <p>Libera integralmente esta reserva virtual sem criar uma saída. Se a obrigação foi paga, cadastre a saída no extrato e libere a reserva para não comprometer o mesmo valor duas vezes. O histórico permanece disponível.</p>
                        <form method="post"><?php ft();fh('acao','liberar');fh('id',$reserve['id']); ?>
                            <label class="check"><input name="confirmar" type="checkbox" value="1" required>Confirmo a liberação integral.</label>
                            <button class="btn sec" type="submit">Liberar</button>
                        </form>
                    </details>
                <?php endif; ?>
            <?php endforeach; ?>
            </section>
        <?php endif; ?>
        <?php if ($editing && (!$detail || !$detail['reversed_at'])): ?>
            <form method="post" class="finance-form">
                <h2><?= $detail?'Editar movimentação':'Nova movimentação confirmada' ?></h2>
                <?php ft();fh('acao','salvar');if ($detail) fh('id',$detail['id']); ?>
                <?php fi('descricao','Descrição','text',$detail['description']??'','required maxlength="160"'); ?>
                <?php if (!$detail): ?>
                    <?php fs('conta','Conta',$accounts); ?>
                    <label>Tipo<select name="tipo" required><option value="entrada">Entrada</option><option value="saida">Saída</option></select></label>
                <?php endif; ?>
                <?php fs('categoria','Categoria',$categories,isset($detail['category_id'])?(string)$detail['category_id']:null); ?>
                <?php fi('valor','Valor (R$)','number',isset($detail['amount_cents'])?number_format($detail['amount_cents']/100,2,'.',''):'','required min="0.01" max="999999999.99" step="0.01" '.(!empty($detail['external_ref'])?'readonly':'')); ?>
                <?php fi('data','Data','date',$detail['occurred_on']??date('Y-m-d'),'required max="'.date('Y-m-d').'" '.(!empty($detail['external_ref'])?'readonly':'')); ?>
                <p>Conta e tipo são preservados na edição. Movimentações importadas permitem ajustar apenas descrição e categoria. Entradas geram reservas conforme as regras ativas no cadastro.</p>
                <button class="btn" type="submit">Salvar movimentação</button>
            </form>
        <?php elseif ($detail && !$detail['reversed_at']): ?>
            <a href="<?= e(financeUrl('movimentacao',['id'=>$detail['id'],'editar'=>'1'])) ?>">Editar</a>
        <?php endif; ?>
        <?php if ($detail && !$detail['reversed_at']): ?>
            <details><summary><?= $detail['external_ref']?'Estornar movimentação':'Excluir movimentação' ?></summary>
                <p><?= $detail['external_ref']?'O estorno preserva o histórico e retira os valores e reservas dos totais.':'A exclusão remove a movimentação manual e suas reservas vinculadas.' ?></p>
                <form method="post"><?php ft();fh('acao','remover');fh('id',$detail['id']); ?>
                    <label class="check"><input name="confirmar" value="1" type="checkbox" required>Confirmo esta operação.</label>
                    <button class="btn sec" type="submit">Confirmar</button>
                </form>
            </details>
        <?php endif; ?>
        <a href="<?= e(financeUrl('extrato')) ?>">Voltar ao extrato</a>
    <?php else: ?>
        <form method="get" class="filters">
            <?php fh('p',$p);fi('busca','Buscar descrição, conta ou categoria','search',$filters['busca'],'maxlength="80"'); ?>
            <?php fi('de','De','date',$filters['de']??'');fi('ate','Até','date',$filters['ate']??''); ?>
            <?php fs('conta','Conta',$accounts,$filters['conta']?(string)$filters['conta']:null,true);fs('categoria','Categoria',$categories,$filters['categoria']?(string)$filters['categoria']:null,true); ?>
            <label>Tipo<select name="tipo"><option value="">Todos</option><?php foreach (['entrada'=>'Entrada','saida'=>'Saída'] as $key=>$label): ?><option value="<?= e($key) ?>" <?= $filters['tipo']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label>Situação<select name="status"><?php foreach (['todos'=>'Todas','ativos'=>'Confirmadas','estornados'=>'Estornadas'] as $key=>$label): ?><option value="<?= e($key) ?>" <?= $filters['status']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label>Ordenação<select name="ordem"><?php foreach (['recentes'=>'Data mais recente','antigos'=>'Data mais antiga','maior'=>'Maior valor','menor'=>'Menor valor'] as $key=>$label): ?><option value="<?= e($key) ?>" <?= $filters['ordem']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <button class="btn" type="submit">Aplicar filtros</button>
            <a href="<?= e(financeUrl($p)) ?>">Limpar filtros</a>
        </form>
        <?php if ($p==='painel'): ?>
            <section><h2>Posição atual da conta <?= e($accountName) ?></h2>
                <p>Considera todo o histórico confirmado da conta selecionada. Período, categoria, busca e situação filtram os gráficos e os totais de consulta abaixo, não esta posição atual.</p>
                <dl class="finance-kpis">
                    <div><dt>Saldo registrado</dt><dd><?= e($money($balance)) ?></dd></div>
                    <div><dt>Reservas ativas</dt><dd><?= e($money($reserved)) ?></dd></div>
                    <div><dt>Disponível estimado</dt><dd><?= e($money($balance-$reserved)) ?></dd></div>
                </dl>
                <p>Disponível = saldo inicial + entradas − saídas − reservas ativas. Não inclui obrigações futuras ainda não registradas. Reservas não são pagamentos e permanecem no saldo registrado.</p>
                <?php foreach ($accounts as $a): if (!$a['connected'] && (!$filters['conta'] || $filters['conta']===$a['id'])): ?>
                    <p class="finance-note"><?= e('Conexão simulada revogada: '.$a['name'].'. A importação está bloqueada até reconectar.') ?></p>
                <?php endif; endforeach; ?>
                <?php if ($balance-$reserved<0): ?><p role="alert" class="err">Saldo disponível negativo: o planejamento supera os recursos registrados.</p><?php endif; ?>
                <?php foreach ($due as $r): ?><p><?= e('Reserva com vencimento até os próximos sete dias: '.$r['purpose'].' — '.$r['due_date'].' — '.$money($r['amount_cents'])) ?></p><?php endforeach; ?>
            </section>
            <section><h2>Gráficos da consulta</h2>
                <?php financeChart($monthly,'Entradas e saídas por mês (até 12 meses mais recentes da consulta)'); ?>
                <?php financeChart($expenses,'Saídas por categoria (até 10 categorias com maior valor)'); ?>
            </section>
            <form method="post"><?php ft(); ?><button class="btn sec" type="submit">Sair da conta</button></form>
        <?php else: ?>
            <nav class="finance-nav" aria-label="Operações">
                <a href="<?= e(financeUrl('movimentacao')) ?>">Nova movimentação</a>
                <?php foreach (['pdf'=>'Exportar PDF','xlsx'=>'Exportar XLSX'] as $key=>$label): ?>
                    <a href="<?= e(financeUrl('extrato',array_filter($filters,static fn($v)=>$v!==null && $v!=='')+['exportar'=>$key])) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <details><summary>Importar demonstração bancária simulada</summary>
                <p>Adiciona uma entrada de R$ 2.500,00 e uma saída de R$ 420,00 à conta selecionada. Repetir a importação não duplica os registros, inclusive após estorno. Crie regras antes de importar para gerar reservas.</p>
                <form method="post" class="finance-form"><?php ft();fh('acao','importar');fs('conta','Conta',$accounts);fs('categoria','Categoria',$categories); ?>
                    <button type="submit" class="btn">Importar dados fictícios</button>
                </form>
            </details>
            <div class="table-scroll"><table>
                <caption>Movimentações filtradas: <?= e($totals['total']) ?></caption>
                <thead><tr><?php foreach (['Data','Descrição','Conta','Categoria','Tipo','Situação','Valor','Ação'] as $heading): ?><th scope="col"><?= e($heading) ?></th><?php endforeach; ?></tr></thead>
                <tbody><?php foreach ($entries as $entry): ?><tr>
                    <td><?= e($entry['occurred_on']) ?></td><td><?= e($entry['description']) ?></td>
                    <td><?= e($entry['account']) ?></td><td><?= e($entry['category']) ?></td>
                    <td><?= e($entry['kind']) ?></td><td><?= $entry['reversed_at']?'Estornada':'Confirmada' ?></td>
                    <td><?= e($money($entry['amount_cents'])) ?></td><td><a href="<?= e(financeUrl('movimentacao',['id'=>$entry['id']])) ?>">Detalhes</a></td>
                </tr><?php endforeach; ?>
                <?php if (!$entries): ?><tr><td colspan="8">Nenhuma movimentação encontrada.</td></tr><?php endif; ?></tbody>
            </table></div>
            <nav class="finance-nav" aria-label="Paginação">
                <span>Página <?= $page ?> de <?= $maxPage ?> (20 registros por página)</span>
                <?php if ($page>1): ?><a href="<?= e(financeUrl('extrato',array_filter($filters,static fn($v)=>$v!==null && $v!=='')+['pagina'=>$page-1])) ?>">Anterior</a><?php endif; ?>
                <?php if ($page<$maxPage): ?><a href="<?= e(financeUrl('extrato',array_filter($filters,static fn($v)=>$v!==null && $v!=='')+['pagina'=>$page+1])) ?>">Próxima</a><?php endif; ?>
            </nav>
        <?php endif; ?>
        <section><h2>Totais da consulta completa</h2>
            <p>Entradas: <?= e($money($totals['incoming'])) ?>. Saídas: <?= e($money($totals['outgoing'])) ?>.
                Resultado: <?= e($money((int)$totals['incoming']-(int)$totals['outgoing'])) ?>.</p>
            <p>Estornos aparecem na consulta quando selecionados, mas não compõem os totais. Exportações usam todos os resultados filtrados, até 10.000 registros.</p>
        </section>
    <?php endif; ?>
</main>
    </div>
</div>
<?php view($title,ob_get_clean()); ?>
