# Revisão e organização para GitHub

Data: 20/10/2026. Base: ZIP `fluxon_projeto-main(3).zip` enviado pelo usuário. O original foi preservado; esta entrega é uma cópia organizada. Não houve publicação no GitHub nem alteração no banco ou no XAMPP do usuário.

## O que mudou

- Indentação e quebra de linhas em PHP, JavaScript, CSS e telas HTML/PHP, com espaços, UTF-8 e final de linha LF. Adicionado `.editorconfig` e configuração do formatador.
- Arquivos de `app/` separados em `controllers/`, `services/`, `repositories/` e `views/`. Os `require` da entrada, dos controladores e dos testes foram atualizados. Os nomes de rotas e o endereço `index.php?p=...` foram mantidos.
- Documento HTML compartilhado extraído para `app/views/layout.php`; o `index.php` continua como entrada da aplicação. As rotas de autenticação permanecem nele para limitar a mudança de comportamento nesta revisão.
- Estilos repetidos de largura removidos de Segurança, Exclusão, Suporte e Avaliação e centralizados em `assets/css/layout.css`. As páginas internas usam limite de 900px, com ou sem mensagem; o login continua compacto e Dashboard/Configurações mantêm o layout próprio.
- Estilos da avaliação e do suporte concentrados em seus arquivos CSS, preservando a grade de estrelas e a organização das perguntas.
- CSS ativo da senha extraído de `password.js` para `password.css`. A lógica do olho e da revelação temporária foi mantida. O script da tela de configurações foi movido para `assets/js/settings.js`.
- Arquivos existentes de acessibilidade voltaram a ser carregados no documento compartilhado. Foram retiradas deles as regras antigas que disputavam a largura das páginas com o layout.
- `database/schema.sql` agora também cria `user_reviews` e `account_deletion_tokens`, que antes só estavam nas migrações. Nenhuma tabela existente foi apagada. As migrações originais foram preservadas.
- Exemplos de URL passaram a usar `fluxon_projeto-main`, de acordo com a pasta informada. O arquivo local do usuário continua sendo a fonte da configuração real.
- README e guia de instalação atualizados; guias de incrementos anteriores movidos para `docs/historico/`, com aviso de que não são o procedimento atual de instalação.
- `.gitignore` ampliado e acesso HTTP direto a `docs/` e `database/` bloqueado por `.htaccess`, como já ocorria com `app/` e `tests/`.
- Testes de validação mantidos, adicionado teste de sintaxe sem banco e comando `composer test`. O `composer.lock` original foi mantido byte a byte; nenhuma versão de dependência foi atualizada.

## O que foi removido do pacote

Foram removidos **96 arquivos**, agrupados em seis itens abaixo. Isso não apaga cópias, histórico Git ou instalações que estejam no computador do usuário.

| Caminho original | Arquivos | Motivo |
| --- | ---: | --- |
| `config.local.php` | 1 | Configuração privada com credenciais; recriar localmente pelo exemplo. |
| `emails.log` | 1 | Log antigo de mensagens; não faz parte do código e pode conter dados de acesso. |
| `vendor/` | 84 | Dependências de terceiros geradas pelo Composer; reinstalar com composer install. |
| `prototipo_html/` | 8 | Oito páginas estáticas de protótipo, sem referência nas rotas PHP e sem autenticação real. |
| `assets/js/password-visibility.js` | 1 | Implementação antiga não carregada; a versão ativa é password.js. |
| `assets/css/password-visibility.css` | 1 | Estilo da implementação antiga não carregada; substituído pelo estilo extraído de password.js. |

A lista individual completa, incluindo os 84 arquivos de `vendor/` e as oito páginas do protótipo, está em [remocoes.json](remocoes.json). O nome do arquivo privado é registrado, mas seu conteúdo não foi copiado para nenhum relatório.

`vendor/` não é código descartado: o Composer o recria a partir de `composer.lock`. O protótipo removido era uma demonstração estática separada das telas PHP em uso. Os dois arquivos antigos de visualização de senha não eram referenciados pela aplicação ativa.

## O que foi movido, sem exclusão de funcionalidade

| Origem | Destino |
| --- | --- |
| `app/profile.php`, `security.php`, `reviews.php`, `support.php`, `delete_account.php`, `accessibility.php` | `app/controllers/` |
| `app/*Service.php` | `app/services/` |
| `app/*Repository.php` | `app/repositories/` |
| `app/*_view.php` | `app/views/` |
| Guias `FASE2_PASSO1.md` a `FASE2_PASSO6.md`, `CONFIGURACOES_LEIA_PRIMEIRO.md`, `CONTATO_NAVEGADOR.md`, `CORRECAO_LAYOUT.md`, `SENHA_VISIBILIDADE.md` | `docs/historico/` |

As regras de perfil, foto, preferências, avaliação, troca de senha e exclusão foram preservadas. A comparação estrutural do código dos seis serviços/repositórios confirmou que a formatação não alterou suas instruções. Isso não substitui a validação integrada no ambiente real.

## Verificação executada

Consulte [VALIDACAO.txt](VALIDACAO.txt) para os resultados. Foram aprovados: sintaxe PHP dos arquivos entregues, sintaxe dos quatro scripts JavaScript, quatro testes existentes de validação, caminhos literais de inclusão/recursos e renderização de oito telas com dados fictícios.

A execução usou PHP 8.5.10 em WebAssembly; não foi executada no PHP 8.2 do XAMPP do usuário. O projeto mantém a exigência de PHP 8.1 ou superior. Não foi possível validar aqui MySQL/MariaDB, Apache, SMTP, Google OAuth, aparência no navegador ou exclusão real. Execute o roteiro de aceitação do guia antes de substituir a instalação usada nos testes.

## Como usar sem perder a configuração atual

1. Preserve o `config.local.php` da instalação atual e faça backup do banco.
2. Teste a pasta revisada separadamente. Instale as dependências com `composer install`.
3. Recoloque seu arquivo local e ajuste `base` se a pasta de teste tiver outro nome. A URI de retorno Google deve acompanhar a URL.
4. Se o banco já tinha todas as funcionalidades, não reimporte `schema.sql` e não repita migrações.
5. Execute `composer test` e o roteiro manual de `GUIA_CONFIGURACAO.md`.
6. Publique o conteúdo da pasta revisada no GitHub, mantendo os arquivos privados e `vendor/` fora do commit.

Esta revisão trata de organização, referências, limpeza e correções de integração identificadas. Não é uma auditoria completa de segurança nem comprovação de funcionamento em produção.
