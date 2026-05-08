# 🎨 EncartePro - Sistema SaaS de Encartes Digitais

Sistema completo em PHP puro para criação e gestão de encartes digitais, com integração ao Mercado Pago para pagamentos recorrentes.

## 📋 Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior / MariaDB 10.3+
- Composer (opcional, apenas para mPDF)
- Servidor web (Apache/Nginx)

## 🚀 Instalação

### 1. Upload dos Arquivos

Faça upload de todos os arquivos para seu servidor web, preferencialmente em uma subpasta chamada `saas-encartes`.

### 2. Permissões de Diretório

Certifique-se de que as seguintes pastas tenham permissão de escrita:

```bash
chmod -R 755 /caminho/para/saas-encartes/
chmod -R 777 /caminho/para/saas-encartes/uploads/
chmod -R 777 /caminho/para/saas-encartes/tmp/
```

### 3. Banco de Dados

Crie um banco de dados MySQL vazio e um usuário com privilégios totais sobre ele:

```sql
CREATE DATABASE encartepro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'encarte_user'@'localhost' IDENTIFIED BY 'senha_forte';
GRANT ALL PRIVILEGES ON encartepro.* TO 'encarte_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Assistente de Instalação

Acesse a página de instalação através do navegador:

```
https://seudominio.com/saas-encartes/install/
```

Preencha as informações:

**Etapa 1 - Configurações:**
- Host do banco de dados (geralmente `localhost`)
- Nome do banco de dados criado
- Usuário e senha do banco
- URL do site (ex: `https://seudominio.com/saas-encartes`)
- Nome do sistema (ex: `EncartePro`)
- Email remetente para notificações
- Credenciais do Mercado Pago (opcionais nesta etapa)

**Etapa 2 - Admin:**
- Nome do administrador
- Email do administrador
- Senha do administrador

O instalador irá:
- Criar todas as tabelas necessárias
- Inserir 3 planos padrão (Starter, Pro, Enterprise)
- Criar o arquivo `.env` na raiz do projeto
- Criar a conta de administrador

### 5. Pós-Instalação

Após a instalação, exclua ou renomeie a pasta `install` por segurança:

```bash
mv install install_backup
```

## 💳 Configuração do Mercado Pago

### Obter Credenciais

1. Acesse [Mercado Pago Developers](https://www.mercadopago.com.br/developers/)
2. Crie uma aplicação em "Minhas Integrações"
3. Obtenha o **Access Token** e a **Public Key**

### Configurar no Sistema

Edite o arquivo `.env` na raiz do projeto:

```env
MP_ACCESS_TOKEN=APP_USR-xxxxxxxxxxxxxx
MP_PUBLIC_KEY=APP_USR-xxxxxxxxxxxxxx
```

### Configurar Webhook

No painel do Mercado Pago, configure a URL de notificação:

```
https://seudominio.com/saas-encartes/webhooks/mercadopago.php
```

### URLs de Retorno

Configure também as URLs de retorno no Mercado Pago:
- **Success:** `https://seudominio.com/saas-encartes/dashboard/?payment=success`
- **Failure:** `https://seudominio.com/saas-encartes/dashboard/?payment=failure`
- **Pending:** `https://seudominio.com/saas-encartes/dashboard/?payment=pending`

## 📧 Configuração de SMTP

Para envio de emails transacionais, edite o arquivo `.env`:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=seuemail@gmail.com
MAIL_PASS=sua_senha_de_app
MAIL_FROM=noreply@seudominio.com
MAIL_FROM_NAME=EncartePro
```

### Gmail (com App Password)

1. Ative a verificação em duas etapas na sua conta Google
2. Gere uma "Senha de App" em: https://myaccount.google.com/apppasswords
3. Use essa senha no `MAIL_PASS`

### Outros provedores:

| Provedor | Host | Porta |
|----------|------|-------|
| Gmail | smtp.gmail.com | 587 |
| Outlook | smtp-mail.outlook.com | 587 |
| SendGrid | smtp.sendgrid.net | 587 |
| Mailgun | smtp.mailgun.org | 587 |

## 📦 Instalação do mPDF (Opcional)

Para geração de PDFs dos encartes, instale o mPDF via Composer:

```bash
cd /caminho/para/saas-encartes
composer require mpdf/mpdf
```

Se não usar Composer, o sistema tentará usar métodos alternativos ou exibirá mensagem de erro ao tentar baixar PDFs.

## 🔧 Estrutura de Arquivos

```
/saas-encartes/
├── index.php              # Landing page principal
├── .env                   # Configurações (gerado na instalação)
├── /install/              # Assistente de instalação
├── /auth/                 # Autenticação
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── forgot-password.php
│   └── reset-password.php
├── /admin/                # Painel administrativo
│   ├── index.php
│   ├── users.php
│   ├── plans.php
│   └── subscriptions.php
├── /dashboard/            # Painel do usuário
│   ├── index.php
│   ├── editor.php
│   ├── my-encartes.php
│   └── download.php
├── /webhooks/             # Webhooks externos
│   └── mercadopago.php
├── /emails/               # Templates de email
│   ├── welcome.php
│   ├── reset-password.php
│   └── subscription-confirmed.php
├── /includes/             # Bibliotecas e utilitários
│   ├── config.php
│   ├── mail.php
│   ├── mercadopago.php
│   ├── pdf.php
│   ├── header.php
│   └── footer.php
└── /uploads/              # Arquivos gerados
    └── /pdfs/             # PDFs dos encartes
```

## 👥 Planos Padrão

O sistema já inclui 3 planos pré-configurados:

| Plano | Preço | Encartes | Recursos |
|-------|-------|----------|----------|
| Starter | R$ 29,90/mês | 10 | Templates básicos, suporte email |
| Pro | R$ 59,90/mês | 50 | Todos templates, suporte prioritário |
| Enterprise | R$ 149,90/mês | Ilimitado | API, white label, suporte 24/7 |

Novos usuários recebem automaticamente 7 dias de trial no plano Starter.

## 🔐 Acessos

### Administrador

Acesse o painel admin em:
```
https://seudominio.com/saas-encartes/admin/
```

Use as credenciais criadas durante a instalação.

### Usuário Comum

Os usuários acessam em:
```
https://seudominio.com/saas-encartes/dashboard/
```

## 🛠️ Personalização

### Cores do Sistema

As cores principais estão definidas no CSS do header.php:

```css
--primary-color: #e8401c;    /* Laranja-vermelho */
--secondary-color: #f5a623;  /* Âmbar */
```

### Templates de Encartes

Os templates estão definidos em `/includes/pdf.php`. Para adicionar novos:

1. Crie a função `renderNomeTemplate($data)` 
2. Adicione ao array `$templates` na função `renderEncarteTemplate()`

### Emails

Os templates de email estão em `/emails/`. Edite os arquivos HTML conforme necessário.

## 🐛 Troubleshooting

### Erro de conexão com banco de dados

Verifique se as credenciais no arquivo `.env` estão corretas.

### Emails não são enviados

- Verifique as configurações SMTP no `.env`
- Teste com `mail()` nativo primeiro
- Verifique logs de erro do PHP

### Webhook do Mercado Pago não funciona

- Verifique se a URL está acessível externamente
- Confira o Access Token no `.env`
- Veja o log em `/webhooks/mp_webhook.log`

### PDF não é gerado

- Instale o mPDF via Composer
- Verifique permissões da pasta `/tmp/` e `/uploads/pdfs/`

## 📝 Logs

Os seguintes arquivos de log são gerados:

- `/webhooks/mp_webhook.log` - Webhooks do Mercado Pago
- Logs de erro do PHP no diretório configurado no `php.ini`

## 🔒 Segurança

- Todas as queries usam PDO com prepared statements
- Senhas são hasheadas com `password_hash()`
- Tokens CSRF protegem formulários
- Sessions PHP nativas com regeneração de ID

## 📄 Licença

Este projeto é fornecido "como está" para uso comercial.

## 🤝 Suporte

Para dúvidas ou problemas:
- Email: suporte@encartepro.com.br
- Documentação: Consulte este README

---

Desenvolvido com ❤️ para impulsionar pequenos negócios.
