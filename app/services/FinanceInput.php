<?php

final class FinanceInput
{
    public static function field(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    public static function name(string $value, int $max): string
    {
        if ($value === '' || !preg_match('/^[^\p{C}]{1,' . $max . '}$/uD', $value)) {
            throw new InvalidArgumentException("Informe um texto válido de até $max caracteres.");
        }
        return $value;
    }

    public static function id(string $value, bool $optional = false): ?int
    {
        if ($optional && $value === '') {
            return null;
        }
        if (!preg_match('/^[1-9][0-9]{0,9}$/D', $value) || (int) $value > 2147483647) {
            throw new InvalidArgumentException('Selecione um registro válido.');
        }
        return (int) $value;
    }

    public static function scaled(string $value, int $min, int $max): int
    {
        if (!preg_match('/^([0-9]{1,9})(?:[,.]([0-9]{1,2}))?$/D', $value, $parts)) {
            throw new InvalidArgumentException('Informe um valor com até duas casas decimais, sem separador de milhares.');
        }
        $number = (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
        if ($number < $min || $number > $max) {
            throw new InvalidArgumentException('O valor informado está fora do intervalo permitido.');
        }
        return $number;
    }

    public static function date(string $value, bool $optional = false): ?string
    {
        if ($optional && $value === '') {
            return null;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts)
            || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            throw new InvalidArgumentException('Informe uma data válida.');
        }
        return $value;
    }

    public static function filters(array $data): array
    {
        $get = static fn(string $key): string => self::field($data, $key);
        $from = self::date($get('de'), true);
        $to = self::date($get('ate'), true);
        if ($from && $to && $from > $to) {
            throw new InvalidArgumentException('A data inicial deve ser anterior ou igual à final.');
        }
        $query = $get('busca');
        if ($query !== '') {
            self::name($query, 80);
        }
        return [
            'busca' => $query, 'de' => $from, 'ate' => $to,
            'conta' => self::id($get('conta'), true),
            'categoria' => self::id($get('categoria'), true),
            'tipo' => in_array($get('tipo'), ['entrada','saida'], true) ? $get('tipo') : '',
            'status' => in_array($get('status'), ['ativos','estornados'], true) ? $get('status') : 'todos',
            'ordem' => in_array($get('ordem'), ['antigos','maior','menor'], true) ? $get('ordem') : 'recentes',
        ];
    }
}
