<?php
final class ReviewService
{
    public static function validate(array $input): array
    {
        $rating = $input['nota'] ?? '';
        $comment = $input['comentario'] ?? '';
        if (!is_string($rating) || !preg_match('/^[1-5]$/D', $rating)) {
            throw new InvalidArgumentException(
                'Escolha uma nota de 1 a 5 estrelas.',
            );
        }
        if (!is_string($comment)) {
            throw new InvalidArgumentException('Comentário inválido.');
        }
        $comment = trim($comment);
        if (
            !preg_match('//u', $comment) ||
            preg_match_all('/./us', $comment) > 1000
        ) {
            throw new InvalidArgumentException(
                'O comentário deve ter até 1000 caracteres.',
            );
        }
        return ['nota' => (int) $rating, 'comentario' => $comment];
    }
}
