<?php
// Analisa sintaxe sem executar rotas, conectar ao banco ou enviar e-mails.
$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root),
);
$count = 0;
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $relative = str_replace(
        chr(92),
        '/',
        substr($file->getPathname(), strlen($root) + 1),
    );
    if (
        str_starts_with($relative, 'vendor/') ||
        str_starts_with($relative, 'node_modules/') ||
        $relative === 'config.local.php'
    ) {
        continue;
    }
    token_get_all(file_get_contents($file->getPathname()), TOKEN_PARSE);
    $count++;
}
echo "Sintaxe PHP aprovada: {$count} arquivos.\n";
