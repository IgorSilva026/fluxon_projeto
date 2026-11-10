# Fase 2 — Primeiro incremento Minha Conta

## Instalar no projeto existente

1. Faça backup da pasta do projeto e exporte o banco fluxon pelo phpMyAdmin.
2. Copie o conteúdo deste pacote para C:\xampp\htdocs\fluxon_projeto. Mantenha seu config.local.php e a pasta vendor atuais. Não há credenciais neste pacote.
3. No phpMyAdmin, selecione fluxon, abra Importar e escolha database/migracao_002_perfil.sql. O arquivo cria somente user_profiles; não apaga usuários e pode ser reaplicado.
4. Não importe schema.sql em banco existente. Ele é somente para instalação nova. A base pressupõe as colunas da Fase 1 já existentes (email_verificado_em, versao_sessao e ultimo_reset).
5. Foto requer as extensões PHP GD e fileinfo. Em C:\xampp\php\php.ini, procure extension=gd e retire o ponto e vírgula inicial, se estiver desativada. Faça o mesmo para fileinfo se necessário. Reinicie o Apache depois de alterar php.ini. O limite upload_max_filesize deve ser pelo menos 2M e post_max_size maior que 2M (ex.: 8M).
6. Abra http://localhost/fluxon_projeto/, faça login, confirme o código de e-mail e clique em Minha conta.

## Implementado

- Dados pessoais e data de cadastro consultáveis pelo sistema (nome/e-mail exibidos no formulário).
- Atualização de nome, telefone, cidade e ocupação. E-mail não é aceito nas alterações do backend. CPF não é coletado nesta base.
- Foto JPG/PNG até 2 MB e 3000 × 3000, reduzida para até 512 pixels, reencodificada em JPEG sem metadados. Foto pode ser substituída ou removida. Foto armazenada no banco e entregue somente à sessão do proprietário.
- Preferências de app/e-mail e frequência evento/diária/programada, com horário de Brasília salvo para os modos diários. Os dois modos diários usam o horário escolhido; nenhum agendador ou envio financeiro é implementado neste incremento. E-mails de segurança não são desativados pelas preferências.
- Separação de controlador, serviço, persistência e tela em app/.
- Autorização, versão da sessão, CSRF e consultas parametrizadas.

## Testar localmente

No terminal do projeto:

    C:\xampp\php\php.exe tests\profile_validation.php

Esse teste cobre validação, campos de identificação ignorados e preferências. Não substitui testes com banco, navegador e upload.

Roteiro manual:

- Acessar ?p=perfil e ?p=foto sem login deve encaminhar ao login.
- Editar nome, telefone, cidade e ocupação, salvar, atualizar a página e confirmar persistência.
- Confirmar e-mail de identificação inalterado.
- Enviar foto válida, substituir e remover; conferir após recarregar.
- Recusar arquivo que não seja imagem, imagem acima do limite e dimensões excessivas.
- Configurar canais/horários, recarregar e confirmar persistência; horário inválido deve ser recusado.
- Entrar com outro usuário e confirmar que vê somente os próprios dados/foto.
- Confirmar mensagem clara quando tabela não foi instalada ou upload falhou.
- Confirmar que login, código por e-mail e recuperação da Fase 1 continuam funcionando.

## Validação desta entrega

Análise sintática PHP e revisão estática concluídas. Não foi executado PHP/MySQL nem realizado teste visual do aplicativo neste ambiente. Testes de validação fornecidos para execução no XAMPP. O teste real de upload requer GD.

## Próximos incrementos para concluir a Fase 2

Política/troca segura de senha, suporte/FAQ/sobre, avaliação gerenciável, acessibilidade de fonte/temas, exclusão por confirmação segura, privacidade/termos e medidas de proteção de dados. Este pacote NÃO representa a Fase 2 completa.

## Git

Mantenha config.local.php e vendor fora do commit. Inclua app/, tests/, assets/css/style.css, index.php, database/schema.sql, database/migracao_002_perfil.sql, README.md, FASE2_PASSO1.md e o modelo de configuração no commit da sua branch da Fase 2. Não execute git add indiscriminadamente sem revisar o status.
