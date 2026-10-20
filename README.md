# Fluxon Analytics — Tesoureiro Digital

Projeto acadêmico em PHP e MySQL para acesso, perfil e configurações iniciais de organização financeira.

## Funcionalidades

- Cadastro, login, Google OAuth, código por e-mail e recuperação de senha.
- Perfil, foto privada, dados fiscais e preferências de notificações.
- Troca de senha, avaliação, acessibilidade, ajuda, FAQ e informações do projeto.
- Exclusão de conta com política de permanência, cancelamento e código por e-mail.

Fluxo de caixa, transações, automação de reserva fiscal, alertas financeiros e cobrança de assinatura ainda não estão integrados. O plano apresentado na interface é demonstrativo.

## Instalação

Requer PHP 8.1+, MySQL/MariaDB com InnoDB e Composer. Habilite PDO MySQL, cURL, OpenSSL, GD e Fileinfo no PHP do XAMPP. GD e Fileinfo são usados nas fotos.

1. Extraia a pasta `fluxon_projeto-main` em `C:\xampp\htdocs`.
2. Na raiz do projeto, execute `composer install`.
3. Para instalação nova, importe somente `database/schema.sql`. Ele inclui todas as tabelas desta entrega.
4. Copie `config.local.example.php` para `config.local.php` e configure banco, URL, SMTP e Google, se utilizado.
5. Inicie Apache e MySQL. Acesse `http://localhost/fluxon_projeto-main/index.php`.

**Atualização de uma instalação existente:** preserve seu `config.local.php`, faça backup do banco e siga o [guia de configuração](GUIA_CONFIGURACAO.md). Não reimporte o schema nem repita migrações já aplicadas.

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
| `database/`                  | Schema completo e migrações existentes                      |
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
