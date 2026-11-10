<?php
require __DIR__ . '/../app/services/ReviewService.php';
foreach (range(1, 5) as $n) {
    $v = ReviewService::validate([
        'nota' => (string) $n,
        'comentario' => '  Experiência boa  ',
        'user_id' => '999',
    ]);
    if (
        $v['nota'] !== $n ||
        $v['comentario'] !== 'Experiência boa' ||
        isset($v['user_id'])
    ) {
        throw new RuntimeException('Erro na validação.');
    }
}
foreach (
    [
        [],
        ['nota' => '0'],
        ['nota' => '6'],
        ['nota' => ['5']],
        ['nota' => '5', 'comentario' => ['texto']],
        ['nota' => '3', 'comentario' => str_repeat('ç', 1001)],
    ]
    as $input
) {
    $rejected = false;
    try {
        ReviewService::validate($input);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    if (!$rejected) {
        throw new RuntimeException('Entrada inválida foi aceita.');
    }
}
echo "Validação de avaliação aprovada.\n";
