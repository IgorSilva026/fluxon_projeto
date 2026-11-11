# Fluxon Analytics — Tesoureiro Digital

Projeto acadêmico de Análise e Desenvolvimento de Sistemas do Centro Universitário Piaget. O Fluxon ajuda a acompanhar movimentações, reservas e saldo disponível estimado em um ambiente financeiro simulado.

## O que está disponível

- **Painel:** indicadores de saldo registrado, reservas ativas e disponível estimado, com gráficos e filtros.
- **Cadastros e movimentações:** contas simuladas, categorias, regras de provisão, lançamentos manuais, importação fictícia sem duplicação e estorno.
- **Extrato e relatórios:** consulta com filtros, ordenação e paginação; exportação em PDF e XLSX.
- **Acesso e configurações:** cadastro, verificação por e-mail, código de acesso, recuperação de senha, Google OAuth opcional, perfil, foto, preferências, segurança, ajuda, avaliação e exclusão de conta.
- **Interface:** visual compacto em todo o sistema, navegação compartilhada entre as telas financeiras e o perfil, opções de tema e tamanho de fonte.

## Rodar no XAMPP

Requisitos: PHP 8.1+, Apache, Composer e um projeto PostgreSQL no Supabase. Habilite no PHP as extensões `pdo_pgsql`, `pgsql`, `curl`, `openssl`, `gd`, `fileinfo`, `iconv` e `zip`. A extensão ZIP é usada na exportação XLSX.

1. Coloque o projeto em `C:\xampp\htdocs\fluxon_projeto-main`. No terminal, dentro da pasta, rode `composer install`.
2. **Banco novo:** no SQL Editor do Supabase, execute `database/schema.sql` e depois `database/migracao_fase3.sql`. **Banco PostgreSQL existente, antes da Fase 3:** execute somente `database/migracao_fase3.sql`. Se já aplicou essa migração e só quer atualizar o visual, pule este passo.
3. Copie `.env.example` para `.env`. Preencha os parâmetros do banco exibidos em **Connect → Session pooler** e configure o SMTP para os fluxos por e-mail. Mantenha `APP_BASE_URL` igual ao endereço real do `index.php`. Como alternativa, use `config.local.example.php` como modelo para `config.local.php`.
4. Inicie o Apache e abra [o Fluxon local](http://localhost/fluxon_projeto-main/index.php).

A autenticação é feita pelo Fluxon em PHP. O Supabase fornece o banco. Os dados do MySQL não são importados automaticamente; os scripts de `database/mysql_legado/` são apenas para a versão antiga.

## Testes e documentação

Execute `composer test` para verificar a sintaxe e as regras de validação. Para testar o fluxo completo, use dados fictícios e siga [Instalação e demonstração da Fase 3](INSTALAR_FASE3.md).

- [Requisitos da Fase 3](docs/FASE3_REQUISITOS.md)
- [Validação da Fase 3](docs/FASE3_VALIDACAO.md)
- [Integração do dashboard](INTEGRACAO_DASHBOARD.md)

As reservas são virtuais. O Fluxon não conecta bancos reais, movimenta dinheiro, paga tributos ou envia notificações agendadas. O disponível estimado não considera obrigações futuras que ainda não foram registradas.

Mantenha `.env`, `config.local.php`, logs e `vendor/` fora do Git. O Apache deve aplicar as regras dos arquivos `.htaccess`.

**Equipe:** Igor Silva, Jeferson Rios Pereira e Deric Fahr Diniz.
