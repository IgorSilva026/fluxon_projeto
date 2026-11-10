# Fluxon Analytics — Tesoureiro Digital

Projeto acadêmico em PHP e PostgreSQL (Supabase) para acesso, perfil e configurações iniciais de organização financeira.

## Funcionalidades

- Cadastro, login, Google OAuth, código por e-mail e recuperação de senha.
- Perfil, foto privada, dados fiscais e preferências de notificações.
- Troca de senha, avaliação, acessibilidade, ajuda, FAQ e informações do projeto.
- Exclusão de conta com política de permanência, cancelamento e código por e-mail.

Fluxo de caixa, transações, automação de reserva fiscal, alertas financeiros e cobrança de assinatura ainda não estão integrados. O plano apresentado na interface é demonstrativo.

## Instalação

Requer PHP 8.1+, Composer e um projeto no [Supabase](https://supabase.com) (usado como banco PostgreSQL; a autenticação continua no PHP). No PHP do XAMPP habilite `pdo_pgsql`, `pgsql`, `curl`, `openssl`, `gd` e `fileinfo` (em `php.ini`, remova o `;` das linhas `extension=...`). GD e Fileinfo são usados nas fotos.

1. Extraia a pasta `fluxon_projeto-main` em `C:\xampp\htdocs`.
2. Na raiz do projeto, execute `composer install`.
3. No Supabase, abra **SQL Editor**, cole o conteúdo de `database/schema.sql` e execute. Ele cria todas as tabelas e liga o RLS para a API pública não enxergá-las.
4. Copie `.env.example` para `.env` e preencha banco (**Connect > Session pooler**), URL, SMTP e Google, se utilizado. O `config.local.php` continua funcionando como alternativa; se os dois existirem, o `.env` vence.
5. Inicie o Apache. Acesse `http://localhost/fluxon_projeto-main/index.php`.

**Vindo da versão MySQL:** os dados do MySQL não são migrados automaticamente. Veja o [guia de configuração](GUIA_CONFIGURACAO.md). Não execute os scripts de `database/mysql_legado/` no Supabase.

O ZIP para GitHub não contém credenciais, banco de usuários, logs nem `vendor/`. Instale as dependências e mantenha a configuração privada apenas no ambiente local.

## Organização

| Caminho                      | Responsabilidade                                            |
| ---------------------------- | ----------------------------------------------------------- |
| `index.php`                  | Entrada, utilitários compartilhados e rotas de autenticação |
| `app/controllers/`           | Fluxos das áreas internas e verificações de sessão          |
| `app/services/`              | Validação e regras de negócio                               |
| `app/repositories/`          | Persistência de perfil e avaliação                          |
| `app/views/`                 | Telas PHP/HTML                                              |
| `assets/css/` e `assets/js/` | Estilos e comportamento no navegador                        |
| `html/`                      | Telas de autenticação (templates lidos pelo `index.php`)    |
| `database/`                  | Schema PostgreSQL (Supabase); `mysql_legado/` é só histórico |
| `tests/`                     | Verificação de sintaxe e validações sem banco               |
| `docs/`                      | Revisão, lista de remoções e documentação histórica         |

As rotas continuam no formato `index.php?p=perfil`, `?p=painel`, etc. Não é necessário alterar links públicos por causa da reorganização interna.

## Verificações

```sh
composer test
```

Os testes não enviam e-mails nem excluem contas. Os resultados desta revisão, os limites da validação e o roteiro manual estão em [docs/REVISAO.md](docs/REVISAO.md).

## GitHub

Versione o código, `composer.json`, `composer.lock`, testes e documentação. O `.gitignore` exclui configuração local, dependências, logs e arquivos temporários. Antes do commit, confira `git status` e `git diff --cached`.

As proteções `.htaccess` exigem Apache com `AllowOverride` habilitado. Outro servidor precisa de regras equivalentes. O servidor embutido do PHP não aplica essas proteções.

Equipe: Igor Silva, Jeferson Rios Pereira e Deric Fahr Diniz. Projeto acadêmico de Análise e Desenvolvimento de Sistemas do Centro Universitário Piaget.
