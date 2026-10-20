<?php
/** Persistência do perfil; user_id vem exclusivamente da sessão autenticada. */
final class ProfileRepository
{
    public function __construct(private PDO $db) {}
    public function find(int $id): array
    {
        $q = $this->db->prepare(
            'SELECT u.nome,u.email,u.criado_em,p.cnpj,p.tipo_empresa,p.aliquota,p.reserva_auto,p.alertas_vencimento,p.lembretes_cobranca,p.telefone,p.cidade,p.ocupacao,p.notif_app,p.notif_email,p.frequencia,p.horario,(p.foto IS NOT NULL) AS tem_foto FROM users u LEFT JOIN user_profiles p ON p.user_id=u.id WHERE u.id=?',
        );
        $q->execute([$id]);
        return $q->fetch() ?: [];
    }
    public function save(int $id, array $v, ?string $photo, bool $remove): void
    {
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('UPDATE users SET nome=? WHERE id=?');
            $q->execute([$v['nome'], $id]);
            $q = $this->db->prepare(
                'INSERT INTO user_profiles(user_id,telefone,cidade,ocupacao,notif_app,notif_email,frequencia,horario) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE telefone=VALUES(telefone),cidade=VALUES(cidade),ocupacao=VALUES(ocupacao),notif_app=VALUES(notif_app),notif_email=VALUES(notif_email),frequencia=VALUES(frequencia),horario=VALUES(horario)',
            );
            $q->execute([
                $id,
                $v['telefone'],
                $v['cidade'],
                $v['ocupacao'],
                $v['notif_app'],
                $v['notif_email'],
                $v['frequencia'],
                $v['horario'],
            ]);
            $q = $this->db->prepare(
                'UPDATE user_profiles SET cnpj=?,tipo_empresa=?,aliquota=?,reserva_auto=?,alertas_vencimento=?,lembretes_cobranca=? WHERE user_id=?',
            );
            $q->execute([
                $v['cnpj'],
                $v['tipo_empresa'],
                $v['aliquota'],
                $v['reserva_auto'],
                $v['alertas_vencimento'],
                $v['lembretes_cobranca'],
                $id,
            ]);
            if ($photo !== null || $remove) {
                $q = $this->db->prepare(
                    'UPDATE user_profiles SET foto=?,foto_mime=? WHERE user_id=?',
                );
                $q->execute([
                    $remove ? null : $photo,
                    $remove ? null : 'image/jpeg',
                    $id,
                ]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    public function photo(int $id): ?string
    {
        $q = $this->db->prepare(
            'SELECT foto FROM user_profiles WHERE user_id=?',
        );
        $q->execute([$id]);
        $v = $q->fetchColumn();
        return is_string($v) ? $v : null;
    }
}
