# 📋 Instruções de Instalação - FlyerSaaS

## ✅ Problema Resolvido

O sistema foi atualizado para **NÃO redirecionar automaticamente** da página de instalação, mesmo se já estiver instalado. Isso permite que você re-instale o sistema quantas vezes forem necessárias.

## 🔧 Como Instalar Corretamente

### Passo 1: Criar Banco de Dados na Hostinger

1. Acesse o **hPanel** da Hostinger
2. Vá em **Bancos de Dados MySQL**
3. Crie um novo banco de dados:
   - **Nome do banco**: (ex: `u624766619_encartes`)
   - **Usuário**: crie um usuário com senha
   - **Anote**: host, nome do banco, usuário e senha

### Passo 2: Acessar a Página de Instalação

1. Acesse: `https://seudominio.com/install.php`
2. O sistema mostrará o formulário de instalação

### Passo 3: Preencher Dados do Banco

- **Host**: geralmente `localhost` na Hostinger
- **Nome do Banco**: o que você criou (ex: `u624766619_encartes`)
- **Usuário**: o usuário do banco
- **Senha**: a senha do usuário

Clique em **"Testar Conexão"** para validar.

### Passo 4: Configurar URL Base

- O sistema detectará automaticamente a URL
- Exemplo: `https://seudominio.com` ou `https://seudominio.com/flyersaas`

### Passo 5: Criar Administrador

- Nome completo
- E-mail
- Senha (mínimo 6 caracteres)

### Passo 6: Aguardar Instalação

O sistema irá:
1. ✅ Criar todas as tabelas automaticamente
2. ✅ Criar o administrador
3. ✅ Criar planos padrão (Básico, Profissional, Enterprise)
4. ✅ Gerar arquivo `.env` com configurações
5. ✅ Redirecionar para a página inicial

## 🛠️ Tabelas Criadas Automaticamente

O instalador criará estas 8 tabelas:

| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários do sistema |
| `plans` | Planos de assinatura |
| `user_subscriptions` | Assinaturas dos usuários |
| `gallery_images` | Galeria de imagens compartilhada |
| `flyers` | Encartes digitais |
| `flyer_items` | Itens dos encartes |
| `system_config` | Configurações do sistema |
| `migrations` | Controle de migrações |

## ⚠️ Se o Erro Persistir

### Verifique as Permissões do Usuário do Banco

O usuário deve ter permissões para:
- `CREATE DATABASE`
- `CREATE TABLE`
- `INSERT`
- `SELECT`
- `UPDATE`
- `DELETE`

Na Hostinger, ao criar o banco, o usuário já recebe essas permissões automaticamente.

### Verifique se o Banco Foi Criado

1. No hPanel → Bancos de Dados MySQL
2. Clique em **Gerenciar** no banco criado
3. Abra o **phpMyAdmin**
4. Verifique se o banco existe (mesmo vazio)

### Limpeza para Reinstalar

Se precisar reinstalar:

1. Acesse `install.php` diretamente
2. O sistema permitirá reinstallar
3. As tabelas existentes serão ignoradas (usa `CREATE TABLE IF NOT EXISTS`)
4. O administrador só será criado se não existir

## 📁 Arquivo .env

Após instalação, será criado o arquivo `.env` na raiz do sistema:

```ini
DB_HOST=localhost
DB_NAME=u624766619_encartes
DB_USER=seu_usuario
DB_PASS=sua_senha
BASE_URL=https://seudominio.com
INSTALLED=true
```

**Importante**: Este arquivo terá permissão somente-leitura (0444) por segurança.

## 🎯 Após Instalação

1. Acesse: `https://seudominio.com` ou `https://seudominio.com/index.php`
2. Faça login com e-mail e senha do administrador
3. Você será redirecionado para o painel administrativo

## 🔐 Acesso Administrativo

- **URL**: `https://seudominio.com/admin/`
- Use o e-mail e senha criados na instalação

---

## ❓ Suporte

Se ainda tiver problemas:

1. Verifique os logs de erro do PHP
2. Ative o modo debug no php.ini
3. Contate o suporte da Hostinger para verificar permissões do banco
