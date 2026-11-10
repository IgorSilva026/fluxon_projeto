<?php
require __DIR__ . '/../app/services/PasswordService.php';
$hash = password_hash('SenhaAtual!123', PASSWORD_DEFAULT);
PasswordService::validate('NovaSenha!456', 'NovaSenha!456', $hash);
foreach (
    [
        ['curta', 'curta'],
        ['novasenha123', 'novasenha123'],
        ['NovaSenha!456', 'diferente'],
        ['SenhaAtual!123', 'SenhaAtual!123'],
        [str_repeat('A', 73) . '!1a', str_repeat('A', 73) . '!1a'],
    ]
    as [$a, $b]
) {
    $rejected = false;
    try {
        PasswordService::validate($a, $b, $hash);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    if (!$rejected) {
        throw new RuntimeException('Validação aceitou senha inválida.');
    }
}
echo "Validação de senha aprovada.\n";
