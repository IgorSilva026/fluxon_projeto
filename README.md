# Fluxon Analytics — Fase 1 Controle de Acesso

Aplicação PHP/MySQL com splash, cadastro, login tradicional, acesso Google, verificação por e-mail e recuperação de senha.

Leia **GUIA_CONFIGURACAO.md** para instalação, migração, configuração de SMTP/Google e roteiro de testes.

## Alterações desta revisão
- Envio real por SMTP usando PHPMailer; removido o modo que expunha códigos e links.
- Segredos em config.local.php, ignorado pelo Git.
- Cadastro solicita confirmação do e-mail antes de liberar o painel.
- Recuperação invalida sessões e tokens anteriores após troca da senha.
- Intervalo mínimo de 60 segundos entre solicitações de recuperação por conta.
- Cookies HttpOnly/SameSite, prevenção de cache e referer para links de recuperação.
- OAuth com consumo único de state e tratamento de falhas HTTP.
- Migração para bancos existentes, mantendo usuários.
- Remoção de emails.log do projeto e proteção Apache adicional.

## Validação
Revisão estática e análise sintática dos arquivos PHP. Execução com PHP/MySQL, instalação Composer, envio SMTP e login Google ainda precisam ser validados no XAMPP. Não declarar a atividade concluída sem preencher o roteiro do guia.

## Estrutura
- index.php: telas e rotas reais
- config.php: valores padrão, sem segredos
- config.local.example.php: modelo de configuração local
- composer.json: dependências PHP
- database/schema.sql: instalação nova
- database/migracao_001.sql: atualização do banco existente, uma vez
- prototipo_html/: demonstração estática, sem autenticação real
