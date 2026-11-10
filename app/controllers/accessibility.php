<?php
if (empty($_SESSION['uid'])) {
    go('login');
}
try {
    $q = $db->prepare(
        'SELECT email_verificado_em,versao_sessao FROM users WHERE id=?',
    );
    $q->execute([$_SESSION['uid']]);
    $a = $q->fetch();
    if (
        !$a ||
        !$a['email_verificado_em'] ||
        (int) $a['versao_sessao'] !== ($_SESSION['versao_sessao'] ?? -1)
    ) {
        session_destroy();
        go('login');
    }
} catch (Throwable $ex) {
    error_log('Fluxon: falha ao consultar sessão de acessibilidade.');
    http_response_code(500);
    card(
        'Acessibilidade',
        '<p>Não foi possível abrir esta tela. Tente novamente.</p><a href="?p=painel">Voltar ao painel</a>',
    );
}
ob_start();
?>
<p><a href="?p=painel">Voltar ao painel</a></p>
<p>Ajuste a aparência para facilitar a leitura. As mudanças são aplicadas imediatamente e mantidas neste navegador.</p>
<label for="a11y-theme">Tema</label>
<select id="a11y-theme"><option value="claro">Claro</option><option value="escuro">Escuro</option></select>
<label for="a11y-font">Tamanho da fonte</label>
<select id="a11y-font"><option value="padrao">Padrão (100%)</option><option value="grande">Grande (115%)</option><option value="maior">Maior (130%)</option></select>
<p id="a11y-status" role="status" aria-live="polite"></p>
<button class="btn sec" type="button" id="a11y-reset">Restaurar aparência padrão</button>
<h2>Prévia de leitura</h2>
<p>Organize suas informações e consulte sua conta com mais conforto. Você pode mudar o tema e o tamanho da fonte quando quiser.</p>
<h2>Navegação pelo teclado</h2>
<p>Use Tab e Shift+Tab para percorrer os controles. Use Enter para abrir links ou acionar botões. Nas listas de opções, utilize as setas do teclado.</p>
<p>As preferências ficam no navegador deste dispositivo. Outro navegador ou a limpeza dos dados do site pode restaurar o padrão. Você também pode usar o zoom do navegador.</p>
<noscript><p>Ative o JavaScript para usar os controles de aparência. O zoom do navegador continua disponível.</p></noscript>
<?php card('Acessibilidade', ob_get_clean()); ?>
