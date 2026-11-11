# Fluxon: instalação da Fase 3

Esta versão amplia o projeto enviado. O banco continua PostgreSQL no Supabase. Os scripts MySQL são históricos.

## Atualizar a instalação existente

1. Faça uma cópia da pasta e um backup do banco antes de atualizar.
2. Copie os arquivos do pacote sobre a pasta do projeto no XAMPP. Preserve seu `.env` ou `config.local.php`. Eles não estão no ZIP. Preserve também a pasta `vendor/` instalada localmente.
3. No Supabase, abra SQL Editor e execute **somente `database/migracao_fase3.sql`**. A migração acrescenta tabelas e índices sem apagar usuários existentes. Pode ser reaplicada.
4. Confirme no PHP: `pdo_pgsql`, `pgsql`, `curl`, `openssl`, `gd`, `fileinfo`, `iconv` e `zip`. Reinicie o Apache se alterar o php.ini. ZIP é necessário para XLSX; o aplicativo informa se estiver ausente.
5. Execute `composer install` caso ainda não tenha dependências instaladas.
6. Entre pela URL habitual. O painel financeiro substitui a antiga tela de boas-vindas.

Para instalação nova, execute `database/schema.sql` e depois `database/migracao_fase3.sql`. Copie `.env.example` para `.env` ou use `config.local.example.php`. Preencha as credenciais somente em sua máquina.

## Demonstração em sequência

1. Em Cadastros > Contas simuladas, crie uma conta com saldo inicial de R$ 1.000,00.
2. Em Categorias, crie “Serviços”.
3. Em Regras de provisão, crie “Reserva de planejamento”, 20%, ativa.
4. No Extrato, abra “Importar demonstração bancária simulada”, selecione conta/categoria e importe.
5. O aplicativo cria entrada de R$ 2.500,00 e saída de R$ 420,00. O painel deve mostrar saldo registrado de **R$ 3.080,00**, reservas de **R$ 500,00** e disponível de **R$ 2.580,00**.
6. Repita a importação: zero novas movimentações, sem duplicação.
7. Abra os detalhes da entrada para ver a reserva vinculada. Cadastre uma movimentação manual e edite descrição/valor/data; confira os totais.
8. Aplique filtros e exporte PDF/XLSX. A exportação considera toda a consulta, até 10.000 registros, e não somente a página visível.
9. Tente excluir conta/categoria com movimentação: o vínculo bloqueia a operação. Desative a regra: o histórico permanece.
10. No cadastro da conta, altere a conexão simulada para “Revogada”. A importação deve ser bloqueada até reconectar.

## Interpretação dos valores

- Somente movimentações confirmadas, com data até hoje. Não há contas a pagar futuras.
- Saldo registrado = saldo inicial + entradas não estornadas - saídas não estornadas.
- Disponível estimado = saldo registrado - reservas ativas. Não é autorização de retirada.
- Reserva virtual não é saída. Se pagar uma obrigação reservada, registre a saída e **libere a reserva** nos detalhes da entrada de origem, para não comprometer o mesmo valor duas vezes. A liberação é integral e mantém o histórico.
- Não há conciliação automática de despesa/reserva nem liberação parcial. Obrigações futuras não cadastradas precisam ser consideradas fora dessa estimativa.
- Alterar entrada manual recalcula reservas com percentuais históricos. Regras novas valem para novas entradas. Entrada com reserva já liberada preserva o valor.
- Conta e tipo da movimentação são imutáveis na edição. Cadastro incorreto pode exigir exclusão e novo lançamento manual.
- Movimentação importada preserva data/valor; descrição/categoria são editáveis. Sua remoção é um estorno, que mantém histórico e inativa seu efeito financeiro. Reimportar não desfaz o estorno.
- Contas/categorias com movimentações não são excluídas. Regras são desativadas. Excluir movimentação manual remove também suas reservas após confirmação.
- Gráficos e totais da consulta respeitam os filtros. Os três saldos de posição atual usam todo o histórico da conta selecionada, independentemente de período/categoria. Essa distinção está indicada na tela.
- Gráficos mostram até 12 meses e até 10 categorias. Totais e relatórios continuam completos para os filtros.
- Não há integração bancária real, transferência, bloqueio de saldo ou apuração/pagamento de impostos.

## Conferência no seu ambiente

Execute `composer test` e faça o roteiro acima com duas contas de usuário diferentes para conferir o isolamento dos dados.

`tests/finance_integration.php` serve para **banco descartável**, nunca o banco principal. Instale os dois schemas e defina `FLUXON_TEST_DSN`, `FLUXON_TEST_USER` e `FLUXON_TEST_PASS`. Ele cria e remove usuários de teste.

Leia `docs/FASE3_REQUISITOS.md` e `docs/FASE3_VALIDACAO.md` para rastreabilidade e limites dos testes.
