<?php
require __DIR__ . '/../app/services/AccountDeletionService.php';
if (
    AccountDeletionService::validate([
        'confirmacao' => '1',
        'codigo' => '12345678',
    ]) !== '12345678'
) {
    throw new RuntimeException('Código válido recusado.');
}
foreach (
    [
        [],
        ['codigo' => '12345678'],
        ['confirmacao' => '1', 'codigo' => '1234'],
        ['confirmacao' => '1', 'codigo' => 'abcdefgh'],
        ['confirmacao' => '1', 'codigo' => ['12345678']],
    ]
    as $in
) {
    $rejected = false;
    try {
        AccountDeletionService::validate($in);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    if (!$rejected) {
        throw new RuntimeException('Confirmação inválida aceita.');
    }
}
echo "Validações de exclusão aprovadas.\n";
