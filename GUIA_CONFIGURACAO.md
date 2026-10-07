# Fluxon — aplicação das correções no XAMPP

## Estado desta entrega
Código preparado sobre o commit 2dc9339. Ainda requer instalação das dependências, migração do banco e testes locais. Não houve envio ao GitHub nem teste real de Google/SMTP. O protótipo HTML permanece apenas demonstrativo; use index.php para avaliar autenticação.

## 1. Preparar
1. Faça cópia da pasta atual e exporte o banco fluxon pelo phpMyAdmin.
2. Extraia os arquivos do pacote em C:\xampp\htdocs\fluxon_projeto. Preserve seus dados e compare eventuais alterações locais antes de substituir arquivos.
3. Use PHP 8.1 ou superior, com pdo_mysql, curl e openssl habilitados no php.ini do XAMPP.
4. Instale o Composer usando o PHP do XAMPP. No terminal do VS Code aberto na pasta do projeto, execute `composer install`. Isso gera vendor e composer.lock; inclua composer.lock no próximo commit após instalação bem-sucedida.
5. Para banco existente, importe database/migracao_001.sql UMA VEZ. Para banco novo, importe apenas database/schema.sql. Não execute ambos. A migração invalida códigos antigos, preservando usuários.
6. Copie config.local.example.php para config.local.php. Preencha os valores localmente, sem enviar esse arquivo ao GitHub ou ao chat.

## 2. E-mail real
Em config.local.php, configure smtp.host, port, user, pass, secure e from conforme seu provedor. Para porta 587, normalmente use tls; para 465, ssl. Use o remetente autorizado pelo serviço. Se o provedor exigir senha de aplicativo, use-a no lugar da senha comum. Não desative a validação de certificados.

O sistema usa PHPMailer por SMTP. Códigos e links não aparecem na tela nem são gravados em emails.log. Falhas no envio do código impedem o acesso e mostram uma mensagem; falhas de recuperação são registradas sem expor endereço ou token, mantendo uma resposta genérica para evitar revelar contas cadastradas. Verifique a caixa de spam.

## 3. Google
1. Abra Google Cloud Console e selecione/crie um projeto.
2. Configure a tela de consentimento/Google Auth Platform. Se estiver em modo de teste, inclua os e-mails dos participantes como usuários de teste.
3. Crie um cliente OAuth do tipo aplicação Web.
4. Cadastre exatamente a URI de retorno: http://localhost/fluxon_projeto/index.php?p=gcb
5. Copie Client ID e Client Secret para google.id e google.secret em config.local.php.
6. Mantenha base igual a http://localhost/fluxon_projeto/index.php. Se mudar pasta ou domínio, atualize base e a URI cadastrada no Google.
7. Teste com uma conta Google. Após o retorno, conclua o código enviado por e-mail para acessar o painel.

Referência oficial: https://developers.google.com/identity/openid-connect/openid-connect
SMTP: https://github.com/PHPMailer/PHPMailer

## 4. Testes de aceitação — registrar resultado real
- [ ] Splash abre e encaminha à tela inicial.
- [ ] Cadastro rejeita senha fraca e confirmação divergente.
- [ ] Cadastro válido envia código real; painel permanece bloqueado até confirmar.
- [ ] E-mail é marcado como verificado após confirmação correta.
- [ ] Login tradicional exige senha e código em uma nova sessão.
- [ ] Código incorreto, expirado, reutilizado ou com cinco erros é recusado.
- [ ] Login Google completa o retorno e exige o código de e-mail.
- [ ] Recuperação envia link real; link expira após uma hora.
- [ ] Nova senha fraca é recusada; senha anterior deixa de funcionar após troca.
- [ ] Link já usado é recusado e sessões anteriores perdem acesso ao painel.
- [ ] Abrir ?p=painel sem autenticação redireciona ao login.
- [ ] Falha no SMTP não concede acesso nem mostra códigos.
- [ ] config.local.php, .git e arquivos SQL/log não são acessíveis pelo Apache.

Faça os testes com contas próprias/de teste. Guarde capturas sem senhas, tokens ou segredos. Anexe o documento teórico corrigido à entrega.

## 5. Git e publicação
Remova emails.log do rastreamento no repositório original (`git rm --cached --ignore-unmatch emails.log`) e apague a cópia local antiga após não precisar mais dela. A remoção atual não apaga o histórico do Git. A migração invalida tokens antigos no banco; se houve exposição de outras credenciais reais, substitua-as no provedor.

Revise `git status` e `git diff`, registre as alterações e não inclua config.local.php nem vendor.

HTTP é apenas para localhost. Publicação em servidor exige HTTPS e configuração Apache equivalente; .htaccess não é aplicado por Nginx ou pelo servidor embutido do PHP. Esta entrega não é uma auditoria completa de produção.
