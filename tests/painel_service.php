<?php
require __DIR__ . '/../app/services/PainelService.php';
function confere($ok, $msg)
{
    if (!$ok) {
        throw new RuntimeException("Falhou: $msg");
    }
}
$hoje = '2026-11-16';

// Semanas: 5 colunas, a última termina hoje (hoje e 6 dias antes)
$s = PainelService::semanas(
    [
        ['occurred_on' => '2026-11-16', 'kind' => 'entrada', 'total' => '10000'], // hoje -> atual
        ['occurred_on' => '2026-11-10', 'kind' => 'saida', 'total' => '2500'],    // 6 dias atrás -> atual
        ['occurred_on' => '2026-11-09', 'kind' => 'entrada', 'total' => '4000'],  // 7 dias atrás -> anterior
        ['occurred_on' => '2026-10-13', 'kind' => 'saida', 'total' => '700'],     // 34 dias -> 1ª coluna
        ['occurred_on' => '2026-10-12', 'kind' => 'entrada', 'total' => '999'],   // 35 dias -> fora
        ['occurred_on' => '2026-11-17', 'kind' => 'entrada', 'total' => '555'],   // futuro -> fora
    ],
    $hoje,
);
confere(count($s) === 5, '5 semanas');
confere($s[4]['atual'] && !$s[3]['atual'], 'só a última é a semana atual');
confere(array_column($s, 'entradas') === [0, 0, 0, 4000, 10000], 'entradas ' . json_encode(array_column($s, 'entradas')));
confere(array_column($s, 'saidas') === [700, 0, 0, 0, 2500], 'saídas ' . json_encode(array_column($s, 'saidas')));
confere($s[4]['rotulo'] === '10/11' && $s[3]['rotulo'] === '3/11' && $s[0]['rotulo'] === '13/10', 'rótulos ' . json_encode(array_column($s, 'rotulo')));
confere(PainelService::semanas([], $hoje, 3) === PainelService::semanas([], $hoje, 3) && count(PainelService::semanas([], $hoje, 3)) === 3, 'n configurável');

// Escala
$e = PainelService::escala(0);
confere($e['topo'] === 100000 && $e['marcas'] === [0, 25000, 50000, 75000, 100000], 'escala mínima R$ 1 mil');
confere(PainelService::escala(520000)['topo'] === 600000, '5,2 mil -> 6 mil');
confere(PainelService::escala(2500000)['topo'] === 4000000, '25 mil -> 40 mil');
confere(PainelService::escala(240000)['marcas'] === [0, 100000, 200000, 300000, 400000], 'marcas redondas');
confere(PainelService::rotuloEixo(600000) === 'R$6k' && PainelService::rotuloEixo(150000) === 'R$1,5k' && PainelService::rotuloEixo(25000) === 'R$250' && PainelService::rotuloEixo(0) === 'R$0', 'rótulos do eixo');

// Percentual e altura
confere(PainelService::percentual(50000, 308000) === 16, 'percentual 16%');
confere(PainelService::percentual(500, 0) === 0 && PainelService::percentual(500, -10) === 0 && PainelService::percentual(0, 100) === 0 && PainelService::percentual(-5, 100) === 0, 'percentual sem base ou negativo = 0');
confere(PainelService::percentual(900, 100) === 100, 'percentual limitado a 100');
confere(PainelService::altura(0, 600000) === 0.0 && PainelService::altura(1, 600000) === 3.0 && PainelService::altura(300000, 600000) === 50.0 && PainelService::altura(5, 0) === 0.0, 'altura das barras');
echo "Validações do painel aprovadas.\n";
