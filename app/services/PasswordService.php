<?php
final class PasswordService
{
    public static function validate(
        string $new,
        string $confirm,
        string $hash,
    ): void {
        if (
            strlen($new) > 72 ||
            !preg_match(
                '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/D',
                $new,
            )
        ) {
            throw new InvalidArgumentException(
                'Use pelo menos 8 caracteres, com maiúscula, minúscula, número e símbolo, e no máximo 72 bytes.',
            );
        }
        if ($new !== $confirm) {
            throw new InvalidArgumentException(
                'A confirmação da nova senha não coincide.',
            );
        }
        if (password_verify($new, $hash)) {
            throw new InvalidArgumentException(
                'Escolha uma senha diferente da atual.',
            );
        }
    }
    public static function change(
        PDO $db,
        int $id,
        int $version,
        string $current,
        string $new,
        string $confirm,
    ): void {
        $db->beginTransaction();
        try {
            // Bloqueio serializa tentativas e impede atualização com versão antiga da sessão.
            $q = $db->prepare(
                'SELECT senha_hash,versao_sessao,tentativas,bloqueado_ate FROM users WHERE id=? FOR UPDATE',
            );
            $q->execute([$id]);
            $u = $q->fetch();
            if (!$u || (int) $u['versao_sessao'] !== $version) {
                throw new InvalidArgumentException(
                    'Sessão expirada. Saia e entre novamente.',
                );
            }
            if (!$u['senha_hash']) {
                throw new InvalidArgumentException(
                    'Defina uma senha pelo fluxo de recuperação por e-mail.',
                );
            }
            if (
                $u['bloqueado_ate'] &&
                strtotime($u['bloqueado_ate']) > time()
            ) {
                throw new InvalidArgumentException(
                    'Aguarde o fim do bloqueio de 15 minutos antes de tentar novamente.',
                );
            }
            if (!password_verify($current, $u['senha_hash'])) {
                $tries =
                    $u['bloqueado_ate'] &&
                    strtotime($u['bloqueado_ate']) <= time()
                        ? 1
                        : (int) $u['tentativas'] + 1;
                $q = $db->prepare(
                    'UPDATE users SET tentativas=?,bloqueado_ate=? WHERE id=?',
                );
                $q->execute([
                    $tries,
                    $tries >= 5 ? date('Y-m-d H:i:s', time() + 900) : null,
                    $id,
                ]);
                $db->commit();
                throw new InvalidArgumentException(
                    'Senha atual incorreta. Após cinco erros, a conta será bloqueada por 15 minutos.',
                );
            }
            self::validate($new, $confirm, $u['senha_hash']);
            $q = $db->prepare(
                'UPDATE users SET senha_hash=?,versao_sessao=versao_sessao+1,tentativas=0,bloqueado_ate=NULL WHERE id=?',
            );
            $q->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
            $q = $db->prepare('UPDATE tokens SET usado=1 WHERE user_id=?');
            $q->execute([$id]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
