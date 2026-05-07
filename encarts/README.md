# 🎨 Encarts - Sistema de Criação de Encarts Digitais

Sistema completo para criação, edição e exportação de encarts digitais (banners/cards para redes sociais) com editor visual baseado em Canvas HTML5.

## 🚀 INSTALAÇÃO RÁPIDA - HOSTINGER

### Passo 1: Upload dos Arquivos
1. Acesse sua conta Hostinger
2. Vá em **Gerenciador de Arquivos** ou use **FTP**
3. Faça upload de TODOS os arquivos para:
   - `/public_html/encarts/` (recomendado)
   - OU `/public_html/` (se quiser na raiz)

### Passo 2: Acessar o Instalador
No seu navegador, acesse:
```
https://seudominio.com/encarts/install.php
```

### Passo 3: Preencher Configurações
O instalador já vem pré-preenchido com seus dados:

| Campo | Valor Pré-configurado |
|-------|----------------------|
| **URL da Aplicação** | `https://seudominio.com/encarts` |
| **Host do Banco** | `localhost` |
| **Nome do Banco** | `u624766619_encartes` |
| **Usuário** | `u624766619_encartes` |
| **Senha** | `Pt190912!@#` |

Basta clicar em **"Salvar Configurações"** → **"Instalar Banco de Dados"**

### Passo 4: Pronto! 🎉
Acesse `index.php` e faça login com uma das contas:

| Perfil | Email | Senha |
|--------|-------|-------|
| Admin | admin@encarts.com | admin123 |
| Usuário Free | user@free.com | user123 |
| Usuário Pro | user@pro.com | user123 |

---

## 📋 PRÉ-REQUISITOS

- ✅ PHP 7.4 ou superior
- ✅ MySQL 5.7+ ou MariaDB
- ✅ Apache com mod_rewrite
- ✅ Permissões de escrita na pasta do projeto

---

## 🔧 CONFIGURAÇÕES PERSONALIZADAS

Se precisar alterar as credenciais do banco de dados:

### Opção A: Pelo Instalador
Durante a instalação, edite os campos no formulário.

### Opção B: Manualmente no arquivo `.env`
Após instalar, edite `/encarts/.env`:

```env
DB_HOST=localhost
DB_NAME=u624766619_encartes
DB_USER=u624766619_encartes
DB_PASS=Pt190912!@#
```

---

## 📁 ESTRUTURA DO PROJETO

```
/encarts/
├── install.php          ⭐ ACESSE ESTE ARQUIVO PARA INSTALAR
├── index.php            Login e Registro
├── dashboard.php        Painel do Usuário
├── editor.php           Editor Visual de Encarts
├── .env                 Configurações (criado automaticamente)
├── .htaccess            Regras Apache
│
├── /config/
│   └── database.php     Conexão PDO com MySQL
│
├── /classes/
│   ├── Database.php     Singleton PDO
│   ├── Auth.php         Autenticação e Sessões
│   ├── User.php         CRUD de Usuários
│   ├── Encart.php       CRUD de Encarts
│   └── Template.php     CRUD de Templates
│
├── /api/
│   ├── auth.php         Login/Logout/Registro
│   ├── encarts.php      Criar/Editar/Excluir Encarts
│   ├── templates.php    Listar Templates
│   └── upload.php       Upload de Imagens
│
├── /assets/
│   ├── /css/            Estilos CSS
│   ├── /js/             Scripts JavaScript
│   └── /uploads/        Imagens dos usuários
│
└── /sql/
    └── schema.sql       Script do banco de dados
```

---

## ✨ FUNCIONALIDADES

### Editor Visual
- ✅ Canvas HTML5 com zoom e grid
- ✅ Tamanhos predefinidos (Post, Story, Banner, Cover)
- ✅ Adicionar textos com fontes Google Fonts
- ✅ Upload e redimensionamento de imagens
- ✅ Formas geométricas (retângulo, círculo, triângulo)
- ✅ Fundos: cor sólida, gradiente ou imagem
- ✅ Camadas com reordenação drag-and-drop
- ✅ Desfazer/Refazer alterações
- ✅ Exportar em PNG ou JPG

### Dashboard
- ✅ Grid com todos os encarts salvos
- ✅ Preview em thumbnail
- ✅ Editar, duplicar, excluir
- ✅ Toggle público/privado
- ✅ Contador de uso por plano (Free: 5, Pro: ilimitado)

### Segurança
- ✅ PDO com prepared statements
- ✅ Proteção contra XSS (htmlspecialchars)
- ✅ Tokens CSRF em formulários
- ✅ Validação de upload (mime-type, extensão, tamanho)
- ✅ Rate limiting no login (10 tentativas/15min)
- ✅ Senhas hash com password_verify
- ✅ Sessões seguras com tokens únicos

---

## 🐛 SOLUÇÃO DE PROBLEMAS

### Erro ao conectar no banco
1. Verifique se o banco `u624766619_encartes` existe no phpMyAdmin
2. Confirme usuário e senha no painel da Hostinger
3. Teste as credenciais manualmente no phpMyAdmin

### Página em branco
1. Ative `APP_DEBUG=true` no arquivo `.env` temporariamente
2. Verifique os logs de erro na Hostinger
3. Confira se todos os arquivos foram enviados

### Erro 403 Forbidden
1. Verifique se o `.htaccess` foi enviado
2. Permissões corretas:
   - Pastas: `755`
   - Arquivos: `644`

### Upload não funciona
1. Pasta `/assets/uploads/` deve ter permissão `755`
2. Verifique `upload_max_filesize` no PHP (mínimo 5MB)

---

## 🔒 PÓS-INSTALAÇÃO (IMPORTANTE)

Após instalar com sucesso:

1. **Delete o arquivo `install.php`**:
   ```bash
   # Via FTP ou Gerenciador de Arquivos
   Delete: /encarts/install.php
   ```

2. **Proteja o arquivo `.env`** (já feito automaticamente):
   - O instalador cria `/config/.htaccess` bloqueando acesso

3. **Teste todas as funcionalidades**:
   - Login/Logout
   - Criação de encart
   - Upload de imagem
   - Exportação PNG/JPG

---

## 📊 BANCO DE DADOS

O sistema cria automaticamente 6 tabelas:

| Tabela | Descrição |
|--------|-----------|
| `users` | Usuários do sistema |
| `encarts` | Encarts criados pelos usuários |
| `templates` | Templates pré-definidos |
| `user_uploads` | Histórico de uploads |
| `sessions` | Sessões ativas |
| `login_attempts` | Tentativas de login (rate limiting) |

São inseridos automaticamente:
- 3 usuários de teste
- 5 templates de exemplo com JSON realista

---

## 🌐 URLs DO SISTEMA

| Página | URL |
|--------|-----|
| Login | `/encarts/index.php` |
| Dashboard | `/encarts/dashboard.php` |
| Editor | `/encarts/editor.php` |
| Instalação | `/encarts/install.php` |

---

## 📞 SUPORTE TÉCNICO

Para mais informações ou problemas:

1. Consulte `INSTALL.md` para detalhes avançados
2. Verifique os logs de erro da Hostinger
3. Revise as configurações no arquivo `.env`

---

## 🛠️ TECNOLOGIAS UTILIZADAS

- **Backend:** PHP puro (sem frameworks)
- **Banco:** MySQL com PDO
- **Frontend:** HTML5 Canvas, JavaScript vanilla
- **Estilos:** Bootstrap 5 (CDN)
- **Ícones:** Bootstrap Icons (CDN)
- **Fontes:** Google Fonts

---

## 📝 LICENÇA

Sistema desenvolvido para uso na Hostinger.

---

**Versão:** 1.0.0  
**Desenvolvido por:** Equipe Encarts  
**Última atualização:** 2024

---

## ⚡ RESUMO RÁPIDO

1. **Upload** → Todos os arquivos via FTP
2. **Acesse** → `https://seudominio.com/encarts/install.php`
3. **Configure** → Dados já estão pré-preenchidos
4. **Instale** → Clique em "Instalar Banco de Dados"
5. **Use** → Acesse `index.php` e faça login
6. **Proteja** → Delete `install.php` após uso

**Pronto! Seu sistema está no ar! 🎉**
