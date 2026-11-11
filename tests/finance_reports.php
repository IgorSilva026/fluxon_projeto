<?php
require_once __DIR__.'/../app/services/FinanceReport.php';
$target=$argv[1]??sys_get_temp_dir();
$rows=[];
for($i=0;$i<65;$i++){
    $rows[]=['occurred_on'=>'2026-11-10','description'=>$i===0?'=SUM(A1:A2) descrição com ação (teste)':'Serviço de manutenção '.$i,
        'account'=>'Conta demonstração','category'=>'Serviços','kind'=>$i%2?'saida':'entrada','reversed_at'=>null,'amount_cents'=>12345+$i];
}
$meta=['date'=>'10/11/2026 10:00 -03','filters'=>'Filtros: conta Demonstração; categoria Serviços; período 01/11/2026 a 10/11/2026; situação todos.',
    'totals'=>'Registros: 65 | Entradas: R$ 100,00 | Saídas: R$ 50,00 | Resultado: R$ 50,00'];
file_put_contents($target.'/report.pdf',FinanceReport::pdf($rows,$meta));
file_put_contents($target.'/report.xlsx',FinanceReport::xlsx($rows,$meta));
file_put_contents($target.'/empty.pdf',FinanceReport::pdf([],$meta));
file_put_contents($target.'/empty.xlsx',FinanceReport::xlsx([],$meta));
echo "Relatórios de teste gerados.\n";
