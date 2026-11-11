<?php
require_once __DIR__.'/../app/services/FinanceInput.php';
$checks = 0;
function check(bool $ok,string $name): void {
    global $checks;
    if (!$ok) throw new RuntimeException($name);
    $checks++;
}
function rejects(callable $fn): void {
    try {$fn();} catch (InvalidArgumentException $e) {return;}
    throw new RuntimeException('Entrada inválida foi aceita.');
}
check(FinanceInput::scaled('123,45',1,99999999999)===12345,'Centavos exatos');
check(FinanceInput::scaled('0.01',1,10000)===1,'Um centavo');
check(FinanceInput::scaled('100',1,10000)===10000,'Percentual');
foreach (['1e2','-1','1.234','0','NaN','1,234.56'] as $bad) rejects(fn()=>FinanceInput::scaled($bad,1,10000));
rejects(fn()=>FinanceInput::date('2026-02-30'));
rejects(fn()=>FinanceInput::id('1 OR 1=1'));
rejects(fn()=>FinanceInput::filters(['de'=>'2026-12-01','ate'=>'2026-01-01']));
rejects(fn()=>FinanceInput::name("abc\ninvalid",80));
check(FinanceInput::filters(['ordem'=>'malicious SQL'])['ordem']==='recentes','Lista permitida');
echo "Validações financeiras aprovadas: $checks verificações e entradas inválidas recusadas.\n";
