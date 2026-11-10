<?php
$rating = $review['nota'] ?? null;
$comment = $review['comentario'] ?? '';
if ($POST && ($_POST['acao'] ?? '') === 'salvar') {
    $rating = is_string($_POST['nota'] ?? null) ? $_POST['nota'] : null;
    $comment = is_string($_POST['comentario'] ?? null)
        ? $_POST['comentario']
        : '';
}
ob_start();
?>
<p><a href="?p=painel">Voltar ao painel</a> · <a href="?p=suporte">Ajuda e suporte</a></p>
<p>Conte como foi sua experiência. Sua avaliação ficará vinculada à sua conta para análise da equipe. Ela não é
    publicada para outros usuários nesta versão.</p>
<form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <input type="hidden" name="acao" value="salvar">
    <fieldset>
        <legend>Sua nota de 1 a 5 estrelas</legend>
        <div class="review-stars">
            <?php for ($n = 1; $n <= 5; $n++): ?>
            <label class="review-star"><input type="radio" name="nota" value="<?= $n ?>" required <?= (string) $rating ===
(string) $n
    ? 'checked'
    : '' ?>>
                <span class="review-symbol" aria-hidden="true">★</span><span><?= $n ?> <?= $n ===
 1
     ? 'estrela'
     : 'estrelas' ?></span></label>
            <?php endfor; ?>
        </div>
        <p>1 = muito insatisfeito · 5 = muito satisfeito</p>
    </fieldset>
    <label for="review-comment">Comentário (opcional, até 1000 caracteres)</label>
    <textarea id="review-comment" name="comentario" maxlength="1000" rows="5" placeholder="O que funcionou bem? O que pode melhorar?"><?= e(
    $comment,
) ?></textarea>
    <p>Evite incluir dados pessoais, senhas ou códigos de acesso no comentário.</p>
    <button class="btn" type="submit"><?= $review
    ? 'Salvar alterações da avaliação'
    : 'Enviar avaliação' ?></button>
</form>
<?php if ($review): ?>
<p>Última atualização: <?= e(
    date('d/m/Y H:i', strtotime($review['atualizado_em'])),
) ?></p>
<form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <input type="hidden" name="acao" value="excluir">
    <label class="check"><input type="checkbox" name="confirmar_exclusao" value="1" required>Confirmo que desejo excluir
        minha avaliação.</label>
    <button class="btn sec" type="submit">Excluir minha avaliação</button>
</form>
<?php endif; ?>
<?php card('Avaliar aplicativo', ob_get_clean()); ?>
