<?php
require __DIR__ . '/../app/services/ProfileService.php';
function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
$v = ProfileService::validate([
    'nome' => 'Ana',
    'email' => 'troca@example.com',
    'user_id' => '999',
    'frequencia' => 'evento',
]);
check(
    !isset($v['email']) && !isset($v['user_id']),
    'Identidade não deve ser editável',
);
check($v['horario'] === null, 'Evento não usa horário');
$v = ProfileService::validate([
    'nome' => 'Ana',
    'frequencia' => 'programado',
    'horario' => '09:30',
    'notif_app' => '1',
]);
check(
    $v['horario'] === '09:30' && $v['notif_app'] === 1,
    'Preferências válidas',
);
foreach (
    [
        ['nome' => ''],
        ['nome' => str_repeat('a', 121)],
        ['nome' => 'Ana', 'telefone' => '<script>'],
        ['nome' => 'Ana', 'frequencia' => 'invalida'],
        ['nome' => 'Ana', 'frequencia' => 'diario', 'horario' => '25:90'],
        ['nome' => ['Ana']],
    ]
    as $input
) {
    $rejected = false;
    try {
        ProfileService::validate($input);
    } catch (InvalidArgumentException $e) {
        $rejected = true;
    }
    check($rejected, 'Entrada inválida aceita');
}
check(ProfileService::photo([]) === null, 'Upload opcional');
echo "Validações de perfil aprovadas.\n";
