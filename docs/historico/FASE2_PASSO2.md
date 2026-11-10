# Fase 2 — Segurança e senha

## Aplicar

Este pacote é SOMENTE uma atualização do incremento Minha Conta já instalado.
Copie index.php e mescle o conteúdo de app/ e tests/ na pasta C:\xampp\htdocs\fluxon_projeto-main.
Preserve config.local.php, vendor, arquivos do perfil e dados do banco. Não é necessário importar SQL neste passo.
Abra o painel após login e clique em Segurança e senha.

## Comportamento

Política visível, campos obrigatórios, confirmação da senha atual e da nova senha, recusa de repetição e senha fraca. Limite de 72 bytes evita truncamento pelo bcrypt. Cinco erros de senha atual provocam bloqueio de 15 minutos compartilhado com login. Campos de senha nunca são repopulados nem registrados em log.
Atualização em transação com bloqueio da linha do usuário, hash seguro, incremento da versão de sessão e invalidação dos tokens. A sessão atual é limpa; sessões antigas serão recusadas no próximo acesso protegido. Conta criada só com Google define senha pela recuperação já existente.

## Validar no XAMPP

No terminal:

    C:\xampp\php\php.exe tests\password_validation.php

Teste manual: senha atual errada, nova senha fraca, confirmação divergente e repetição devem ser recusadas. Uma troca válida deve redirecionar ao login. A senha anterior deve ser rejeitada e a nova deve exigir código por e-mail. Abra outra sessão em janela anônima antes da troca e confira que o acesso protegido é recusado depois. Confirme também Minha Conta e recuperação. Não envie senhas ao chat.

## Estado da validação

Sintaxe PHP verificada por parser. Testes de execução com PHP/MySQL e navegador dependem do XAMPP; não foram executados neste ambiente. Google e entrega SMTP dependem das credenciais locais. Outros blocos da Fase 2 continuam pendentes.
