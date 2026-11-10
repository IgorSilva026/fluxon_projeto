<?php ob_start(); ?>
<p><a href="?p=painel">Voltar ao painel</a> · <a href="?p=suporte">Fale conosco</a></p>
<h2>Antes de sair</h2>
<p>Manter sua conta permite conservar seus dados de perfil, sua foto, suas preferências e sua avaliação. Você pode
    atualizar os dados opcionais ou remover sua foto sem excluir a conta. Notificações financeiras ainda não são
    enviadas nesta versão.</p>
<p>A decisão é sua. Você pode cancelar a solicitação ou continuar com a exclusão.</p>
<h2>O que será excluído</h2>
<p>A confirmação remove permanentemente do banco ativo seu cadastro, hash de senha, vínculo com o Google, perfil e foto,
    preferências, avaliação e tokens de acesso/recuperação. Sua conta Google e sua caixa de e-mail não serão excluídas.
</p>
<p>Após a exclusão, esta aplicação não oferece recuperação da conta. Todas as sessões perderão acesso às áreas
    protegidas. Mensagens já entregues e cópias de segurança externas não são apagadas automaticamente por essa
    operação; para dúvidas, contate a equipe.</p>
<h2>1. Receber código de confirmação</h2>
<form method="post">
    <input type="hidden" name="csrf" value="<?= e(
    csrf(),
) ?>"><input type="hidden" name="acao" value="enviar">
    <p>O código será enviado ao seu e-mail cadastrado. Solicitar o código, por si só, não exclui a conta.</p>
    <button class="btn sec" type="submit">Enviar código por e-mail</button>
</form>
<h2>2. Confirmar exclusão permanente</h2>
<form method="post">
    <input type="hidden" name="csrf" value="<?= e(
    csrf(),
) ?>"><input type="hidden" name="acao" value="excluir">
    <label for="delete-code">Código de oito dígitos<input id="delete-code" name="codigo" type="text" required
            inputmode="numeric" pattern="[0-9]{8}" minlength="8" maxlength="8" autocomplete="one-time-code"></label>
    <label class="check"><input type="checkbox" name="confirmacao" value="1" required>Entendi quais dados serão
        removidos e confirmo que desejo excluir permanentemente minha conta.</label>
    <button class="btn" type="submit">Excluir permanentemente minha conta</button>
</form>
<form method="post"><input type="hidden" name="csrf" value="<?= e(
    csrf(),
) ?>"><input type="hidden" name="acao" value="cancelar"><button class="btn sec" type="submit">Cancelar e manter minha
        conta</button></form>
<?php card('Excluir conta', ob_get_clean()); ?>
