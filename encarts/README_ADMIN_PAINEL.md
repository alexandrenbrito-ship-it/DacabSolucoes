# Painel Administrativo Encarts - Documentação

## Visão Geral

O sistema de painel administrativo do Encarts permite que administradores gerenciem:
- **Tipos de Usuários (Roles)**: Admin, User, Editor
- **Planos de Assinatura**: Com limites configuráveis de encarts e uploads
- **Recursos por Plano**: Quantidade de encarts e imagens por plano
- **Atribuição de Assinaturas**: Atribuir planos a usuários específicos

## Arquivos Implementados

### 1. `/encarts/admin.php`
Painel administrativo principal com interface completa.

**Funcionalidades:**
- Dashboard com visão geral (total de usuários, planos, roles)
- Gerenciamento de usuários (listar, editar role, ativar/desativar)
- Gerenciamento de planos (criar, editar, excluir)
- Visualização de tipos de usuário (roles)
- Interface para atribuir assinaturas a usuários

**Acesso:** Apenas usuários com role "admin" podem acessar.

### 2. `/encarts/api/admin.php` (Atualizado)
API backend para operações administrativas.

**Endpoints disponíveis:**

#### Usuários
- `GET ?action=get_users&page=1&limit=20` - Lista todos os usuários com paginação
- `GET ?action=get_user&user_id=1` - Obtém detalhes de um usuário específico
- `POST ?action=update_role` - Atualiza o tipo de usuário (role)
- `POST ?action=update_plan` - Atualiza o plano de um usuário
- `POST ?action=deactivate_user` - Ativa/desativa usuário

#### Planos
- `GET ?action=get_plans` - Lista todos os planos ativos
- `GET ?action=get_plan&plan_id=1` - Obtém detalhes de um plano
- `GET ?action=get_plan_stats` - Estatísticas de usuários por plano
- `POST ?action=create_plan` - Cria novo plano
- `POST ?action=update_plan_item` - Atualiza plano existente
- `POST ?action=delete_plan` - Remove plano

### 3. `/encarts/classes/User.php` (Atualizado)
Classe User com métodos aprimorados para administração.

**Métodos principais:**
- `getAll($page, $limit)` - Lista usuários com contagem de encarts
- `getById($id)` - Obtém usuário com dados do plano e role
- `updateRole($userId, $roleId)` - Atualiza tipo de usuário
- `updatePlan($userId, $planId)` - Atualiza plano do usuário
- `isAdmin($userId)` - Verifica se usuário é administrador
- `getPlanLimits($userId)` - Retorna limites do plano do usuário

### 4. `/encarts/classes/Plan.php`
Classe para gerenciamento de planos.

**Métodos principais:**
- `getAllActive()` - Lista planos ativos
- `getById($id)` - Obtém plano por ID
- `getBySlug($slug)` - Obtém plano por slug
- `create($data)` - Cria novo plano
- `update($id, $data)` - Atualiza plano
- `delete($id)` - Remove plano
- `checkLimits($userId, $type)` - Verifica limites de uso
- `getPlanStats()` - Estatísticas de uso dos planos

## Estrutura do Banco de Dados

### Tabela `roles`
```sql
- id: INT (PK)
- name: VARCHAR(50) - 'admin', 'user', 'editor'
- description: VARCHAR(255)
- created_at: TIMESTAMP
```

### Tabela `plans`
```sql
- id: INT (PK)
- name: VARCHAR(100) - Nome exibido
- slug: VARCHAR(50) - Identificador único (free, pro, enterprise)
- price: DECIMAL(10,2) - Preço mensal
- max_encarts: INT - Quantidade máxima de encartes
- max_uploads: INT - Quantidade máxima de uploads na galeria
- is_active: TINYINT(1) - Plano ativo para novas assinaturas
- features: JSON - Características extras
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

### Tabela `users`
```sql
- id: INT (PK)
- clerk_user_id: VARCHAR(255) - ID no Clerk
- clerk_email: VARCHAR(255) - Email do Clerk
- name: VARCHAR(100) - Nome completo
- email: VARCHAR(255) - Email único
- role_id: INT (FK -> roles.id) - Perfil de acesso
- plan_id: INT (FK -> plans.id) - Plano de assinatura
- subscription_status: ENUM - 'active', 'cancelled', 'expired', 'trial'
- subscription_expires_at: DATETIME - Data de expiração
- avatar_url: VARCHAR(500) - URL do avatar
- is_active: TINYINT(1) - Conta ativa
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

## Como Usar

### Acessar o Painel Admin
1. Faça login com uma conta de usuário admin
2. Acesse: `https://seudominio.com/encarts/admin.php`
3. Se não for admin, será redirecionado ao dashboard

### Criar Novo Plano
1. No painel admin, clique na aba "Planos"
2. Clique em "Novo Plano"
3. Preencha os dados:
   - Nome do Plano (ex: "Profissional")
   - Slug (ex: "pro")
   - Preço (R$)
   - Máximo de Encarts
   - Máximo de Uploads
   - Recursos (JSON opcional)
4. Clique em "Salvar Plano"

### Atribuir Assinatura a Usuário
1. No painel admin, clique na aba "Atribuir Assinatura"
2. Digite nome ou email para buscar usuário
3. Selecione o usuário na lista
4. Escolha o plano desejado
5. Clique em "Confirmar Atribuição"

### Alterar Tipo de Usuário (Role)
1. No painel admin, clique na aba "Usuários"
2. Clique no botão de engrenagem (⚙️) ao lado do usuário
3. Digite o novo ID do tipo:
   - 1 = Admin
   - 2 = User
   - 3 = Editor
4. Confirme a alteração

### Editar Plano Existente
1. No painel admin, clique na aba "Planos"
2. Clique em "Editar" no cartão do plano
3. Modifique os campos desejados
4. Clique em "Atualizar Plano"

## Planos Padrão

O sistema já inclui 3 planos pré-configurados:

### Gratuito (free)
- Preço: R$ 0,00
- Encarts: 3
- Uploads: 5
- Recursos: Watermark, Suporte comunitário, Templates básicos

### Profissional (pro)
- Preço: R$ 29,90
- Encarts: 50
- Uploads: 100
- Recursos: Sem watermark, Suporte prioritário, Todos templates, Analytics

### Empresarial (enterprise)
- Preço: R$ 99,90
- Encarts: 9999 (ilimitado)
- Uploads: 9999 (ilimitado)
- Recursos: Todos recursos + API access + White label

## Tipos de Usuário (Roles) Padrão

1. **Admin** (ID: 1)
   - Acesso total ao painel administrativo
   - Gerencia usuários, planos e configurações

2. **User** (ID: 2)
   - Usuário padrão
   - Acesso aos encartes e galeria pessoal
   - Limites conforme plano

3. **Editor** (ID: 3)
   - Pode criar conteúdo na galeria pública
   - Permissões intermediárias

## Validações e Segurança

- Todas as APIs verificam autenticação via Clerk
- Apenas usuários com role "admin" podem acessar endpoints administrativos
- Dados sensíveis são sanitizados antes de salvar no banco
- SQL injection prevenido com prepared statements
- XSS prevenido com htmlspecialchars nas saídas

## Integração com Clerk

O sistema usa Clerk para:
- Autenticação de usuários
- Verificação de sessão
- Dados básicos do usuário (nome, email, avatar)

A sincronização entre Clerk e o banco local é feita via webhook (`api/clerk_webhook.php`).

## Próximos Passos Sugeridos

1. **Implementar notificações por email** quando plano for alterado
2. **Adicionar histórico de mudanças** de plano/role
3. **Criar relatórios** de uso e faturamento
4. **Implementar pagamentos** recorrentes
5. **Adicionar permissões granulares** por role
6. **Criar logs de auditoria** para ações administrativas

## Suporte

Para dúvidas ou problemas, consulte:
- Documentação do Clerk: https://clerk.com/docs
- Logs de erro: Verificar `error_log` do PHP
- Banco de dados: Tabelas no schema `/encarts/sql/schema.sql`
