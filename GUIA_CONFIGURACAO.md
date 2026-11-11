> Instale também a migração financeira e confira [INSTALAR_FASE3.md](INSTALAR_FASE3.md).

# Configuração e atualização

## Instalação nova

1. Use PHP 8.1 ou superior com `pdo_pgsql`, `pgsql`, `curl`, `openssl`, `gd` e `fileinfo`. No XAMPP, remova o `;` de `extension=pdo_pgsql` e `extension=pgsql` no `php.ini` e reinicie o Apache. Execute o Composer com o mesmo PHP do XAMPP.
2. Execute `composer install` na raiz. O `composer.lock` existente fixa a versão de PHPMailer; `vendor/` será recriado.
3. No Supabase, crie um projeto (anote a senha do banco). Abra **SQL Editor**, cole `database/schema.sql` e execute. O script pode ser repetido sem erro e já liga o RLS em todas as tabelas.
4. Copie `.env.example` para `.env` (na raiz, ao lado do `index.php`). No Supabase clique em **Connect** e escolha **Session pooler**: copie o host para `DB_HOST`, a porta 5432 para `DB_PORT` e o usuário `postgres.SEU_PROJECT_REF` para `DB_USER`; `DB_PASS` é a senha do passo 3 (sem aspas e sem codificar caracteres especiais). O `config.local.php` ainda é aceito; se os dois existirem, o `.env` vence.
5. Configure `base` com a URL exata do `index.php`. O exemplo usa `http://localhost/fluxon_projeto-main/index.php`.
6. Inicie o Apache e abra a aplicação.

### Qual conexão do Supabase usar

| Conexão                         | Usar?                                                                                     |
| ------------------------------- | ----------------------------------------------------------------------------------------- |
| Session pooler (porta 5432)     | **Sim.** Funciona em IPv4 (rede de casa/faculdade) e aceita prepared statements.           |
| Direct connection (`db.REF...`) | Só se sua rede tiver IPv6. Caso contrário o PHP não conecta.                              |
| Transaction pooler (porta 6543) | **Não.** Quebra os prepared statements do PDO.                                            |

O PHP conecta com o usuário do banco, não com as chaves `anon`/`service_role` do Supabase. Não coloque essas chaves no projeto. A senha do banco só vai em `.env` (ou `config.local.php`).

## Vindo da versão MySQL

Os dados do MySQL não são migrados automaticamente: crie o banco novo no Supabase e cadastre as contas de novo (em ambiente acadêmico é o caminho mais simples). Se precisar preservar contas reais, a migração de `users` exige cuidado com o `id` e com a sequência do Postgres, então faça-a à parte e com backup.

Faça backup da pasta antes de atualizar e guarde seu `config.local.php` fora da cópia que vai ao GitHub. Substitua a aplicação pela pasta revisada, recoloque o arquivo local (trocando o bloco `db` pelo do Supabase) e execute `composer install`. Os scripts em `database/mysql_legado/` são apenas histórico: **não** execute no Supabase.

## SMTP

Preencha `smtp.host`, `port`, `user`, `pass`, `secure` e `from` no arquivo local. Use o remetente autorizado pelo provedor e senha de aplicativo quando exigida. Porta 587 costuma usar `tls`; porta 465, `ssl`. Não desative a validação de certificados.

O login e o cadastro precisam do código por e-mail para concluir o acesso. A exclusão usa um código de oito dígitos, válido por dez minutos e com até cinco tentativas. Solicitar o código não exclui a conta.

## Google

O acesso Google é opcional. Configure o cliente OAuth Web e a URI de retorno exatamente igual a `base` acrescida de `?p=gcb`, por exemplo `http://localhost/fluxon_projeto-main/index.php?p=gcb`. Preencha `google.id` e `google.secret` localmente. Mesmo após o retorno do Google, o projeto exige o código de e-mail.

## GitHub e arquivos privados

O pacote não contém `config.local.php`, `emails.log` nem `vendor/`. A remoção do pacote não apaga arquivos ou histórico do repositório original. Se já estiverem rastreados, remova-os do índice do Git com `git rm --cached --ignore-unmatch config.local.php emails.log` e `git rm -r --cached --ignore-unmatch vendor` antes do commit, preservando as cópias locais necessárias. Credenciais anteriormente publicadas precisam ser substituídas no provedor.

Confira as regras Apache: `app/`, `database/`, `html/`, `tests/` e `docs/` não precisam de acesso HTTP direto; configuração e arquivos internos também são bloqueados na raiz. O fluxo deve ser iniciado por `index.php`.

## Aceitação manual

- Cadastro, login e Google com confirmação por e-mail (as telas vêm de `html/`).
- No Supabase, em **Table Editor**, conferir que as tabelas aparecem com RLS ativo e que a chave `anon` não lê `users`.
- Senha incorreta, bloqueio, código expirado/incorreto e recuperação de senha.
- Salvar perfil, foto, alíquota e preferências; reabrir e conferir persistência.
- Conferir Dashboard e Configurações com e sem mensagens de sucesso.
- Abrir Segurança, Suporte, FAQ, Sobre, Avaliação e Acessibilidade no computador e celular.
- Conferir olho da senha, revelação breve do último caractere, ocultação ao sair e autofill.
- Trocar tema/fonte e reabrir a página no mesmo navegador.
- Em conta descartável: abrir política de exclusão, cancelar, solicitar código, rejeitar código incorreto e confirmar exclusão. Conferir que o login deixa de funcionar e dados vinculados são removidos.
- Conferir respostas de acesso negado para configuração, SQL, testes e arquivos internos pelo Apache.

Não use contas reais para o teste destrutivo de exclusão. Os testes automatizados incluídos não executam esse fluxo.
