# Fase 3: requisitos e rastreabilidade

Base: Engenharia_Modelagem_Fluxon_ABNT.docx, versão 2 consultada em 10/11/2026, e orientações do professor. Este é um adendo técnico, não uma reescrita do documento acadêmico.

## Requisitos existentes

| Requisito | Implementação nesta entrega |
|---|---|
| RF01 cadastro e acesso | Base anterior preservada em index.php e controladores de conta |
| RF02 conexão simulada, revogação e última sincronização | finance_accounts e FinanceRepository::importDemo/saveItem |
| RF03 importar e provisionar sem duplicidade | finance_entries.external_ref, finance_reserves, insertEntry e transações |
| RF04 três saldos por conta | FinanceRepository::balances, finance_view.php |
| RF05 alertas | Avisos derivados de saldo negativo, conexão revogada e vencimento de reservas. Leitura/arquivamento persistente e notificações agendadas continuam pendentes |
| RF06 regras por finalidade, percentual e vencimento | finance_rules, edição/desativação e preservação do histórico |
| RF07 extrato filtrado e vínculos | filter, rows, entry e reserves |
| RF08 estorno e reversão do efeito financeiro | removeEntry e exclusão dos valores estornados das agregações |

O cadastro manual complementa a demonstração acadêmica. Não substitui nem comprova integração bancária real.

## Complementação para a Fase 3

| ID | Requisito | Código |
|---|---|---|
| F3-01 | Contas/categorias com inclusão, consulta, detalhe, edição, confirmação de exclusão e busca | finance.php, finance_view.php, FinanceRepository |
| F3-02 | Ciclo completo de movimentações manuais confirmadas | saveEntry, entry, removeEntry |
| F3-03 | Busca por texto e filtros de datas, conta, categoria, tipo e situação | FinanceInput::filters, FinanceRepository::filter |
| F3-04 | Ordenação por data/valor e paginação de 20 itens | rows e controlador |
| F3-05 | Gráficos de entradas/saídas por mês e saídas por categoria | chart e financeChart |
| F3-06 | PDF/XLSX da consulta com título, geração, filtros e consolidados | FinanceReport e controlador |
| F3-07 | Liberar reserva integralmente com confirmação e histórico | releaseReserve |
| F3-08 | Isolar usuário e validar associações no banco | user_id, FKs compostas e operações transacionais |
| F3-09 | Atualizar consultas e gráficos após operações/filtros | Nova leitura do banco em cada requisição e redirecionamento após POST |
| F3-10 | Limitar exportação a 10.000 registros e orientar refinamento | FinanceReport::LIMIT |

## Modelo lógico acrescentado

- Usuario 1:N ContaSimulada (finance_accounts).
- Usuario 1:N Categoria (finance_categories).
- Usuario 1:N RegraProvisao (finance_rules).
- ContaSimulada 1:N Movimentacao (finance_entries).
- Categoria 1:N Movimentacao.
- Movimentacao 1:N ReservaFinanceira (finance_reserves).
- RegraProvisao 1:N ReservaFinanceira.

Reserva guarda finalidade, percentual, vencimento e valor históricos, com liberação opcional. Categoria complementa o modelo original. FKs compostas por user_id e ID do pai impedem vínculos entre usuários diferentes. O SQL físico está em database/migracao_fase3.sql.

## Regras de integridade

- Nomes únicos por usuário, sem diferenciar maiúsculas/minúsculas.
- Propriedade vem da sessão, nunca do formulário.
- Escritas bloqueiam a linha do usuário e usam transações, serializando alterações e validação da soma das regras.
- Regras ativas não ultrapassam 100% em conjunto.
- Valores usam centavos inteiros. Percentuais usam centésimos de porcentagem. Reservas truncam frações de centavo para não ultrapassar a entrada.
- Novas regras não recalculam entradas antigas. Alterar o valor de entrada manual mantém percentuais históricos. Reserva liberada impede alterar esse valor.
- Conta/categoria com movimentações: exclusão bloqueada. Regra: desativação, mantendo o histórico.
- Excluir movimentação manual remove reservas por cascata. Importação sofre estorno, preservando o registro e suas referências externas.
- Excluir usuário remove dados financeiros por cascata.
- Relatórios e tela usam os mesmos filtros, com snapshot de leitura consistente.
- Dados importados preservam conta, tipo, referência externa, data e valor. Permite-se editar descrição/categoria.
- O simulador emite dois eventos conhecidos por conta. Demonstra idempotência, não um conector completo.

## Interface e limites

Gráficos são barras HTML alimentadas por agregações do PostgreSQL, com valores textuais, filtros e atualização ao consultar novamente. Não dependem de CDN. As telas usam tema e escala de fonte existentes.

Sem banco real, cobrança, pagamento de impostos, agenda de notificações, arquivamento de alertas ou contas a pagar futuras. O MVP não concilia automaticamente reserva e despesa. Para reproduzir integralmente a hipótese ampliada com compromissos futuros descobertos, é necessário adicionar o cadastro dessas obrigações e sua cobertura por reservas.

O RNF02 (20 usuários simultâneos, p95 até 3 segundos) requer homologação no ambiente de destino. Testes isolados não comprovam a meta. Esta entrega não publica commits nem pull requests.
