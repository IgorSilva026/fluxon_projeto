# Visualização da senha

Atualização sobre o Passo 2, Segurança e senha. Mescle assets/ e substitua index.php na pasta C:\xampp\htdocs\fluxon_projeto-main. Não apague a pasta atual. Não há alteração de banco ou credenciais. Use Ctrl+F5 após copiar.

Campos PHP de login, cadastro, recuperação e troca recebem botão acessível Mostrar/Ocultar. Por padrão o input permanece password; uma camada visual exibe somente o último caractere digitado por 700 ms e depois pontos. Colagem e autofill permanecem mascarados. A senha volta a ficar oculta ao sair do campo ou trocar de aba. O botão é type=button e não envia o formulário. Sem JavaScript, os campos mantêm o comportamento nativo. A aparência dos pontos não é criptografia: o armazenamento continua utilizando hash no servidor.

Conferido: sintaxe JavaScript/PHP e testes DOM de digitação, temporizador, botão, colagem e blur. Renderização em navegador real/XAMPP ainda deve ser verificada; não foi possível instalar o navegador automatizado neste ambiente. Testar também preenchimento automático, teclado, celular e senhas longas. Em modo de alto contraste, mantém-se a máscara nativa em vez da camada visual.
