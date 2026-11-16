<?php
preg_match('/^./us', $shellName, $shellChars);
$shellInitial = $shellChars[0] ?? '';
$shellInitial = function_exists('mb_strtoupper') ? mb_strtoupper($shellInitial,'UTF-8') : strtoupper($shellInitial); ?>
<div class="dashboard-shell">
    <a href="#finance-main" class="skip">Pular para o conteúdo</a>
    <aside class="dashboard-sidebar">
        <a class="dashboard-brand" href="?p=painel"><b>F</b> FLUXON</a>
        <nav aria-label="Menu principal">
            <a href="?p=painel" <?= $p==='painel'?'aria-current="page"':'' ?>>⊞ &nbsp; Dashboard</a>
            <a href="?p=extrato" <?= $p==='extrato'?'aria-current="page"':'' ?>>↕ &nbsp; Fluxo de Caixa / Extrato</a>
            <a href="?p=cadastros&amp;grupo=regras" <?= $p==='cadastros' && ($group??null)==='regras'?'aria-current="page"':'' ?>>◎ &nbsp; Regras de Reserva</a>
            <a href="?p=movimentacao" <?= $p==='movimentacao'?'aria-current="page"':'' ?>>≡ &nbsp; Movimentações</a>
            <a href="?p=cadastros&amp;grupo=contas" <?= $p==='cadastros' && ($group??null)!=='regras'?'aria-current="page"':'' ?>>▤ &nbsp; Cadastros</a>
            <a href="?p=perfil" <?= $p==='perfil'?'aria-current="page"':'' ?>>⚙ &nbsp; Configurações</a>
        </nav>
        <div class="dashboard-user"><b><?= e($shellInitial) ?></b><span><?= e($shellName) ?><small>Minha conta</small></span></div>
    </aside>
    <div class="dashboard-workspace">
        <header class="dashboard-topbar">
            <h1 id="finance-main" tabindex="-1"><?= e($shellTitle) ?></h1>
            <div class="dashboard-topbar-actions">
                <?php if ($p==='perfil'): ?>
                    <a href="#notificacoes">Notificações</a>
                <?php endif; ?>
                <a class="dashboard-avatar" href="<?= $p==='perfil'?'#perfil':'?p=perfil' ?>" aria-label="<?= $p==='perfil'?'Seu perfil':'Abrir configurações' ?>"><?= e($shellInitial) ?></a>
            </div>
        </header>
