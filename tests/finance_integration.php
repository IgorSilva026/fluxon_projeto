<?php
// Rodar somente em banco descartável, com schema.sql e migracao_fase3.sql instalados.
// FLUXON_TEST_DSN, FLUXON_TEST_USER e FLUXON_TEST_PASS nunca apontam para produção.
require_once __DIR__.'/../app/services/FinanceInput.php';
require_once __DIR__.'/../app/repositories/FinanceRepository.php';
if (!isset($db)) {
    $dsn = getenv('FLUXON_TEST_DSN');
    if (!$dsn) {
        exit("Defina FLUXON_TEST_DSN para um PostgreSQL descartável.\n");
    }
    $db = new PDO($dsn,getenv('FLUXON_TEST_USER'),getenv('FLUXON_TEST_PASS'),[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    ]);
}
function expect(bool $ok,string $label): void {
    if (!$ok) throw new RuntimeException('FALHOU: '.$label);
    echo "OK: $label\n";
}
function denied(callable $op): void {
    try {$op();} catch (DomainException|PDOException $e) {return;}
    throw new RuntimeException('Operação indevida aceita.');
}
$marker=bin2hex(random_bytes(8));
$ids=[];
try {
    foreach (['a','b'] as $label) {
        $q=$db->prepare('INSERT INTO users(nome,email,email_verificado_em) VALUES(?,?,now()) RETURNING id');
        $q->execute(["Teste $label","finance-$marker-$label@example.invalid"]);
        $ids[]=(int)$q->fetchColumn();
    }
    $r=new FinanceRepository($db,$ids[0]);
    $other=new FinanceRepository($db,$ids[1]);
    $r->saveItem('contas',null,['nome'=>'Conta teste','valor'=>'100.00']);
    $r->saveItem('categorias',null,['nome'=>'Serviços']);
    $r->saveItem('regras',null,['nome'=>'Reserva','percentual'=>'20','ativa'=>'1']);
    $account=(int)$r->catalog('contas')[0]['id'];
    $category=(int)$r->catalog('categorias')[0]['id'];
    $rule=(int)$r->catalog('regras')[0]['id'];
    $input=['conta'=>(string)$account,'categoria'=>(string)$category,'descricao'=>'Serviço teste','tipo'=>'entrada','valor'=>'100.00','data'=>date('Y-m-d')];
    $r->saveEntry(null,$input);
    $f=FinanceInput::filters([]);
    $entry=(int)$r->rows($f,0,20)->fetch()['id'];
    expect($r->balances(null)===[20000,2000],'Saldo inicial, entrada e reserva exatos');
    $r->saveItem('regras',$rule,['nome'=>'Novo nome','percentual'=>'30','ativa'=>'1']);
    expect((int)$r->reserves($entry)[0]['rate_bp']===2000,'Alterar regra preserva percentual histórico');
    $r->saveEntry($entry,$input+[]); // mantém percentuais históricos
    $input['valor']='50.00';
    $r->saveEntry($entry,$input);
    expect($r->balances(null)===[15000,1000],'Editar valor recalcula reserva com percentual histórico');
    denied(fn()=>$r->saveItem('regras',null,['nome'=>'Excesso','percentual'=>'80','ativa'=>'1']));
    denied(fn()=>$r->removeItem('contas',$account));
    denied(fn()=>$r->removeItem('categorias',$category));
    denied(fn()=>$other->entry($entry));
    denied(fn()=>$other->saveEntry(null,$input));
    expect($r->catalog('contas')!==[] && $other->catalog('contas')===[],'Isolamento entre usuários');
    $reserveId=(int)$r->reserves($entry)[0]['id'];
    denied(fn()=>$other->releaseReserve($reserveId));
    $r->releaseReserve($reserveId);
    expect($r->balances(null)===[15000,0],'Liberar reserva preserva saldo e libera disponibilidade');
    $badAmount=$input;$badAmount['valor']='70.00';
    denied(fn()=>$r->saveEntry($entry,$badAmount));
    $r->removeItem('regras',$rule);
    expect(count($r->reserves($entry))===1,'Desativação conserva reservas anteriores');
    $r->removeEntry($entry);
    expect($r->reserves($entry)===[],'Excluir manual remove reservas vinculadas');
    $r->saveItem('contas',$account,['nome'=>'Conta teste','valor'=>'100.00','conectada'=>'0']);
    denied(fn()=>$r->importDemo($account,$category));
    $r->saveItem('contas',$account,['nome'=>'Conta teste','valor'=>'100.00','conectada'=>'1']);
    expect($r->importDemo($account,$category)===2,'Importação cria dois registros');
    expect($r->importDemo($account,$category)===0,'Reimportação idempotente');
    $entry=(int)$r->rows(FinanceInput::filters(['tipo'=>'entrada']),0,20)->fetch()['id'];
    $r->removeEntry($entry);
    expect((int)$r->totals($f)['incoming']===0,'Estorno excluído dos totais');
    expect($r->importDemo($account,$category)===0,'Estorno não permite reimportação duplicada');
    $f=FinanceInput::filters(['busca'=>'materiais','tipo'=>'saida','conta'=>(string)$account]);
    expect((int)$r->totals($f)['total']===1,'Filtros combinados');
    expect(count($r->chart($f,'categories'))===1,'Agregação do gráfico');
    // FK composta deve recusar associação de um usuário à conta de outro.
    $db->beginTransaction();
    try {
        $q=$db->prepare("INSERT INTO finance_entries(user_id,account_id,category_id,description,kind,amount_cents,occurred_on) VALUES(?,?,?,'Inválida','entrada',100,CURRENT_DATE)");
        $q->execute([$ids[1],$account,$category]);
        $db->commit();
        throw new RuntimeException('FK composta aceitou vínculo entre usuários.');
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
    }
    expect(true,'Banco recusa associação entre usuários');
    // Paginação real: 25 entradas adicionais, além de duas importadas.
    for ($i=0;$i<25;$i++) $r->saveEntry(null,$input);
    expect(count($r->rows(FinanceInput::filters([]),0,20)->fetchAll())===20,'Primeira página com 20 registros');
    expect(count($r->rows(FinanceInput::filters([]),20,20)->fetchAll())===7,'Segunda página com restante');
    $q=$db->prepare('DELETE FROM users WHERE id=?');$q->execute([$ids[0]]);
    $q=$db->prepare('SELECT COUNT(*) FROM finance_entries WHERE user_id=?');$q->execute([$ids[0]]);
    expect((int)$q->fetchColumn()===0,'Excluir usuário remove dados financeiros em cascata');
} finally {
    if ($db->inTransaction()) $db->rollBack();
    foreach ($ids as $id) {$q=$db->prepare('DELETE FROM users WHERE id=?');$q->execute([$id]);}
}
echo "Integração financeira aprovada.\n";
