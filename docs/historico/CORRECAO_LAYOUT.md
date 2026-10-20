# Largura estável das telas

Substitua index.php e copie assets/css/layout.css na pasta C:\xampp\htdocs\fluxon_projeto-main. Não apague módulos ou outros estilos. Atualize com Ctrl+F5.

O contêiner externo agora existe em todas as respostas, com ou sem mensagem. Sua largura depende da rota, não do conteúdo do formulário nem da mensagem. Acessibilidade, perfil e segurança usam 680px; avaliação/FAQ/sobre, 720px; login e painel, 420px. Todas encolhem para a largura disponível em telas menores.

Mudar fonte altera a altura e as quebras de linha, como esperado, mas não a largura configurada da página. A área de rolagem permanece reservada para evitar deslocamento horizontal. Navegar entre rotas com larguras diferentes continua intencional.

Validação: sintaxe PHP e revisão da estrutura única. Conferir no navegador: Acessibilidade nos três tamanhos, alternância de tema, Perfil e Avaliação antes/depois de salvar. Sem migração SQL.
