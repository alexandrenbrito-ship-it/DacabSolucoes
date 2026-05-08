# 🛒 FlyerSaaS - Sistema de Encartes Digitais

Sistema SaaS completo para criação de encartes digitais para supermercados.

## ✅ Instalação Concluída

Os arquivos principais foram criados com sucesso! O sistema agora:

1. **Verifica automaticamente** se está instalado
2. **Redireciona para install.php** se faltar tabelas ou configuração
3. **Cria todas as tabelas** automaticamente durante a instalação
4. **Gera arquivo .env.php** e **installed.lock** para controle

## 📁 Estrutura de Arquivos Criados

```
/workspace/
├── config.php              # Configuração principal com verificação de instalação
├── install.php             # Instalador automático (3 passos)
├── index.php               # Redireciona para dashboard/login
├── login.php               # Página de login
├── logout.php              # Logout
├── .htaccess               # Segurança Apache
├── installed.lock          # Criado após instalação (bloqueia reinstall)
├── .env.php                # Criado na instalação (dados do banco)
│
├── includes/
│   ├── Database.php        # Conexão PDO com auto-criação de DB
│   ├── Auth.php            # Autenticação e sessões
│   └── functions.php       # Funções auxiliares (isSystemInstalled, etc)
│
├── admin/
│   ├── index.php           # Dashboard admin
│   ├── header.php          # Cabeçalho comum admin
│   ├── footer.php          # Rodapé comum admin
│   ├── db-update.php       # Atualização de banco de dados
│   ├── users.php           # Gerenciar usuários
│   ├── plans.php           # Gerenciar planos
│   └── config.php          # Configurações do sistema
│
└── user/
    ├── dashboard.php       # Dashboard do usuário
    ├── gallery.php         # Galeria de imagens
    ├── flyers.php          # Meus encartes
    ├── upload-image.php    # Upload de imagens
    └── profile.php         # Perfil do usuário
```

## 🚀 Como Instalar (Passo a Passo)

### 1. No Painel da Hostinger (hPanel)

1. Acesse **Bancos de Dados MySQL**
2. **Crie um novo banco de dados** (ex: `u624766619_encartes`)
3. **Crie um usuário MySQL** com senha forte
4. **Associe o usuário ao banco** com TODAS as permissões

### 2. Acesse o Instalador

1. Abra seu navegador em: `https://seudominio.com/install.php`
2. O sistema verificará automaticamente se precisa instalar

### 3. Preencha os Dados

**Passo 1 - Banco de Dados:**
- Host: `localhost` (ou endereço fornecido pela Hostinger)
- Nome do banco: `u624766619_encartes` (o que você criou)
- Usuário: `u624766619_admin` (o que você criou)
- Senha: a senha do usuário MySQL

**Passo 2 - URL:**
- O sistema detecta automaticamente
- Ajuste se necessário (ex: `https://meusite.com`)

**Passo 3 - Administrador:**
- Nome completo
- E-mail
- Senha (mínimo 6 caracteres)

### 4. Conclusão

O sistema irá:
- ✅ Criar todas as 8 tabelas automaticamente
- ✅ Criar arquivo `.env.php` com configurações
- ✅ Criar arquivo `installed.lock` 
- ✅ Cadastrar primeiro administrador
- ✅ Inserir planos padrão (Básico, Profissional, Enterprise)

## 🔧 Resolução de Problemas

### Erro: "Table doesn't exist" após instalação

**Causa:** Arquivo `installed.lock` não foi criado ou permissões incorretas.

**Solução:**
```bash
# SSH ou Gerenciador de Arquivos da Hostinger
cd public_html
chmod 755 .
touch installed.lock
chmod 444 installed.lock
```

### Erro: "Não foi possível criar .env.php"

**Causa:** Permissão de escrita na pasta raiz.

**Solução:**
1. No hPanel → Gerenciador de Arquivos
2. Clique com botão direito na pasta `public_html`
3. **Permissões** → Marque **Write** para Owner
4. Tente instalar novamente
5. Após instalar, volte permissão para **Read + Execute**

### Sistema continua redirecionando para install.php

**Verifique:**
1. Arquivo `installed.lock` existe na raiz?
2. Arquivo `.env.php` existe na raiz?
3. Todas as 8 tabelas existem no banco?

Tabelas necessárias:
- `users`
- `plans`
- `user_subscriptions`
- `gallery_images`
- `flyers`
- `flyer_items`
- `system_config`
- `migrations`

## 🔐 Segurança Implementada

- ✅ Prepared Statements (SQL Injection)
- ✅ password_hash() (senhas)
- ✅ CSRF Tokens em formulários
- ✅ XSS Protection (sanitize)
- ✅ .htaccess bloqueando execução PHP em uploads
- ✅ Session timeout (30 min)

## 📊 Próximos Passos

Após instalação, acesse:
1. **Admin:** `https://seudominio.com/admin/`
2. **Usuário:** `https://seudominio.com/user/dashboard.php`

No painel admin:
- Atribua planos aos usuários
- Configure assinaturas manualmente
- Gerencie o sistema

## 💡 Dicas Hostinger

- Use **PHP 7.4** ou superior
- Banco deve ser criado **antes** da instalação
- Permissões de pasta: **755** (pastas), **644** (arquivos)
- `.env.php` e `installed.lock`: **444** (somente leitura)

---

**FlyerSaaS v1.0** - Desenvolvido para Hostinger
