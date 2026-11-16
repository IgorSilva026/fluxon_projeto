<?php

require_once __DIR__ . '/../services/FinanceInput.php';
require_once __DIR__ . '/../services/FinanceReport.php';
require_once __DIR__ . '/../repositories/FinanceRepository.php';

if (empty($_SESSION['uid'])) {
    go('login');
}
$owner = (int) $_SESSION['uid'];
$lookup = $db->prepare('SELECT nome,email,email_verificado_em,versao_sessao FROM users WHERE id=?');
$lookup->execute([$owner]);
$user = $lookup->fetch();
if (!$user || !$user['email_verificado_em'] || (int) $user['versao_sessao'] !== ($_SESSION['versao_sessao'] ?? -1)) {
    session_destroy();
    go('login');
}
$repo = new FinanceRepository($db,$owner);
$groups = ['contas'=>'Contas simuladas','categorias'=>'Categorias','regras'=>'Regras de provisão'];
$get = static fn($key) => FinanceInput::field($_GET,$key);
$post = static fn($key) => FinanceInput::field($_POST,$key);
$group = isset($groups[$get('grupo')]) ? $get('grupo') : 'contas';
$title = match ($p) {
    'painel'=>'Painel financeiro',
    'cadastros'=>$groups[$group],
    'movimentacao'=>'Movimentação',
    default=>'Extrato e relatórios',
};
$nav = ['painel'=>'Painel','cadastros'=>'Cadastros','extrato'=>'Extrato e relatórios','perfil'=>'Configurações'];
$edit = null;
$detail = null;
$filters = FinanceInput::filters([]);
$inTransaction = false;

function financeUrl(string $route, array $args = []): string
{
    return '?' . http_build_query(['p'=>$route] + $args);
}

try {
    if ($POST) {
        $expected = $_SESSION['csrf'] ?? '';
        if ($expected === '' || !hash_equals($expected,$post('csrf'))) {
            http_response_code(403);
            throw new DomainException('Sessão do formulário expirada. Recarregue a página.');
        }
        if ($p === 'painel') {
            session_destroy();
            go('inicio');
        }
        $action = $post('acao');
        $id = FinanceInput::id($post('id'),true);
        $confirm = static function () use ($post): void {
            if ($post('confirmar') !== '1') {
                throw new DomainException('Marque a confirmação antes de concluir a operação.');
            }
        };
        if ($p === 'cadastros' && $action === 'salvar') {
            $repo->saveItem($group,$id,$_POST);
        } elseif ($p === 'cadastros' && $action === 'remover' && $id) {
            $confirm();
            $repo->removeItem($group,$id);
        } elseif ($p === 'movimentacao' && $action === 'salvar') {
            $repo->saveEntry($id,$_POST);
        } elseif ($p === 'movimentacao' && $action === 'liberar' && $id) {
            $confirm();
            $repo->releaseReserve($id);
        } elseif ($p === 'movimentacao' && $action === 'remover' && $id) {
            $confirm();
            $repo->removeEntry($id);
        } elseif ($p === 'extrato' && $action === 'importar') {
            $count = $repo->importDemo(FinanceInput::id($post('conta')),FinanceInput::id($post('categoria')));
        } else {
            throw new DomainException('Operação inválida.');
        }
        flash('ok',isset($count) ? "$count movimentação(ões) simulada(s) importada(s)." : 'Operação concluída.');
        header('Location: ' . financeUrl($p === 'cadastros' ? 'cadastros' : 'extrato',
            $p === 'cadastros' ? ['grupo'=>$group] : []),true,303);
        exit();
    }
    $db->beginTransaction();
    $inTransaction = true;
    $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $accounts = $repo->catalog('contas');
    $categories = $repo->catalog('categorias');
    if ($p === 'cadastros') {
        $items = $group === 'contas' ? $accounts : ($group === 'categorias' ? $categories : $repo->catalog('regras'));
        if ($id = FinanceInput::id($get('editar'),true)) {
            $edit = $repo->item($group,$id);
        }
        if ($id = FinanceInput::id($get('detalhe'),true)) {
            $detail = $repo->item($group,$id);
        }
        $search = $get('busca');
        if ($search !== '') {
            FinanceInput::name($search,80);
            $items = array_values(array_filter($items,static function ($item) use ($search) {
                return preg_match('/'.preg_quote($search,'/').'/iu',$item['name']) === 1;
            }));
        }
        if ($get('ordem') === 'desc') {
            $items = array_reverse($items);
        }
    } elseif ($p === 'movimentacao') {
        if ($id = FinanceInput::id($get('id'),true)) {
            $detail = $repo->entry($id);
            $provisions = $repo->reserves($id);
        }
    } else {
        $filters = FinanceInput::filters($_GET);
        $accountName = $filters['conta'] ? $repo->item('contas',$filters['conta'])['name'] : 'Todas';
        $categoryName = $filters['categoria'] ? $repo->item('categorias',$filters['categoria'])['name'] : 'Todas';
        $totals = $repo->totals($filters);
        if ($p === 'painel') {
            [$balance,$reserved] = $repo->balances($filters['conta']);
            $monthly = array_reverse($repo->chart($filters,'months'));
            $expenses = $repo->chart($filters,'categories');
            $due = $repo->due($filters['conta']);
        } else {
            $maxPage = max(1,(int) ceil($totals['total']/FinanceRepository::PAGE_SIZE));
            $page = min(FinanceInput::id($get('pagina'),true) ?? 1,$maxPage);
            $format = $get('exportar');
            if (in_array($format,['pdf','xlsx'],true)) {
                if ((int) $totals['total'] > FinanceReport::LIMIT) {
                    throw new DomainException('Refine os filtros: o relatório suporta até 10.000 registros por exportação.');
                }
                $money = FinanceReport::money(...);
                $meta = [
                    'date'=>FinanceReport::generatedAt(),
                    'filters'=>'Filtros: período '.($filters['de'] ?? 'início').' a '.($filters['ate'] ?? 'fim').
                        "; conta: $accountName; categoria: $categoryName; tipo: ".($filters['tipo'] ?: 'todos').
                        '; situação: '.$filters['status'].'; busca: '.($filters['busca'] ?: 'nenhuma').
                        '; ordem: '.$filters['ordem'].'.',
                    'totals'=>'Registros: '.$totals['total'].' | Entradas: '.$money($totals['incoming']).
                        ' | Saídas: '.$money($totals['outgoing']).' | Resultado: '.$money((int) $totals['incoming']-(int) $totals['outgoing']),
                ];
                $entries = $repo->rows($filters,0,FinanceReport::LIMIT);
                $report = $format === 'pdf' ? FinanceReport::pdf($entries,$meta) : FinanceReport::xlsx($entries,$meta);
                $db->commit();
                $inTransaction = false;
                header('Content-Type: ' . ($format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
                header('Content-Disposition: attachment; filename="fluxon-relatorio.' . $format . '"');
                header('Content-Length: ' . strlen($report));
                echo $report;
                exit();
            }
            $entries = $repo->rows($filters,($page-1)*FinanceRepository::PAGE_SIZE,FinanceRepository::PAGE_SIZE)->fetchAll();
        }
    }
    $db->commit();
    $inTransaction = false;
} catch (Throwable $ex) {
    if ($inTransaction && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($ex instanceof DomainException || $ex instanceof InvalidArgumentException) {
        $err = $ex->getMessage();
    } elseif ($ex instanceof PDOException && $ex->getCode() === '23505') {
        $err = 'Já existe um registro com esse nome.';
    } elseif ($ex instanceof PDOException && $ex->getCode() === '42P01') {
        $err = 'Módulo financeiro ainda não instalado. Execute a migração da Fase 3 no PostgreSQL.';
    } elseif ($ex instanceof PDOException && $ex->getCode() === '23503') {
        $err = 'A operação foi bloqueada porque há um vínculo obrigatório com outro registro.';
    } else {
        error_log('Fluxon financeiro: ' . get_class($ex) . ' código ' . $ex->getCode());
        $err = 'Não foi possível concluir a operação. Tente novamente ou procure a equipe.';
    }
    if ($POST && http_response_code() !== 403) {
        flash('err',$err);
        $return = match ($p) {
            'cadastros'=>['grupo'=>$group] + (isset($id) && $id ? ['editar'=>$id] : []),
            'movimentacao'=>isset($id) && $id ? ['id'=>$id,'editar'=>'1'] : [],
            default=>[],
        };
        header('Location: ' . financeUrl($p,$return),true,303);
        exit();
    }
}
require __DIR__ . '/../views/finance_view.php';
