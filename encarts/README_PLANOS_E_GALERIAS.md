# 🎨 Encarts - Sistema Completo com Planos, Galerias e Painel Admin

## 📋 Resumo das Alterações

### 1. **Banco de Dados Atualizado** (`sql/schema.sql`)

#### Novas Tabelas Criadas:
- **`roles`**: Perfis de acesso (admin, user, editor)
- **`plans`**: Planos de assinatura com limites configuráveis
- **`gallery_categories`**: Categorias da galeria pública
- **`public_gallery_items`**: Itens da galeria pública (gerida por admins)
- **`user_galleries`**: Galeria pessoal dos usuários (uploads privados)

#### Tabelas Modificadas:
- **`users`**: 
  - Adicionado `role_id` (FK para roles)
  - Adicionado `plan_id` (FK para plans)
  - Adicionado `subscription_status`
  - Adicionado `subscription_expires_at`
  - Removido campo `plan` (ENUM) antigo

### 2. **Novas Classes PHP**

#### `/classes/Plan.php`
Gerenciamento completo de planos de assinatura:
- `getAllActive()`: Lista planos ativos
- `getById()`, `getBySlug()`: Busca de planos
- `create()`, `update()`, `delete()`: CRUD de planos (admin)
- `checkLimits()`: Verifica limites de uso por usuário
- `getPlanStats()`: Estatísticas de assinaturas

#### `/classes/User.php` (Atualizada)
CRUD de usuários com integração Clerk e sistema de roles/planos:
- `createFromClerk()`: Cria usuário via webhook do Clerk
- `getById()`, `getByClerkId()`, `getByEmail()`: Buscas
- `update()`, `updatePlan()`, `updateRole()`: Atualizações
- `deactivate()`: Desativa usuário
- `getAll()`: Lista com paginação
- `countEncarts()`, `countUploads()`: Contadores de uso
- `getPlanLimits()`: Limites do plano do usuário
- `isAdmin()`, `hasRole()`: Verificação de permissões

### 3. **Novas APIs**

#### `/api/admin.php`
Painel administrativo completo (requer role=admin):
- `GET ?action=get_users`: Lista todos usuários (paginado)
- `GET ?action=get_user&user_id=X`: Detalhes de usuário
- `POST ?action=update_role`: Altera perfil de usuário
- `POST ?action=update_plan`: Altera plano de usuário
- `POST ?action=deactivate_user`: Desativa usuário
- `GET ?action=get_plans`: Lista planos
- `GET ?action=get_plan_stats`: Estatísticas
- `POST ?action=create_plan`: Cria novo plano
- `POST ?action=update_plan_item`: Atualiza plano
- `POST ?action=delete_plan`: Remove plano

#### `/api/gallery.php`
API de galerias (pública e pessoal):
- `GET ?action=get_public`: Lista galeria pública
- `GET ?action=get_categories`: Lista categorias
- `GET ?action=get_personal`: Galeria pessoal do usuário
- `POST ?action=upload_personal`: Upload para galeria pessoal (com verificação de limites)
- `POST ?action=delete_personal`: Remove imagem pessoal

### 4. **Arquivo `.env.example` Atualizado**

```env
DB_HOST=localhost
DB_NAME=u624766619_encartes
DB_USER=u624766619_admin
DB_PASS=sua_senha_forte

CLERK_PUBLISHABLE_KEY=pk_test_xxxxxxxxx
CLERK_SECRET_KEY=sk_test_xxxxxxxxx
CLERK_WEBHOOK_SECRET=whsec_xxxxxxxxx
CLERK_JWKS_URL=https://api.clerk.com/v1/jwks
CLERK_ISSUER=https://seu-dominio.clerk.accounts.dev

APP_URL=https://seudominio.com.br
```

---

## 🚀 Como Usar

### Passo 1: Atualizar Banco de Dados

1. Acesse o phpMyAdmin na Hostinger
2. Selecione seu banco de dados
3. Execute o arquivo `sql/schema.sql` atualizado

**OU** use o instalador automático:
```
https://seudominio.com.br/encarts/install.php
```

### Passo 2: Configurar Clerk Dashboard

1. Acesse https://dashboard.clerk.com
2. Configure os provedores de login (Google, GitHub, Email/Senha)
3. Obtenha as chaves API no menu "API Keys"
4. Configure Webhook:
   - Endpoint: `https://seudominio.com.br/encarts/api/clerk_webhook.php`
   - Eventos: `user.created`, `user.updated`, `user.deleted`

### Passo 3: Primeiro Acesso Admin

Após a instalação, o primeiro usuário que fizer login precisa ser promovido a admin manualmente no banco:

```sql
UPDATE users SET role_id = 1 WHERE clerk_email = 'seu@email.com';
```

### Passo 4: Painel Administrativo

O painel admin permite:
- ✅ Criar/editar/excluir planos de assinatura
- ✅ Definir limites de encartes e uploads por plano
- ✅ Atribuir planos aos usuários
- ✅ Promover/demover usuários (admin, user, editor)
- ✅ Gerenciar galeria pública (em breve)

### Passo 5: Fluxo do Usuário

1. **Login**: Usuário faz login via Clerk em `index.php`
2. **Dashboard**: Redirecionado para `dashboard.php`
3. **Limites**: Sistema verifica limites do plano automaticamente
4. **Galeria Pessoal**: Upload de imagens limitado pelo plano
5. **Galeria Pública**: Acesso a imagens compartilhadas por categoria

---

## 📊 Planos Padrão (Seeders)

| Plano | Preço | Encartes | Uploads | Recursos |
|-------|-------|----------|---------|----------|
| Gratuito | R$ 0 | 3 | 5 | Marca d'água, Suporte comunidade |
| Profissional | R$ 29,90 | 50 | 100 | Sem marca d'água, Suporte prioritário, Analytics |
| Empresarial | R$ 99,90 | ∞ | ∞ | Tudo + API, White Label |

---

## 🔐 Sistema de Roles

| Role | ID | Permissões |
|------|----|------------|
| admin | 1 | Acesso total ao painel, gerencia usuários, planos, galeria pública |
| user | 2 | Cria encartes, gallery pessoal, usa galeria pública |
| editor | 3 | Mesmo que user + pode adicionar conteúdo à galeria pública |

---

## 📁 Estrutura de Diretórios

```
/encarts/
├── api/
│   ├── admin.php         ← NOVO: API administrativa
│   ├── gallery.php       ← NOVO: API de galerias
│   ├── clerk_webhook.php
│   ├── encarts.php
│   ├── templates.php
│   └── upload.php
├── classes/
│   ├── Plan.php          ← NOVA: Classe de planos
│   ├── User.php          ← ATUALIZADA: Com roles/planos
│   ├── ClerkAuth.php
│   ├── Database.php
│   ├── Encart.php
│   └── Template.php
├── sql/
│   └── schema.sql        ← ATUALIZADO: Novas tabelas
├── assets/
│   └── uploads/
│       └── galleries/    ← NOVO: Pastas das galerias
│           └── {user_id}/
└── .env.example          ← ATUALIZADO: Variáveis Clerk
```

---

## ⚠️ Importante

1. **Segurança**: Todas as APIs admin verificam `ClerkAuth::requireAuth()` + `isAdmin()`
2. **Limites**: Uploads e criação de encartes são validados contra o plano do usuário
3. **Webhooks**: Clerk notifica automaticamente sobre novos usuários via webhook
4. **Cache JWKS**: Tokens JWT são validados com cache de 1 hora para performance

---

## 🛠️ Próximos Passos Sugeridos

1. Criar página `admin.php` no frontend para interface gráfica do painel
2. Implementar upload em lote na galeria pública (apenas admin/editor)
3. Adicionar sistema de pagamentos para upgrade de plano
4. Criar página de galeria pública acessível a todos usuários
5. Implementar busca e filtros nas galerias

---

## 📞 Suporte

Para dúvidas ou problemas, consulte os logs em:
- `error_log` do PHP
- Console do navegador (frontend)
- Dashboard do Clerk (eventos e webhooks)
