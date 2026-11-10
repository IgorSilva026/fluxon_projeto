<?php
$initial = strtoupper(substr($u['nome'], 0, 1));

ob_start();
?>
<link rel="stylesheet" href="assets/css/settings.css">

<div class="settings-shell">
    <aside class="sidebar">
        <a class="brand" href="?p=painel">
            <b>F</b> FLUXON
        </a>

        <nav aria-label="Menu principal">
            <a class="active" href="?p=painel" aria-current="page">
                ⊞ &nbsp; Dashboard
            </a>

            <span>↕ &nbsp; Fluxo de Caixa <small>Em breve</small></span>
            <span>◎ &nbsp; Reserva Fiscal <small>Em breve</small></span>
            <span>≡ &nbsp; Transações <small>Em breve</small></span>

            <a href="?p=perfil">⚙ &nbsp; Configurações</a>
        </nav>

        <div class="sidebar-user">
            <b><?= e($initial) ?></b>
            <span>
                <?= e($u['nome']) ?>
                <small>Minha conta</small>
            </span>
        </div>
    </aside>

    <div class="workspace">
        <header class="topbar">
            <h1>Dashboard</h1>

            <div>
                <a class="mini-avatar" href="?p=perfil" aria-label="Abrir configurações">
                    <?= e($initial) ?>
                </a>
            </div>
        </header>

        <main class="settings-content">
            <section class="settings-card">
                <h2>Bem-vindo ao Fluxon</h2>

                <p>Olá, <strong><?= e($u['nome']) ?></strong>!</p>
                <p><?= e($u['email']) ?></p>

                <a class="save" href="?p=perfil" style="display:inline-block;text-decoration:none">
                    Abrir configurações
                </a>
            </section>

            <section class="settings-card">
                <h2>Visão financeira</h2>

                <p>Seus indicadores financeiros aparecerão aqui
                    quando o módulo de transações estiver integrado.</p>

                <small>
                    Fluxo de caixa, reserva fiscal e transações
                    ainda estão em desenvolvimento.
                </small>
            </section>

            <section class="settings-card">
                <h2>Acesso à conta</h2>

                <form method="post" action="?p=painel">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

                    <button class="save" type="submit">
                        Sair da conta
                    </button>
                </form>
            </section>
        </main>
    </div>
</div>

<?php view('Dashboard', ob_get_clean());
