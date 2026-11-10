# Contato pelo navegador

Substitua app/support_view.php e copie assets/js/contact.js para a pasta correspondente dentro de C:\xampp\htdocs\fluxon_projeto-main. Preserve os demais arquivos. Não há SQL ou configuração adicional.

Use Ctrl+F5, abra Ajuda e suporte e teste:

- Abrir no Gmail abre uma nova aba com destinatário e assunto. Pode solicitar login. O usuário redige e envia; o Fluxon não envia automaticamente.
- Copiar e-mail usa a área de transferência. Se o navegador bloquear, o endereço é selecionado e uma mensagem explica como copiá-lo manualmente.
- Usar aplicativo instalado mantém o comportamento mailto, dependente do dispositivo.

Implementação com links HTML e JavaScript padrão, destinada a Opera GX e Chrome. Sintaxe e testes DOM verificados. Abertura real de Gmail e permissões de clipboard devem ser conferidas nos navegadores do usuário.
