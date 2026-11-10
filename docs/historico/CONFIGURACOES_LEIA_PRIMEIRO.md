# Tela de Configurações — Fluxon

## Instalação no projeto existente

1. Faça backup da pasta do projeto e do banco `fluxon`.
2. Copie o conteúdo desta pasta para `C:\xampp\htdocs\fluxon_projeto`, substituindo os arquivos correspondentes. Mantenha seu `config.local.php` e sua pasta `vendor` existentes.
3. No phpMyAdmin, selecione `fluxon`. Se ainda não existir `user_profiles`, execute primeiro `database/migracao_002_perfil.sql`.
4. Execute `database/migracao_003_configuracoes.sql` uma única vez. Não execute `schema.sql` sobre o banco existente: ele é destinado a instalações novas.
5. Inicie Apache e MySQL, entre na conta e acesse `http://localhost/fluxon_projeto/index.php?p=perfil`.

Para instalação nova, use `database/schema.sql`, configure `config.local.php` a partir do exemplo e execute `composer install` para instalar o PHPMailer. Ajuste a URL base ao nome real da pasta e a porta do MySQL ao seu ambiente (por exemplo, 3307).

## Implementado

- Layout inspirado na imagem: menu lateral, cabeçalho, perfil, configurações fiscais, notificações e cartão demonstrativo de plano.
- Nome, CNPJ, tipo de empresa, alíquota e preferências persistidos por usuário autenticado.
- E-mail somente para leitura; foto, telefone, cidade, ocupação, canais e frequência preservados em opções expansíveis.
- Ambos os botões de salvar gravam todas as preferências do formulário. Alterar interruptores exige salvar.
- Validação no servidor, consultas parametrizadas e proteção CSRF existentes preservadas.
- Layout responsivo para celular.

## Limites da implementação

Dashboard usa o painel existente. Fluxo de caixa, reserva fiscal e transações estão identificados como futuras áreas. A preferência de reserva não movimenta dinheiro nem calcula lançamentos: o módulo financeiro ainda não existe. Alertas financeiros não são enviados; somente suas preferências são armazenadas. O plano e seu preço são demonstrativos; o botão informa que não há integração de cobrança. CNPJ possui validação de formato, sem consulta cadastral ou verificação de dígitos.

## Validação

Revisão da integração entre formulário, validação, repositório e migração. O ambiente de geração não possui PHP/MySQL; execução e teste de persistência precisam ser feitos no XAMPP. Execute também `php tests/profile_validation.php` após instalar PHP.
