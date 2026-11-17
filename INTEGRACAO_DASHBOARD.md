# Integração do dashboard

Base: segundo arquivo (RAR). Referência visual: primeiro arquivo (ZIP).

O ZIP apresenta menu lateral FLUXON, cabeçalho, identificação do usuário e cartões, mas seu dashboard financeiro contém apenas avisos de funcionalidades futuras. O RAR contém os módulos financeiros da Fase 3. A integração adapta a estrutura visual do ZIP aos dados e funcionalidades do RAR.

## Alterações
- app/views/finance_view.php: menu lateral, cabeçalho, avatar com inicial, boas-vindas e links funcionais. Mantidos filtros, indicadores, gráficos, cadastros, reservas, movimentações, exportações e confirmação de exclusão.
- assets/css/dashboard.css: visual inspirado no primeiro projeto, com adaptação para celular, temas e tamanho de fonte.
- index.php: carrega o novo CSS somente nas quatro rotas financeiras.

## Instalação
Faça backup da pasta atual. Copie o conteúdo deste pacote para C:/xampp/htdocs/fluxon_projeto-main. Preserve seu .env e config.local.php: eles não estão no pacote. A pasta vendor do segundo projeto foi mantida. Não é necessário repetir a migração já executada: esta integração não altera o banco.

No seu .env, mantenha APP_BASE_URL=http://localhost/fluxon_projeto-main/index.php. Abra o index.php, entre na conta e teste Dashboard, Fluxo de Caixa / Extrato, Regras de Reserva, Movimentações, Cadastros e Configurações. Confira também temas e fonte em Acessibilidade.

## Validação
Renderização PHP das rotas e testes automatizados executados em ambiente isolado; conexão real, envio de e-mail e aparência no navegador do XAMPP dependem de conferência local. Nenhuma credencial ou log de e-mail acompanha o pacote.

## Configurações
A tela de perfil usa o mesmo menu e cabeçalho das telas financeiras, com cartões e campos que se adaptam à largura da janela. Os formulários e as regras de gravação foram preservados.

## Painel (layout do protótipo)

O painel (`?p=painel`) segue o protótipo: saldo disponível em destaque, cartão de reservas ativas, fluxo de caixa das últimas 5 semanas e tabela de próximos lançamentos. Ele usa só os dados confirmados do módulo financeiro, sem tabela própria.

- `app/views/painel_view.php`: tela do painel. As demais telas continuam em `finance_view.php`.
- `app/services/PainelService.php`: semanas do gráfico, escala do eixo e percentuais (sem banco, com teste em `tests/painel_service.php`).
- `assets/css/painel.css`: estilos, com as variáveis de tema e de tamanho de fonte do aplicativo.
- Saldo disponível = saldo registrado − reservas ativas. O percentual do cartão de reservas é a parcela do saldo registrado reservada.
- Não há contas a pagar nem recebimentos futuros no módulo, então o gráfico mostra o que já aconteceu e a tabela lista reservas com vencimento até 7 dias (ou vencidas) e as últimas movimentações.
