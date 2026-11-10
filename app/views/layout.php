<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($t) ?> · Fluxon</title>
    <?php foreach ($styles as $style): ?>
        <link rel="stylesheet" href="assets/css/<?= e($style) ?>.css">
    <?php endforeach; ?>
    <script src="assets/js/accessibility.js"></script>
</head>
<body class="<?= $wide ? 'fluxon-wide' : '' ?>">
    <?php if ($x): ?>
        <div class="col">
            <?= $x ?>
            <?= $b ?>
        </div>
    <?php else: ?>
        <?= $b ?>
    <?php endif; ?>
    <script src="assets/js/password.js" defer></script>
</body>
</html>
