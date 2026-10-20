# Avaliação do aplicativo

Atualização sobre Perfil, Segurança e Suporte. Preserve config.local.php, vendor e demais arquivos existentes. Mescle os diretórios do pacote em C:\xampp\htdocs\fluxon_projeto-main.

No phpMyAdmin, selecione fluxon e importe SOMENTE database/migracao_003_avaliacoes.sql. Ela cria user_reviews, sem apagar usuários. É reaplicável. Não importe schema.sql no banco existente.

Use Ctrl+F5 e abra Avaliar aplicativo no painel. Escolha uma nota de 1 a 5 estrelas e comentário opcional até 1000 caracteres. Cada conta mantém uma avaliação, editável e excluível pelo próprio titular. Uma edição atualiza o registro existente. Para excluir é obrigatório marcar a confirmação. Não existe painel administrativo ou publicação de comentários neste incremento; a equipe responsável pela operação pode analisar os registros no banco, com acesso restrito.

Teste: criar avaliação, recarregar, editar nota/comentário, excluir com confirmação e verificar ausência após recarga. Teste com outra conta: ela não deve visualizar ou editar a avaliação da primeira. Sem login, a rota deve redirecionar. Formulário exige nota, limita comentário, usa CSRF e backend valida entradas. Identidade vem somente da sessão.

Teste automatizado de validação, para executar localmente:

    C:\xampp\php\php.exe tests\review_validation.php

Validação desta entrega: sintaxe PHP e revisão estática dos parâmetros de propriedade e rotas. PHP/MySQL e interface real ainda dependem dos testes no XAMPP. Os testes de execução não foram realizados neste ambiente.

Próximos itens da Fase 2: acessibilidade (fontes/temas), exclusão segura, políticas de privacidade/termos e demais medidas de proteção de dados. Preferências de notificações financeiras continuam sem envio automático.
