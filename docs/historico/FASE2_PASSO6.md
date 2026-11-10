# Exclusão segura — Passo 6

Mescle os arquivos em C:\xampp\htdocs\fluxon_projeto-main. Preserve config.local.php, vendor e os módulos anteriores.
No phpMyAdmin, banco fluxon, importe SOMENTE database/migracao_004_exclusao.sql. Não importe schema.sql no banco existente. A nova tabela de código não apaga dados durante a instalação.

## Teste obrigatório em conta de teste

Não exclua a conta principal para testar. Cadastre um usuário de teste com e-mail que você controla. Faça login e confirme o acesso normalmente. Adicione dados de perfil e uma avaliação para testar a remoção em cascata.
Abra Excluir conta no painel. Antes de enviar código, tente confirmar: a exclusão deve ser recusada. Solicite código, informe um código errado uma vez e confirme que a conta permanece. Cancele e confira que o código anterior não funciona mais. Solicite outro código após o intervalo mínimo de um minuto.
Para testar o caminho final, informe o código correto e marque a confirmação explícita. O aplicativo deve retornar ao início com mensagem de exclusão. Login antigo deve ser recusado. No banco, confira que o usuário de teste e suas linhas em user_profiles, user_reviews, tokens e account_deletion_tokens foram removidos (chaves estrangeiras ON DELETE CASCADE). Sessões desse usuário devem perder acesso protegido. Não envie código ao chat.

## Funcionamento

Código de oito dígitos gerado com random_int, armazenado como password_hash, válido por dez minutos, cinco erros no máximo, um pedido por minuto por conta. Solicitar código não exclui. Falha de SMTP cancela o código emitido. Uma sessão autenticada válida, CSRF, código e checkbox são obrigatórios para exclusão. A propriedade da conta vem da sessão, nunca do formulário. DELETE executado em transação com bloqueio do usuário e token; erros revertem a operação. A conta principal e outros usuários não são selecionados por identificador fornecido pelo cliente.

Cópias externas/backups, logs da infraestrutura e mensagens já entregues ficam sob gestão operacional, fora do DELETE do banco ativo. O operador deverá definir retenção e descarte de backups na política de privacidade; este módulo não demonstra conformidade legal completa.

## Validação

Sintaxe PHP e revisão estática do fluxo transacional verificadas. Testes com PHP/MySQL, SMTP e navegador ainda precisam ser executados no XAMPP. Teste de formato disponível:

    C:\xampp\php\php.exe tests\deletion_validation.php

Próximo incremento: Política de Privacidade, Termos de Uso e revisão das medidas de proteção dos dados.
