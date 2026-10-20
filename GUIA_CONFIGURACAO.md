# Configuração e atualização

## Instalação nova

1. Use PHP 8.1 ou superior com `pdo_mysql`, `curl`, `openssl`, `gd` e `fileinfo`. Execute o Composer com o mesmo PHP do XAMPP.
2. Execute `composer install` na raiz. O `composer.lock` existente fixa a versão de PHPMailer; `vendor/` será recriado.
3. Importe `database/schema.sql` em banco novo. Não use este script sobre a instalação existente.
4. Copie `config.local.example.php` para `config.local.php`. Configure o DSN com a porta real do MySQL: 3306 é comum; o exemplo usa 3307, como o ambiente de teste do projeto.
5. Configure `base` com a URL exata do `index.php`. O exemplo usa `http://localhost/fluxon_projeto-main/index.php`.
6. Inicie os serviços e abra a aplicação.

## Instalação existente

Faça backup da pasta e exporte o banco antes de atualizar. Guarde seu `config.local.php` fora da cópia que vai ao GitHub. Ele não está no pacote revisado.

Substitua a aplicação pela pasta revisada, recoloque seu `config.local.php` e execute `composer install`. Use uma pasta de teste primeiro. Não misture os arquivos antigos de `app/` com os novos: os módulos agora ficam em subpastas.

Se todas as funcionalidades já estavam funcionando, esta organização **não exige migração adicional**. O schema completo foi corrigido apenas para instalações novas.

Para uma base antiga, examine as colunas/tabelas antes de aplicar apenas as migrações pendentes, nesta ordem:

| Arquivo                          | Quando necessário                                                                      |
| -------------------------------- | -------------------------------------------------------------------------------------- |
| `migracao_001.sql`               | Faltam as colunas de verificação de e-mail, versão de sessão e último reset em `users` |
| `migracao_002_perfil.sql`        | Falta `user_profiles`                                                                  |
| `migracao_003_configuracoes.sql` | Faltam os campos fiscais e de reserva em `user_profiles`                               |
| `migracao_003_avaliacoes.sql`    | Falta `user_reviews`                                                                   |
| `migracao_004_exclusao.sql`      | Falta `account_deletion_tokens`                                                        |

Os dois arquivos com prefixo 003 são incrementos distintos. Seus nomes foram preservados. Os scripts com `ALTER TABLE ADD COLUMN` não devem ser repetidos em bases já atualizadas. Nenhuma migração foi executada no banco do usuário durante esta revisão.

## SMTP

Preencha `smtp.host`, `port`, `user`, `pass`, `secure` e `from` no arquivo local. Use o remetente autorizado pelo provedor e senha de aplicativo quando exigida. Porta 587 costuma usar `tls`; porta 465, `ssl`. Não desative a validação de certificados.

O login e o cadastro precisam do código por e-mail para concluir o acesso. A exclusão usa um código de oito dígitos, válido por dez minutos e com até cinco tentativas. Solicitar o código não exclui a conta.

## Google

O acesso Google é opcional. Configure o cliente OAuth Web e a URI de retorno exatamente igual a `base` acrescida de `?p=gcb`, por exemplo `http://localhost/fluxon_projeto-main/index.php?p=gcb`. Preencha `google.id` e `google.secret` localmente. Mesmo após o retorno do Google, o projeto exige o código de e-mail.

## GitHub e arquivos privados

O pacote não contém `config.local.php`, `emails.log` nem `vendor/`. A remoção do pacote não apaga arquivos ou histórico do repositório original. Se já estiverem rastreados, remova-os do índice do Git com `git rm --cached --ignore-unmatch config.local.php emails.log` e `git rm -r --cached --ignore-unmatch vendor` antes do commit, preservando as cópias locais necessárias. Credenciais anteriormente publicadas precisam ser substituídas no provedor.

Confira as regras Apache: `app/`, `database/`, `tests/` e `docs/` não precisam de acesso HTTP direto; configuração e arquivos internos também são bloqueados na raiz. O fluxo deve ser iniciado por `index.php`.

## Aceitação manual

- Cadastro, login e Google com confirmação por e-mail.
- Senha incorreta, bloqueio, código expirado/incorreto e recuperação de senha.
- Salvar perfil, foto, alíquota e preferências; reabrir e conferir persistência.
- Conferir Dashboard e Configurações com e sem mensagens de sucesso.
- Abrir Segurança, Suporte, FAQ, Sobre, Avaliação e Acessibilidade no computador e celular.
- Conferir olho da senha, revelação breve do último caractere, ocultação ao sair e autofill.
- Trocar tema/fonte e reabrir a página no mesmo navegador.
- Em conta descartável: abrir política de exclusão, cancelar, solicitar código, rejeitar código incorreto e confirmar exclusão. Conferir que o login deixa de funcionar e dados vinculados são removidos.
- Conferir respostas de acesso negado para configuração, SQL, testes e arquivos internos pelo Apache.

Não use contas reais para o teste destrutivo de exclusão. Os testes automatizados incluídos não executam esse fluxo.
