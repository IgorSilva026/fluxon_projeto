<?php
// Todas as consultas filtram por user_id: um usuário nunca enxerga ou altera
// lançamentos de outro, mesmo que forje o id no formulário.
final class TransactionRepository
{
    public const POR_PAGINA = 20;

    public function __construct(private PDO $db) {}

    public function add(int $uid, array $c): void
    {
        $q = $this->db->prepare(
            'INSERT INTO lancamentos(user_id,descricao,tipo,categoria,valor,data,concluido) VALUES(?,?,?,?,?,?,?)',
        );
        $q->execute([
            $uid,
            $c['descricao'],
            $c['tipo'],
            $c['categoria'],
            $c['valor'],
            $c['data'],
            $c['concluido'],
        ]);
    }

    public function alternar(int $uid, int $id): bool
    {
        $q = $this->db->prepare(
            'UPDATE lancamentos SET concluido = 1 - concluido WHERE id=? AND user_id=?',
        );
        $q->execute([$id, $uid]);
        return $q->rowCount() === 1;
    }

    public function excluir(int $uid, int $id): bool
    {
        $q = $this->db->prepare('DELETE FROM lancamentos WHERE id=? AND user_id=?');
        $q->execute([$id, $uid]);
        return $q->rowCount() === 1;
    }

    public function saldo(int $uid): string
    {
        $q = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo='entrada' THEN valor ELSE -valor END),0) FROM lancamentos WHERE user_id=? AND concluido=1",
        );
        $q->execute([$uid]);
        return (string) $q->fetchColumn();
    }

    /** @return array{entrada:string,saida:string} concluídos entre as datas */
    public function periodo(int $uid, string $ini, string $fim): array
    {
        $q = $this->db->prepare(
            'SELECT tipo, COALESCE(SUM(valor),0) AS total FROM lancamentos WHERE user_id=? AND concluido=1 AND data BETWEEN ? AND ? GROUP BY tipo',
        );
        $q->execute([$uid, $ini, $fim]);
        $r = ['entrada' => '0', 'saida' => '0'];
        foreach ($q as $l) {
            $r[$l['tipo']] = (string) $l['total'];
        }
        return $r;
    }

    public function entradasPendentes(int $uid, string $ate): array
    {
        $q = $this->db->prepare(
            "SELECT data, valor FROM lancamentos WHERE user_id=? AND tipo='entrada' AND concluido=0 AND data<=? ORDER BY data",
        );
        $q->execute([$uid, $ate]);
        return $q->fetchAll();
    }

    public function proximoImposto(int $uid): ?array
    {
        $q = $this->db->prepare(
            "SELECT descricao,valor,data FROM lancamentos WHERE user_id=? AND tipo='saida' AND categoria='imposto' AND concluido=0 ORDER BY data ASC LIMIT 1",
        );
        $q->execute([$uid]);
        return $q->fetch() ?: null;
    }

    public function atrasados(int $uid, string $hoje): int
    {
        $q = $this->db->prepare(
            'SELECT COUNT(*) FROM lancamentos WHERE user_id=? AND concluido=0 AND data<?',
        );
        $q->execute([$uid, $hoje]);
        return (int) $q->fetchColumn();
    }

    /** Pendentes primeiro (mais antigos no topo), depois os concluídos recentes. */
    public function recentes(int $uid, int $limite = 8): array
    {
        $limite = max(1, min(50, $limite));
        $q = $this->db->prepare(
            "SELECT id,descricao,tipo,categoria,valor,data,concluido FROM lancamentos WHERE user_id=? ORDER BY concluido ASC, CASE WHEN concluido=0 THEN data END ASC, data DESC, id DESC LIMIT $limite",
        );
        $q->execute([$uid]);
        return $q->fetchAll();
    }

    private function filtro(string $filtro): string
    {
        return match ($filtro) {
            'pendentes' => ' AND concluido=0',
            'atrasados' => ' AND concluido=0 AND data < :hoje',
            'concluidos' => ' AND concluido=1',
            default => '',
        };
    }

    public function total(int $uid, string $filtro, string $hoje): int
    {
        $sql = 'SELECT COUNT(*) FROM lancamentos WHERE user_id=:u' . $this->filtro($filtro);
        $q = $this->db->prepare($sql);
        $q->bindValue(':u', $uid, PDO::PARAM_INT);
        if ($filtro === 'atrasados') {
            $q->bindValue(':hoje', $hoje);
        }
        $q->execute();
        return (int) $q->fetchColumn();
    }

    public function listar(int $uid, string $filtro, string $hoje, int $pagina): array
    {
        $ordem = in_array($filtro, ['pendentes', 'atrasados'], true)
            ? 'data ASC, id ASC'
            : 'data DESC, id DESC';
        $off = max(0, ($pagina - 1) * self::POR_PAGINA);
        $sql =
            'SELECT id,descricao,tipo,categoria,valor,data,concluido FROM lancamentos WHERE user_id=:u' .
            $this->filtro($filtro) .
            " ORDER BY $ordem LIMIT " .
            self::POR_PAGINA .
            " OFFSET $off";
        $q = $this->db->prepare($sql);
        $q->bindValue(':u', $uid, PDO::PARAM_INT);
        if ($filtro === 'atrasados') {
            $q->bindValue(':hoje', $hoje);
        }
        $q->execute();
        return $q->fetchAll();
    }
}
