<?php
final class ReviewRepository
{
    public function __construct(private PDO $db) {}
    public function find(int $owner): ?array
    {
        $q = $this->db->prepare(
            'SELECT nota,comentario,atualizado_em FROM user_reviews WHERE user_id=?',
        );
        $q->execute([$owner]);
        return $q->fetch() ?: null;
    }
    public function save(int $owner, int $rating, string $comment): void
    {
        $q = $this->db->prepare(
            'INSERT INTO user_reviews(user_id,nota,comentario) VALUES(?,?,?) ON DUPLICATE KEY UPDATE nota=VALUES(nota),comentario=VALUES(comentario)',
        );
        $q->execute([$owner, $rating, $comment]);
    }
    public function delete(int $owner): void
    {
        $q = $this->db->prepare('DELETE FROM user_reviews WHERE user_id=?');
        $q->execute([$owner]);
    }
}
