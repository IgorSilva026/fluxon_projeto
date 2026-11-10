<?php
// Regras do dashboard e dos lançamentos. Valores em centavos (int) para não
// acumular erro de ponto flutuante; sem acesso a banco, portanto testável.
final class TransactionService
{
    public const FILTROS = ['todos', 'pendentes', 'atrasados', 'concluidos'];

    public static function centavos($valor): int
    {
        return (int) round(((float) $valor) * 100);
    }

    public static function moeda(int $centavos, bool $sinal = false): string
    {
        $txt = 'R$ ' . number_format(abs($centavos) / 100, 2, ',', '.');
        if ($centavos < 0) {
            return '− ' . $txt;
        }
        return $sinal ? '+ ' . $txt : $txt;
    }

    /** Aceita "1.234,56", "1234,56", "1234.56" e "R$ 50". Devolve "1234.56". */
    public static function parseValor($v): string
    {
        if (!is_string($v)) {
            throw new InvalidArgumentException('Informe o valor.');
        }
        $v = trim(str_replace(['R$', ' '], '', $v));
        if ($v === '') {
            throw new InvalidArgumentException('Informe o valor.');
        }
        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/D', $v)) {
            throw new InvalidArgumentException(
                'Valor inválido. Use números, por exemplo 1.250,90.',
            );
        }
        if ((float) $v <= 0) {
            throw new InvalidArgumentException('O valor deve ser maior que zero.');
        }
        return number_format((float) $v, 2, '.', '');
    }

    public static function validate(array $in, DateTimeImmutable $hoje): array
    {
        $desc = $in['descricao'] ?? '';
        if (!is_string($desc)) {
            throw new InvalidArgumentException('Descrição inválida.');
        }
        $desc = trim($desc);
        $tam = preg_match('//u', $desc) ? preg_match_all('/./us', $desc) : 0;
        if ($tam < 1 || $tam > 120) {
            throw new InvalidArgumentException(
                'Informe uma descrição de 1 a 120 caracteres.',
            );
        }
        $tipo = $in['tipo'] ?? '';
        if (!in_array($tipo, ['entrada', 'saida'], true)) {
            throw new InvalidArgumentException('Escolha entrada ou saída.');
        }
        $valor = self::parseValor($in['valor'] ?? '');
        $data = $in['data'] ?? '';
        $d = is_string($data)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $data)
            : false;
        if (
            !$d ||
            $d->format('Y-m-d') !== $data ||
            $d < new DateTimeImmutable('2000-01-01') ||
            $d > $hoje->modify('+10 years')
        ) {
            throw new InvalidArgumentException('Informe uma data válida.');
        }
        $concluido = ($in['concluido'] ?? '0') === '1' ? 1 : 0;
        $imposto = $tipo === 'saida' && ($in['imposto'] ?? '') === '1';
        return [
            'descricao' => $desc,
            'tipo' => $tipo,
            'categoria' => $imposto ? 'imposto' : 'geral',
            'valor' => $valor,
            'data' => $data,
            'concluido' => $concluido,
        ];
    }

    /** [rótulo, classe CSS] do estado de um lançamento. */
    public static function status(array $l, string $hoje): array
    {
        $entrada = $l['tipo'] === 'entrada';
        if ((int) $l['concluido'] === 1) {
            return [$entrada ? 'Recebido' : 'Pago', 'ok'];
        }
        if ($l['data'] < $hoje) {
            return ['Atrasado', 'late'];
        }
        return [$entrada ? 'A receber' : 'Agendado', 'wait'];
    }

    /**
     * Entradas pendentes agrupadas por semana, a partir de hoje, até 30 dias.
     * Atrasadas entram na primeira semana (ainda são esperadas).
     * @param array<int,array{data:string,valor:string|float}> $linhas
     */
    public static function semanas(array $linhas, string $hoje, int $n = 5): array
    {
        $h = new DateTimeImmutable($hoje);
        $limite = $h->modify('+29 days')->format('Y-m-d');
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = [
                'rotulo' => $h->modify('+' . 7 * $i . ' days')->format('j/n'),
                'total' => 0,
                'atual' => $i === 0,
            ];
        }
        foreach ($linhas as $l) {
            if ($l['data'] > $limite) {
                continue;
            }
            $dias = (int) $h->diff(new DateTimeImmutable($l['data']))->format('%r%a');
            $i = max(0, min($n - 1, intdiv($dias, 7)));
            $out[$i]['total'] += self::centavos($l['valor']);
        }
        return $out;
    }

    /** Topo "redondo" do gráfico e 5 marcas de eixo (em centavos). */
    public static function escala(int $maxCentavos): array
    {
        $max = max($maxCentavos, 100000); // mínimo R$ 1 mil
        $topo = 0;
        for ($mag = 1000; $topo === 0; $mag *= 10) {
            // Fatores cujos quartos são números redondos (R$ 250, 500, 1k, 1,5k, 2k, 2,5k).
            foreach ([1, 2, 4, 6, 8, 10] as $f) {
                if ($f * $mag * 100 >= $max) {
                    $topo = (int) round($f * $mag * 100);
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

    /**
     * Reserva fiscal estimada: alíquota do perfil sobre as entradas recebidas
     * no mês, comparada ao próximo imposto pendente (a "meta").
     */
    public static function reserva(int $entradasMes, float $aliquota, ?array $imposto): array
    {
        $reservado = (int) round($entradasMes * $aliquota / 100);
        if (!$imposto) {
            return [
                'tem_meta' => false,
                'pct' => 0,
                'reservado' => $reservado,
                'meta' => 0,
                'data' => null,
                'aliquota' => $aliquota,
            ];
        }
        $meta = self::centavos($imposto['valor']);
        return [
            'tem_meta' => true,
            'pct' => $meta > 0 ? min(100, intdiv($reservado * 100, $meta)) : 0,
            'reservado' => $reservado,
            'meta' => $meta,
            'data' => (new DateTimeImmutable($imposto['data']))->format('d/m'),
            'aliquota' => $aliquota,
        ];
    }

    public static function iniciais(string $nome): string
    {
        $p = preg_split('/\s+/u', trim($nome), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$p) {
            return '?';
        }
        $a = mb_substr($p[0], 0, 1);
        $b = count($p) > 1 ? mb_substr($p[count($p) - 1], 0, 1) : '';
        return mb_strtoupper($a . $b);
    }
}
