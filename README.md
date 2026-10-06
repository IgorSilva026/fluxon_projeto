# Fluxon Analytics — Fase 1: Controle de Acesso

## Estrutura
```
fluxon_projeto/
├── index.php            # sistema (rotas, login, cadastro, 2FA, recuperação, Google)
├── config.php           # banco, URL base, Google OAuth, modo dev
├── .htaccess            # bloqueia acesso direto a .log e .sql
├── assets/css/style.css # estilos compartilhados (PHP e protótipo)
├── database/schema.sql  # tabelas users e tokens
└── prototipo_html/      # telas estáticas (abrir index.html)
```

## Como rodar (XAMPP)
1. Copie a pasta para `C:\xampp\htdocs\fluxon_projeto`.
2. Inicie Apache e MySQL; importe `database/schema.sql` no phpMyAdmin.
3. Ajuste `'base'` no `config.php` para `http://localhost/fluxon_projeto/index.php`.
4. Acesse http://localhost/fluxon_projeto/index.php

Google: preencher `id`/`secret` no `config.php` e cadastrar `<base>?p=gcb` como URI de redirecionamento.
Modo dev (`'dev' => true`): e-mails aparecem na tela e em `emails.log`.
