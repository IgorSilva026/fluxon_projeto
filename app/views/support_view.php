<?php
ob_start(); ?>

<nav aria-label="Ajuda">
    <p>
        <a href="?p=painel">Voltar ao painel</a>
        <a href="?p=suporte">Fale conosco</a>
        <a href="?p=faq">Perguntas frequentes</a>
        <a href="?p=sobre">Sobre</a>
    </p>
</nav>

<?php if ($p === 'suporte'): ?>

<h2>Fale conosco</h2>

<p>
    Entre em contato com a equipe Fluxon para tirar
    dúvidas, relatar problemas ou enviar sugestões.
</p>

<p>
    <strong>E-mail:</strong><br>
    <a href="mailto:fluxontesoureirodigital@gmail.com">
        fluxontesoureirodigital@gmail.com
    </a>
</p>

<p>
    <a class="btn"
        href="https://mail.google.com/mail/?view=cm&amp;fs=1&amp;to=fluxontesoureirodigital%40gmail.com&amp;su=D%C3%BAvida%20sobre%20o%20Fluxon%20Analytics"
        target="_blank" rel="noopener noreferrer">
        Abrir no Gmail (nova aba)
    </a>
</p>

<p>
    Entre na sua conta Google se solicitado.
    Confira a mensagem e clique em Enviar no Gmail.
</p>

<label for="contato-email">
    Endereço da equipe
    <input id="contato-email" type="text" readonly value="fluxontesoureirodigital@gmail.com" autocomplete="off">
</label>

<button class="btn sec" id="copiar-contato" type="button">
    Copiar e-mail
</button>

<p id="contato-status" role="status" aria-live="polite"></p>

<p>
    <a href="mailto:fluxontesoureirodigital@gmail.com?subject=D%C3%BAvida%20sobre%20o%20Fluxon%20Analytics">
        Usar aplicativo de e-mail instalado
    </a>
</p>

<p>
    Você também pode selecionar o endereço acima
    e copiá-lo para enviar pelo serviço que preferir.
</p>

<script src="assets/js/contact.js" defer></script>

<h2>Como relatar um problema</h2>

<p>
    Descreva a tela, a ação realizada, a mensagem
    apresentada e o navegador utilizado.
    Se enviar uma captura, oculte dados pessoais.
</p>

<p>
    Não envie senhas, códigos de verificação,
    links de recuperação ou credenciais bancárias.
</p>

<?php elseif ($p === 'faq'): ?>

<h2>Perguntas frequentes</h2>

<details>
    <summary>Como recuperar minha senha?</summary>

    <p>
        Na tela de login, clique em Esqueci a senha
        e informe seu e-mail cadastrado. Use o link
        mais recente recebido para definir uma nova
        senha. O link expira em uma hora e só pode
        ser usado uma vez.
    </p>
</details>

<details>
    <summary>
        Não recebi o código ou o e-mail de recuperação.
        O que fazer?
    </summary>

    <p>
        Confira o endereço informado e a pasta de spam.
        Aguarde pelo menos um minuto antes de solicitar
        outro link de recuperação. Se continuar sem
        receber, contate a equipe pelo Fale conosco.
        A mensagem genérica da recuperação não confirma
        que uma conta existe.
    </p>
</details>

<details>
    <summary>
        Por que preciso de um código após informar a senha?
    </summary>

    <p>
        A confirmação em duas etapas verifica também
        o acesso ao e-mail cadastrado. O código vale
        por dez minutos, só pode ser usado uma vez
        e permite até cinco tentativas.
        Nunca compartilhe esse código.
    </p>
</details>

<details>
    <summary>
        Posso alterar meu e-mail em Minha conta?
    </summary>

    <p>
        O e-mail é o identificador principal da conta
        e não é alterado nesta tela. Você pode atualizar
        nome, telefone, cidade e ocupação.
        Em caso de problema com o endereço cadastrado,
        procure a equipe; nenhuma alteração de identidade
        será feita apenas por pedido sem verificação.
    </p>
</details>

<details>
    <summary>Quais fotos posso enviar?</summary>

    <p>
        Use JPG ou PNG de até 2 MB e no máximo
        3000 × 3000 pixels. A imagem é reduzida para
        até 512 pixels e reencodificada para remover
        os metadados. Você pode substituir ou remover
        a foto em Minha conta.
    </p>
</details>

<details>
    <summary>
        As notificações financeiras já são enviadas?
    </summary>

    <p>
        Nesta versão, os canais e horários são salvos
        como preferências. O envio automático de
        notificações financeiras ainda não está disponível.
        Os e-mails de acesso e recuperação de senha
        são enviados independentemente dessas preferências.
    </p>
</details>

<details>
    <summary>Como alterar minha senha?</summary>

    <p>
        Em Configurações, abra Outras configurações
        e depois Segurança e senha. Informe a senha
        atual, uma nova senha e a confirmação.
        Após a troca, entre novamente.
        As sessões anteriores perderão acesso e os
        códigos e links pendentes serão invalidados.
    </p>
</details>

<details>
    <summary>O que faz o ícone do olho?</summary>

    <p>
        Ele permite mostrar ou ocultar a senha digitada.
        Ao sair do campo, a senha volta a ficar oculta.
        Sem acionar o olho, apenas o último caractere
        digitado aparece brevemente antes de virar ponto.
    </p>
</details>

<details>
    <summary>Como excluir minha conta?</summary>

    <p>
        Em Configurações, clique em Revisar opções
        de exclusão. Leia os benefícios de manter
        a conta e os dados que serão removidos.
        Para continuar, solicite o código enviado
        ao e-mail cadastrado, informe os oito dígitos
        e marque a confirmação de exclusão permanente.
    </p>

    <p>
        Solicitar o código não exclui a conta.
        Você pode cancelar e manter seu cadastro.
        Após confirmar a exclusão, a aplicação
        não oferece recuperação da conta.
    </p>
</details>

<details>
    <summary>
        O Fluxon movimenta dinheiro ou paga impostos?
    </summary>

    <p>
        O projeto está em desenvolvimento acadêmico.
        O MVP proposto utiliza reservas virtuais para
        planejamento; elas não bloqueiam ou transferem
        dinheiro e não quitam tributos.
        O valor disponível será uma estimativa,
        não uma apuração de lucro ou tributos.
    </p>
</details>

<details>
    <summary>Onde encontro mais ajuda?</summary>

    <p>
        Acesse Fale conosco e escreva para
        fluxontesoureirodigital@gmail.com.
        Não envie dados de acesso.
    </p>
</details>

<?php else: ?>

<h2>Sobre o sistema</h2>

<dl>
    <dt><strong>Nome</strong></dt>
    <dd>Fluxon Analytics — Tesoureiro Digital</dd>

    <dt><strong>Versão desta entrega</strong></dt>
    <dd>0.3.0 acadêmica — Perfil, Segurança e Suporte</dd>

    <dt><strong>Finalidade</strong></dt>
    <dd>
        Apoiar a organização financeira de autônomos
        e pequenos negócios. Esta entrega oferece
        acesso, perfil e configurações iniciais.
    </dd>

    <dt>
        <strong>
            Desenvolvimento e responsabilidade pelo projeto
        </strong>
    </dt>
    <dd>
        Equipe Fluxon Analytics: Igor Silva,
        Jeferson Rios Pereira e Deric Fahr Diniz.
    </dd>

    <dt><strong>Contexto</strong></dt>
    <dd>
        Projeto acadêmico do curso de Análise
        e Desenvolvimento de Sistemas do
        Centro Universitário Piaget.
    </dd>

    <dt><strong>Distribuição</strong></dt>
    <dd>
        Aplicação Web acadêmica em PHP e MySQL,
        executada em servidor compatível.
        Não é um aplicativo distribuído por loja.
    </dd>

    <dt><strong>Componentes externos</strong></dt>
    <dd>
        PHPMailer para envio de e-mails por SMTP.
        Login Google depende da configuração
        da integração.
    </dd>

    <dt><strong>Contato</strong></dt>
    <dd>
        <a href="mailto:fluxontesoureirodigital@gmail.com">
            fluxontesoureirodigital@gmail.com
        </a>
    </dd>
</dl>

<p>
    A versão 0.3.0 identifica este incremento;
    não representa certificação de segurança
    nem conclusão de toda a Fase 2.
</p>

<?php endif; ?>

<?php
$titles = [
    'suporte' => 'Ajuda e suporte',
    'faq' => 'Perguntas frequentes',
    'sobre' => 'Sobre o sistema',
];

card($titles[$p] ?? 'Ajuda e suporte', ob_get_clean());
