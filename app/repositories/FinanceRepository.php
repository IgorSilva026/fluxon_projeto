<?php

final class FinanceRepository
{
    public const PAGE_SIZE = 20;
    private const TABLES = ['contas'=>'finance_accounts', 'categorias'=>'finance_categories', 'regras'=>'finance_rules'];

    public function __construct(private PDO $db, private int $owner) {}

    private function query(string $sql, array $params = []): PDOStatement
    {
        $q = $this->db->prepare($sql);
        $q->execute([$this->owner, ...$params]);
        return $q;
    }

    private function write(callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            // Serializa alterações financeiras da mesma conta de usuário.
            $lock = $this->query('SELECT id FROM users WHERE id=? FOR UPDATE')->fetchColumn();
            if (!$lock) {
                throw new DomainException('A sessão não corresponde a uma conta ativa.');
            }
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function catalog(string $group): array
    {
        $table = self::TABLES[$group];
        return $this->query("SELECT * FROM $table WHERE user_id=? ORDER BY lower(name),id")->fetchAll();
    }

    public function item(string $group, int $id): array
    {
        $table = self::TABLES[$group];
        $item = $this->query("SELECT * FROM $table WHERE user_id=? AND id=?", [$id])->fetch();
        if (!$item) {
            throw new DomainException('Registro não encontrado.');
        }
        return $item;
    }

    public function saveItem(string $group, ?int $id, array $data): void
    {
        $name = FinanceInput::name(FinanceInput::field($data,'nome'), 80);
        $table = self::TABLES[$group];
        $this->write(function () use ($group,$id,$data,$name,$table): void {
            if ($id !== null) {
                $this->item($group,$id);
            }
            $columns = ['name' => $name];
            if ($group === 'contas') {
                $columns['connected'] = FinanceInput::field($data,'conectada') === '0' ? 'false' : 'true';
                $columns['opening_cents'] = FinanceInput::scaled(FinanceInput::field($data,'valor'),0,99999999999);
            }
            if ($group === 'regras') {
                $columns['rate_bp'] = FinanceInput::scaled(FinanceInput::field($data,'percentual'),1,10000);
                $columns['due_date'] = FinanceInput::date(FinanceInput::field($data,'vencimento'),true);
                $columns['active'] = FinanceInput::field($data,'ativa') === '1' ? 'true' : 'false';
                if ($columns['active'] === 'true') {
                    $others = $this->query('SELECT COALESCE(SUM(rate_bp),0) FROM finance_rules WHERE user_id=? AND active AND id<>?', [$id ?? 0])->fetchColumn();
                    if ((int) $others + $columns['rate_bp'] > 10000) {
                        throw new DomainException('As regras ativas, somadas, não podem passar de 100%.');
                    }
                }
            }
            // As colunas são definidas no código; os valores são parametrizados.
            if ($id !== null) {
                $set = implode(',',array_map(static fn($column)=>"$column=?",array_keys($columns)));
                $sql = $this->db->prepare("UPDATE $table SET $set WHERE user_id=? AND id=?");
                $sql->execute([...array_values($columns),$this->owner,$id]);
            } else {
                $names = implode(',',array_keys($columns));
                $slots = implode(',',array_fill(0,count($columns),'?'));
                $this->query("INSERT INTO $table(user_id,$names) VALUES (?,$slots)",array_values($columns));
            }
        });
    }

    public function removeItem(string $group, int $id): void
    {
        $table = self::TABLES[$group];
        $this->write(function () use ($group,$id,$table): void {
            $this->item($group,$id);
            if ($group === 'regras') {
                $this->query('UPDATE finance_rules SET active=false WHERE user_id=? AND id=?',[$id]);
                return;
            }
            $column = $group === 'contas' ? 'account_id' : 'category_id';
            if ($this->query("SELECT 1 FROM finance_entries WHERE user_id=? AND $column=? LIMIT 1",[$id])->fetchColumn()) {
                throw new DomainException('O registro tem transações vinculadas e não pode ser excluído.');
            }
            $this->query("DELETE FROM $table WHERE user_id=? AND id=?",[$id]);
        });
    }

    private function entryQuery(): string
    {
        return ' FROM finance_entries t
            JOIN finance_accounts a ON a.id=t.account_id AND a.user_id=t.user_id
            JOIN finance_categories c ON c.id=t.category_id AND c.user_id=t.user_id';
    }

    public function entry(int $id): array
    {
        $row = $this->query('SELECT t.*,a.name AS account,c.name AS category' . $this->entryQuery() .
            ' WHERE t.user_id=? AND t.id=?',[$id])->fetch();
        if (!$row) {
            throw new DomainException('Transação não encontrada.');
        }
        return $row;
    }

    public function reserves(int $id): array
    {
        return $this->query('SELECT id,purpose,due_date,rate_bp,amount_cents,released_at FROM finance_reserves WHERE user_id=? AND entry_id=? ORDER BY id',[$id])->fetchAll();
    }

    private function insertEntry(int $account, int $category, string $description, string $kind, int $amount, string $date, ?string $ref): ?int
    {
        $id = $this->query('INSERT INTO finance_entries(user_id,account_id,category_id,description,kind,amount_cents,occurred_on,external_ref)
            VALUES (?,?,?,?,?,?,?,?) ON CONFLICT (user_id,account_id,external_ref) DO NOTHING RETURNING id',
            [$account,$category,$description,$kind,$amount,$date,$ref])->fetchColumn();
        if ($id && $kind === 'entrada') {
            // O percentual, finalidade e vencimento ficam no histórico da entrada.
            // Divisão inteira trunca frações de centavo e nunca reserva acima da entrada.
            $this->query('INSERT INTO finance_reserves(user_id,entry_id,rule_id,purpose,due_date,rate_bp,amount_cents)
                SELECT r.user_id,t.id,r.id,r.name,r.due_date,r.rate_bp,(t.amount_cents*r.rate_bp)/10000
                FROM finance_rules r JOIN finance_entries t ON t.user_id=r.user_id
                WHERE r.user_id=? AND t.id=? AND r.active',[$id]);
        }
        return $id ? (int) $id : null;
    }

    public function saveEntry(?int $id, array $data): void
    {
        $get = static fn($key) => FinanceInput::field($data,$key);
        $description = FinanceInput::name($get('descricao'),160);
        $date = FinanceInput::date($get('data'));
        if ($date > date('Y-m-d')) {
            throw new DomainException('Uma movimentação confirmada não pode ter data futura.');
        }
        $amount = FinanceInput::scaled($get('valor'),1,99999999999);
        $category = FinanceInput::id($get('categoria'));
        $this->write(function () use ($id,$get,$description,$date,$amount,$category): void {
            $this->item('categorias',$category);
            if ($id === null) {
                $account = FinanceInput::id($get('conta'));
                $this->item('contas',$account);
                $kind = $get('tipo');
                if (!in_array($kind,['entrada','saida'],true)) {
                    throw new DomainException('Selecione entrada ou saída.');
                }
                $this->insertEntry($account,$category,$description,$kind,$amount,$date,null);
                return;
            }
            $row = $this->entry($id);
            if ($row['reversed_at']) {
                throw new DomainException('Transações estornadas são preservadas no histórico.');
            }
            if ($row['external_ref'] !== null) {
                if ($amount !== (int) $row['amount_cents'] || $date !== $row['occurred_on']) {
                    throw new DomainException('Valor e data da importação devem ser preservados.');
                }
                $q = $this->db->prepare('UPDATE finance_entries SET description=?,category_id=? WHERE user_id=? AND id=?');
                $q->execute([$description,$category,$this->owner,$id]);
                return;
            }
            if ($amount !== (int) $row['amount_cents'] &&
                $this->query('SELECT 1 FROM finance_reserves WHERE user_id=? AND entry_id=? AND released_at IS NOT NULL LIMIT 1',[$id])->fetchColumn()) {
                throw new DomainException('Esta entrada possui reserva liberada. Preserve o valor para manter o histórico.');
            }
            $q = $this->db->prepare('UPDATE finance_entries SET description=?,category_id=?,amount_cents=?,occurred_on=? WHERE user_id=? AND id=?');
            $q->execute([$description,$category,$amount,$date,$this->owner,$id]);
            $q = $this->db->prepare('UPDATE finance_reserves SET amount_cents=(CAST(? AS bigint)*rate_bp)/10000 WHERE user_id=? AND entry_id=?');
            $q->execute([$amount,$this->owner,$id]);
        });
    }

    public function releaseReserve(int $reserveId): void
    {
        $this->write(function () use ($reserveId): void {
            $row = $this->query('SELECT r.id,t.reversed_at FROM finance_reserves r
                JOIN finance_entries t ON t.user_id=r.user_id AND t.id=r.entry_id
                WHERE r.user_id=? AND r.id=?',[$reserveId])->fetch();
            if (!$row || $row['reversed_at']) {
                throw new DomainException('Reserva não encontrada ou movimentação estornada.');
            }
            $this->query('UPDATE finance_reserves SET released_at=now() WHERE user_id=? AND id=? AND released_at IS NULL',[$reserveId]);
        });
    }

    public function importDemo(int $account, int $category): int
    {
        return $this->write(function () use ($account,$category): int {
            $connection = $this->item('contas',$account);
            if (!$connection['connected']) {
                throw new DomainException('Conta simulada desconectada. Reconecte no cadastro antes de importar.');
            }
            $this->item('categorias',$category);
            $today = new DateTimeImmutable('today');
            $count = 0;
            foreach ([
                ['demo-v1-entrada','Serviço recebido (simulação)','entrada',250000,2],
                ['demo-v1-saida','Compra de materiais (simulação)','saida',42000,1],
            ] as [$ref,$name,$type,$amount,$days]) {
                $date = $today->modify("-$days days")->format('Y-m-d');
                $count += $this->insertEntry($account,$category,$name,$type,$amount,$date,$ref) !== null ? 1 : 0;
            }
            $this->query('UPDATE finance_accounts SET last_synced_at=now() WHERE user_id=? AND id=?',[$account]);
            return $count;
        });
    }

    public function removeEntry(int $id): void
    {
        $this->write(function () use ($id): void {
            $entry = $this->entry($id);
            if ($entry['external_ref'] !== null) {
                $this->query('UPDATE finance_entries SET reversed_at=now() WHERE user_id=? AND id=? AND reversed_at IS NULL',[$id]);
            } else {
                $this->query('DELETE FROM finance_entries WHERE user_id=? AND id=?',[$id]);
            }
        });
    }

    private function filter(array $f): array
    {
        $conditions = ['t.user_id=?'];
        $params = [];
        foreach (['de'=>'t.occurred_on>=?', 'ate'=>'t.occurred_on<=?', 'conta'=>'t.account_id=?', 'categoria'=>'t.category_id=?', 'tipo'=>'t.kind=?'] as $key=>$condition) {
            if ($f[$key] !== null && $f[$key] !== '') {
                $conditions[] = $condition;
                $params[] = $f[$key];
            }
        }
        if ($f['busca'] !== '') {
            $conditions[] = '(strpos(lower(t.description), lower(?))>0 OR strpos(lower(c.name), lower(?))>0 OR strpos(lower(a.name), lower(?))>0)';
            array_push($params,$f['busca'],$f['busca'],$f['busca']);
        }
        if ($f['status'] !== 'todos') {
            $conditions[] = $f['status'] === 'ativos' ? 't.reversed_at IS NULL' : 't.reversed_at IS NOT NULL';
        }
        return [' WHERE ' . implode(' AND ',$conditions),$params];
    }

    public function totals(array $f): array
    {
        [$where,$params] = $this->filter($f);
        return $this->query("SELECT COUNT(*) AS total,
            COALESCE(SUM(t.amount_cents) FILTER (WHERE t.kind='entrada' AND t.reversed_at IS NULL),0) AS incoming,
            COALESCE(SUM(t.amount_cents) FILTER (WHERE t.kind='saida' AND t.reversed_at IS NULL),0) AS outgoing" .
            $this->entryQuery() . $where,$params)->fetch();
    }

    public function rows(array $f, int $offset, int $limit): PDOStatement
    {
        [$where,$params] = $this->filter($f);
        $sort = match ($f['ordem']) {
            'antigos'=>'t.occurred_on ASC,t.id ASC',
            'maior'=>'t.amount_cents DESC,t.id DESC',
            'menor'=>'t.amount_cents ASC,t.id ASC',
            default=>'t.occurred_on DESC,t.id DESC',
        };
        return $this->query('SELECT t.*,a.name AS account,c.name AS category' . $this->entryQuery() .
            $where . " ORDER BY $sort LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,$params);
    }

    public function balances(?int $account): array
    {
        $extra = $account ? ' AND id=?' : '';
        $byAccount = $account ? ' AND t.account_id=?' : '';
        $params = $account ? [$account] : [];
        $opening = $this->query('SELECT COALESCE(SUM(opening_cents),0) FROM finance_accounts WHERE user_id=?'.$extra,$params)->fetchColumn();
        $net = $this->query("SELECT COALESCE(SUM(CASE WHEN t.kind='entrada' THEN t.amount_cents ELSE -t.amount_cents END),0)
            FROM finance_entries t WHERE t.user_id=? AND t.reversed_at IS NULL".$byAccount,$params)->fetchColumn();
        $reserved = $this->query('SELECT COALESCE(SUM(r.amount_cents),0) FROM finance_reserves r
            JOIN finance_entries t ON t.user_id=r.user_id AND t.id=r.entry_id
            WHERE t.user_id=? AND t.reversed_at IS NULL AND r.released_at IS NULL'.$byAccount,$params)->fetchColumn();
        return [(int) $opening + (int) $net,(int) $reserved];
    }

    public function chart(array $f, string $group): array
    {
        [$where,$params] = $this->filter($f);
        $expr = $group === 'months' ? "to_char(t.occurred_on,'YYYY-MM')" : 'c.name';
        $filter = $group === 'months' ? '' : " AND t.kind='saida'";
        $order = $group === 'months' ? 'label DESC LIMIT 12' : 'outgoing DESC,label LIMIT 10';
        return $this->query("SELECT $expr AS label,
            COALESCE(SUM(t.amount_cents) FILTER (WHERE t.kind='entrada'),0) AS incoming,
            COALESCE(SUM(t.amount_cents) FILTER (WHERE t.kind='saida'),0) AS outgoing" .
            $this->entryQuery() . $where . ' AND t.reversed_at IS NULL' . $filter .
            " GROUP BY $expr ORDER BY $order",$params)->fetchAll();
    }

    public function due(?int $account): array
    {
        $extra = $account ? ' AND t.account_id=?' : '';
        return $this->query('SELECT r.purpose,r.due_date,r.amount_cents FROM finance_reserves r
            JOIN finance_entries t ON t.user_id=r.user_id AND t.id=r.entry_id
            WHERE t.user_id=? AND t.reversed_at IS NULL AND r.released_at IS NULL AND r.amount_cents>0
              AND r.due_date<=CURRENT_DATE+7' . $extra . ' ORDER BY r.due_date,r.id LIMIT 10',
            $account ? [$account] : [])->fetchAll();
    }
}
