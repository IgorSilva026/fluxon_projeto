# Fluxon Analytics — Tesoureiro Digital

<<<<<<< fase3-dashboard-compacto
Projeto acadêmico de Análise e Desenvolvimento de Sistemas do Centro Universitário Piaget. O Fluxon ajuda a acompanhar movimentações, reservas e saldo disponível estimado em um ambiente financeiro simulado.
=======
Projeto acadêmico em PHP e PostgreSQL (Supabase) para acesso, perfil e configurações iniciais de organização financeira.
>>>>>>> main

## O que está disponível

- **Painel:** indicadores de saldo registrado, reservas ativas e disponível estimado, com gráficos e filtros.
- **Cadastros e movimentações:** contas simuladas, categorias, regras de provisão, lançamentos manuais, importação fictícia sem duplicação e estorno.
- **Extrato e relatórios:** consulta com filtros, ordenação e paginação; exportação em PDF e XLSX.
- **Acesso e configurações:** cadastro, verificação por e-mail, código de acesso, recuperação de senha, Google OAuth opcional, perfil, foto, preferências, segurança, ajuda, avaliação e exclusão de conta.
- **Interface:** visual compacto em todo o sistema, navegação compartilhada entre as telas financeiras e o perfil, opções de tema e tamanho de fonte.

## Rodar no XAMPP

Requisitos: PHP 8.1+, Apache, Composer e um projeto PostgreSQL no Supabase. Habilite no PHP as extensões `pdo_pgsql`, `pgsql`, `curl`, `openssl`, `gd`, `fileinfo`, `iconv` e `zip`. A extensão ZIP é usada na exportação XLSX.

<<<<<<< fase3-dashboard-compacto
1. Coloque o projeto em `C:\xampp\htdocs\fluxon_projeto-main`. No terminal, dentro da pasta, rode `composer install`.
2. **Banco novo:** no SQL Editor do Supabase, execute `database/schema.sql` e depois `database/migracao_fase3.sql`. **Banco PostgreSQL existente, antes da Fase 3:** execute somente `database/migracao_fase3.sql`. Se já aplicou essa migração e só quer atualizar o visual, pule este passo.
3. Copie `.env.example` para `.env`. Preencha os parâmetros do banco exibidos em **Connect → Session pooler** e configure o SMTP para os fluxos por e-mail. Mantenha `APP_BASE_URL` igual ao endereço real do `index.php`. Como alternativa, use `config.local.example.php` como modelo para `config.local.php`.
4. Inicie o Apache e abra [o Fluxon local](http://localhost/fluxon_projeto-main/index.php).

A autenticação é feita pelo Fluxon em PHP. O Supabase fornece o banco. Os dados do MySQL não são importados automaticamente; os scripts de `database/mysql_legado/` são apenas para a versão antiga.

## Testes e documentação
=======
Requer PHP 8.1+, Composer e um projeto no [Supabase](https://supabase.com) (usado como banco PostgreSQL; a autenticação continua no PHP). No PHP do XAMPP habilite `pdo_pgsql`, `pgsql`, `curl`, `openssl`, `gd` e `fileinfo` (em `php.ini`, remova o `;` das linhas `extension=...`). GD e Fileinfo são usados nas fotos.

1. Extraia a pasta `fluxon_projeto-main` em `C:\xampp\htdocs`.
2. Na raiz do projeto, execute `composer install`.
3. No Supabase, abra **SQL Editor**, cole o conteúdo de `database/schema.sql` e execute. Ele cria todas as tabelas e liga o RLS para a API pública não enxergá-las.
4. Copie `.env.example` para `.env` e preencha banco (**Connect > Session pooler**), URL, SMTP e Google, se utilizado. O `config.local.php` continua funcionando como alternativa; se os dois existirem, o `.env` vence.
5. Inicie o Apache. Acesse `http://localhost/fluxon_projeto-main/index.php`.

**Vindo da versão MySQL:** os dados do MySQL não são migrados automaticamente. Veja o [guia de configuração](GUIA_CONFIGURACAO.md). Não execute os scripts de `database/mysql_legado/` no Supabase.
>>>>>>> main

Execute `composer test` para verificar a sintaxe e as regras de validação. Para testar o fluxo completo, use dados fictícios e siga [Instalação e demonstração da Fase 3](INSTALAR_FASE3.md).

- [Requisitos da Fase 3](docs/FASE3_REQUISITOS.md)
- [Validação da Fase 3](docs/FASE3_VALIDACAO.md)
- [Integração do dashboard](INTEGRACAO_DASHBOARD.md)

<<<<<<< fase3-dashboard-compacto
As reservas são virtuais. O Fluxon não conecta bancos reais, movimenta dinheiro, paga tributos ou envia notificações agendadas. O disponível estimado não considera obrigações futuras que ainda não foram registradas.
=======
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
>>>>>>> main

Mantenha `.env`, `config.local.php`, logs e `vendor/` fora do Git. O Apache deve aplicar as regras dos arquivos `.htaccess`. 

**Equipe:** Igor Silva, Jeferson Rios Pereira e Deric Fahr Diniz.
 