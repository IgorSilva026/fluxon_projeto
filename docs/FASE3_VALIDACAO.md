# Evidências de validação da Fase 3

Data: 10/11/2026. Base: fluxon_projeto-main(4).zip. Sem acesso ao banco, às credenciais ou ao XAMPP do usuário.

## Verificações executadas

- Sintaxe dos arquivos PHP aprovada por PHP.wasm CLI. Verificações de sintaxe JavaScript com Node.js.
- Testes existentes de perfil, senha, avaliação e exclusão aprovados. Testes novos de validação financeira aprovados: centavos exatos, percentuais, datas impossíveis, IDs inválidos, limites e ordenação permitida.
- Schema e migração executados em PostgreSQL isolado via PGlite. A integração usa PHP 8.2 WASM com um adaptador de teste PDO para transportar as consultas ao PostgreSQL. As consultas e o repositório testados são os do aplicativo; o adaptador é exclusivo do laboratório e não integra o ZIP.
- Integração: saldo inicial/entrada/reserva, edição com percentual histórico, limite de regras, bloqueio de exclusão com dependência, isolamento de usuários, liberação de reserva, desativação preservando histórico, exclusão em cascata, importação, idempotência, estorno, filtros, gráficos, paginação e revogação da conexão simulada aprovados.
- FK composta recusou associação entre usuários diferentes. Exclusão do usuário removeu seus dados financeiros em cascata.
- Controlador e telas PHP renderizados com consultas ao banco isolado: painel, extrato, contas, regras, nova movimentação, detalhes com reserva, consulta vazia e filtro inválido. Sem warnings PHP. Estrutura HTML e valores esperados conferidos.
- POST sem CSRF e exclusão sem confirmação não alteraram os dados nos testes do controlador.
- PDF e XLSX com 65 registros e com consulta vazia gerados e reabertos por pypdf/openpyxl. Conferidos título, acentos, quantidade de linhas, número monetário e texto semelhante a fórmula armazenado como texto (sem executar fórmula na planilha).
- Relatórios de 10.000 registros gerados: PDF em aproximadamente 0,57 s (715 páginas) e XLSX em aproximadamente 0,36 s, no ambiente isolado. Último registro conferido. Essas medidas abrangem geração em memória, não a requisição no XAMPP/Supabase nem download.
- Em 10.000 movimentações, 20 consultas sequenciais no PGlite: p95 aproximado de 9,9 ms para a página do extrato e 10,8 ms para a agregação mensal. Não são 20 usuários simultâneos.

## Limites importantes

Não executado: driver nativo pdo_pgsql, conexão Supabase, Apache/XAMPP, e-mails/Google, inspeção interativa das telas em Chrome/Opera ou carga com 20 usuários simultâneos. A tentativa de abrir Chromium no ambiente de teste foi bloqueada por restrições de execução. A renderização PHP não demonstra ausência de problemas visuais em navegador.

A meta RNF02 de p95 até 3 segundos com 20 usuários simultâneos permanece a homologar no destino. Os resultados isolados não permitem declará-la cumprida. Também não constituem validação da utilidade financeira da hipótese com usuários reais.

## Roteiro manual no ambiente de destino

1. Instale a migração conforme INSTALAR_FASE3.md.
2. Faça o fluxo completo de criar conta/categoria/regra, importar, consultar, filtrar, editar, liberar reserva e excluir/estornar com confirmação.
3. Confira os três saldos do exemplo e se o painel muda após cada alteração.
4. Tente usar um ID de outra conta de usuário: detalhes, edição e remoção devem ser recusados.
5. Crie mais de 20 movimentações e confira a segunda página, com filtros preservados.
6. Exporte PDF/XLSX e compare os totais com a consulta completa, não somente com a página atual. Abra em leitor PDF e Excel/LibreOffice.
7. Confira tema claro/escuro, tamanho de fonte, navegação por teclado e visualização em celular. Tabelas devem poder rolar horizontalmente sem perder dados.
8. Teste os vínculos após exclusões, inclusive com a exclusão de uma conta de aplicativo criada apenas para esse teste.
9. Em homologação descartável, faça teste de carga com 20 sessões e 10.000 registros por conta e registre p95 de painel/extrato.

Os arquivos docs/REVISAO.md e docs/VALIDACAO.txt são registros históricos da versão anterior, não evidências destes novos módulos.
