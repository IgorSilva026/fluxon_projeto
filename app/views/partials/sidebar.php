<?php
// Barra lateral do dashboard e de Transações. Espera $sb = [ativo, iniciais, nome, sub].
$item = function (string $chave, string $href, string $icone, string $rotulo) use ($sb) {
    $ativo = $sb['ativo'] === $chave;
    return '<a' . ($ativo ? ' class="active" aria-current="page"' : '') .
        ' href="' . e($href) . '">' . $icone . ' &nbsp; ' . e($rotulo) . '</a>';
};
?>
<aside class="sidebar">
    <a class="brand" href="?p=painel"><b>F</b> FLUXON</a>
    <nav aria-label="Menu principal">
        <?= $item('painel', '?p=painel', '⊞', 'Dashboard') ?>
        <span>↕ &nbsp; Fluxo de Caixa <small>Em breve</small></span>
        <span>◎ &nbsp; Reserva Fiscal <small>Em breve</small></span>
        <?= $item('transacoes', '?p=transacoes', '≡', 'Transações') ?>
        <?= $item('perfil', '?p=perfil', '⚙', 'Configurações') ?>
    </nav>
    <div class="sidebar-user">
        <b><?= e($sb['iniciais']) ?></b>
        <span>
            <?= e($sb['nome']) ?>
            <small><?= e($sb['sub']) ?></small>
        </span>
    </div>
</aside>
