<?php
// Cálculos do painel. Não acessa banco: recebe linhas e devolve estruturas
// prontas para a tela. Valores sempre em centavos (int).
final class PainelService
{
    /**
     * Agrupa movimentações diárias em semanas terminando em $hoje.
     * Cada semana cobre 7 dias; a última é a semana atual (hoje e 6 dias antes).
     *
     * @param array<int,array{occurred_on:string,kind:string,total:int|string}> $linhas
     * @return array<int,array{rotulo:string,entradas:int,saidas:int,atual:bool}>
     */
    public static function semanas(array $linhas, string $hoje, int $n = 5): array
    {
        $fim = new DateTimeImmutable($hoje);
        $semanas = [];
        for ($i = 0; $i < $n; $i++) {
            $b = $n - 1 - $i; // 0 = semana atual
            $semanas[] = [
                'rotulo' => $fim->modify('-' . (7 * $b + 6) . ' days')->format('j/n'),
                'entradas' => 0,
                'saidas' => 0,
                'atual' => $b === 0,
            ];
        }
        foreach ($linhas as $l) {
            $dias = (int) (new DateTimeImmutable($l['occurred_on']))->diff($fim)->format('%r%a');
            if ($dias < 0 || $dias >= 7 * $n) {
                continue;
            }
            $i = $n - 1 - intdiv($dias, 7);
            $semanas[$i][$l['kind'] === 'entrada' ? 'entradas' : 'saidas'] += (int) $l['total'];
        }
        return $semanas;
    }

    /** Topo "redondo" do gráfico e 5 marcas do eixo (centavos). Mínimo R$ 1 mil. */
    public static function escala(int $maxCentavos): array
    {
        $max = max($maxCentavos, 100000);
        $topo = 0;
        for ($mag = 1000; $topo === 0; $mag *= 10) {
            // Fatores cujos quartos são números redondos (R$ 250, 500, 1k, 1,5k, 2k, 2,5k).
            foreach ([1, 2, 4, 6, 8, 10] as $f) {
                if ($f * $mag * 100 >= $max) {
                    $topo = $f * $mag * 100;
                    break;
                }
            }
        }
        $marcas = [];
        for ($i = 0; $i <= 4; $i++) {
            $marcas[] = intdiv($topo * $i, 4);
        }
        return ['topo' => $topo, 'marcas' => $marcas];
    }

    public static function rotuloEixo(int $centavos): string
    {
        $reais = $centavos / 100;
        if ($reais >= 1000) {
            return 'R$' . rtrim(rtrim(number_format($reais / 1000, 1, ',', ''), '0'), ',') . 'k';
        }
        return 'R$' . (int) $reais;
    }

    /** Percentual inteiro de $parte sobre $total, limitado a 0–100. */
    public static function percentual(int $parte, int $total): int
    {
        if ($total <= 0 || $parte <= 0) {
            return 0;
        }
        return min(100, intdiv($parte * 100, $total));
    }

    /** Altura de barra em % (0 quando não há valor; mínimo de 3% para ficar visível). */
    public static function altura(int $valor, int $topo): float
    {
        return $valor <= 0 || $topo <= 0 ? 0.0 : max(3.0, round($valor * 100 / $topo, 1));
    }
}
