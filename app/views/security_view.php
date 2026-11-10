<?php ob_start(); ?>
<p><a href="?p=painel">Voltar ao painel</a> · <a href="?p=perfil">Minha conta</a></p>
<h2>Política de senha</h2>
<ul id="politica-senha">
    <li>Use pelo menos 8 caracteres, incluindo maiúscula, minúscula, número e símbolo.</li>
    <li>O limite é de 72 bytes; caracteres acentuados podem ocupar mais de um byte.</li>
    <li>Escolha uma senha diferente da atual. Evite dados pessoais e senhas usadas em outros serviços.</li>
    <li>Não compartilhe sua senha nem os códigos recebidos por e-mail.</li>
</ul>
<p>A troca exige sua senha atual. Após a alteração, todas as sessões anteriores perderão acesso às áreas protegidas, e
    será necessário entrar novamente. Links de recuperação e códigos pendentes serão invalidados.</p>
<?php if ($account['senha_hash']): ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <label>Senha atual<input type="password" name="atual" required autocomplete="current-password"></label>
    <label>Nova senha<input type="password" name="nova" required minlength="8" maxlength="72"
            autocomplete="new-password" aria-describedby="politica-senha"
            pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9_\s]).{8,}"></label>
    <label>Confirmar nova senha<input type="password" name="confirmacao" required minlength="8" maxlength="72"
            autocomplete="new-password"></label>
    <button class="btn" type="submit">Alterar senha e sair</button>
</form>
<?php else: ?>
<p>Sua conta utiliza o Google e ainda não tem senha local. Para definir uma senha com confirmação por e-mail, utilize a
    recuperação.</p>
<?php endif; ?>
<p><a href="?p=recuperar">Esqueci minha senha ou quero definir uma senha</a></p>
<?php card('Segurança', ob_get_clean()); ?>
