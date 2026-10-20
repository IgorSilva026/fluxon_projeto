<?php
final class AccountDeletionService
{
    public function __construct(private PDO $db) {}
    private function lockAccount(int $id, int $version): array
    {
        $q = $this->db->prepare(
            'SELECT email,email_verificado_em,versao_sessao FROM users WHERE id=? FOR UPDATE',
        );
        $q->execute([$id]);
        $u = $q->fetch();
        if (
            !$u ||
            !$u['email_verificado_em'] ||
            (int) $u['versao_sessao'] !== $version
        ) {
            throw new InvalidArgumentException(
                'Sessão expirada. Entre novamente.',
            );
        }
        return $u;
    }
    public function issue(int $id, int $version): array
    {
        $this->db->beginTransaction();
        try {
            $u = $this->lockAccount($id, $version);
            $q = $this->db->prepare(
                'SELECT (solicitado_em>DATE_SUB(NOW(),INTERVAL 60 SECOND)) AS aguardar FROM account_deletion_tokens WHERE user_id=?',
            );
            $q->execute([$id]);
            $old = $q->fetch();
            if ($old && $old['aguardar']) {
                throw new InvalidArgumentException(
                    'Aguarde um minuto antes de solicitar outro código.',
                );
            }
            $code = (string) random_int(10000000, 99999999);
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $q = $this->db->prepare(
                'INSERT INTO account_deletion_tokens(user_id,codigo_hash,solicitado_em,expira_em,tentativas,usado) VALUES(?,?,NOW(),DATE_ADD(NOW(),INTERVAL 10 MINUTE),0,0) ON DUPLICATE KEY UPDATE codigo_hash=VALUES(codigo_hash),solicitado_em=VALUES(solicitado_em),expira_em=VALUES(expira_em),tentativas=0,usado=0',
            );
            $q->execute([$id, $hash]);
            $this->db->commit();
            return ['email' => $u['email'], 'codigo' => $code, 'hash' => $hash];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
    public function cancelDelivery(int $id, string $hash): void
    {
        // Não cancela outro código que tenha sido gerado depois deste envio.
        $q = $this->db->prepare(
            'UPDATE account_deletion_tokens SET usado=1 WHERE user_id=? AND codigo_hash=?',
        );
        $q->execute([$id, $hash]);
    }
    public function cancel(int $id): void
    {
        $q = $this->db->prepare(
            'UPDATE account_deletion_tokens SET usado=1 WHERE user_id=?',
        );
        $q->execute([$id]);
    }
    public static function validate(array $input): string
    {
        if (($input['confirmacao'] ?? '') !== '1') {
            throw new InvalidArgumentException(
                'Marque a confirmação para excluir permanentemente sua conta.',
            );
        }
        $code = $input['codigo'] ?? '';
        if (!is_string($code) || !preg_match('/^[0-9]{8}$/D', trim($code))) {
            throw new InvalidArgumentException(
                'Informe o código de oito dígitos recebido por e-mail.',
            );
        }
        return trim($code);
    }
    public function delete(int $id, int $version, string $code): void
    {
        $this->db->beginTransaction();
        try {
            $this->lockAccount($id, $version);
            $q = $this->db->prepare(
                'SELECT codigo_hash,tentativas,usado,(expira_em>NOW()) AS valido FROM account_deletion_tokens WHERE user_id=? FOR UPDATE',
            );
            $q->execute([$id]);
            $t = $q->fetch();
            if (
                !$t ||
                $t['usado'] ||
                !$t['valido'] ||
                (int) $t['tentativas'] >= 5
            ) {
                throw new InvalidArgumentException(
                    'Código inválido, expirado ou sem tentativas. Solicite um novo código.',
                );
            }
            if (!password_verify($code, $t['codigo_hash'])) {
                $q = $this->db->prepare(
                    'UPDATE account_deletion_tokens SET tentativas=tentativas+1 WHERE user_id=?',
                );
                $q->execute([$id]);
                $this->db->commit();
                throw new InvalidArgumentException(
                    'Código incorreto. Após cinco erros, solicite um novo código.',
                );
            }
            // FK ON DELETE CASCADE remove perfil/foto, avaliação, tokens de acesso e de exclusão.
            $q = $this->db->prepare('DELETE FROM users WHERE id=?');
            $q->execute([$id]);
            if ($q->rowCount() !== 1) {
                throw new RuntimeException('Conta não excluída.');
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
