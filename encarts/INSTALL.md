# Instruções de Instalação - Encarts Digital System

## 📋 Pré-requisitos
- Servidor web Apache com mod_rewrite habilitado
- PHP 7.4 ou superior
- MySQL 5.7 ou superior (ou MariaDB)
- Acesso FTP ou gerenciador de arquivos da hospedagem

## 🚀 Instalação Rápida na Hostinger

### Opção 1: Instalador Automático (Recomendado)

1. **Faça upload de todos os arquivos** para sua hospedagem via FTP
   - Pasta: `/public_html/encarts/` ou `/public_html/`

2. **Acesse o instalador** no navegador:
   ```
   https://seudominio.com/encarts/install.php
   ```

3. **Preencha as configurações**:
   - URL da Aplicação: `https://seudominio.com/encarts`
   - Host do Banco: `localhost`
   - Nome do Banco: `u624766619_encartes`
   - Usuário: `u624766619_encartes`
   - Senha: `Pt190912!@#`

4. **Clique em "Salvar Configurações"**

5. **Instale o banco de dados** clicando no botão correspondente

6. **Pronto!** Acesse `index.php` para fazer login

### Opção 2: Instalação Manual

1. **Crie o banco de dados** no painel da Hostinger:
   - Nome: `u624766619_encartes`
   - Usuário: `u624766619_encartes`
   - Senha: `Pt190912!@#`

2. **Importe o script SQL**:
   - Acesse phpMyAdmin na Hostinger
   - Selecione o banco `u624766619_encartes`
   - Importe o arquivo `/sql/schema.sql`

3. **Configure o arquivo .env**:
   - Copie `.env.example` para `.env`
   - Edite com suas credenciais (já vem pré-configurado)

4. **Defina permissões nas pastas**:
   ```bash
   chmod 755 /assets/uploads/
   chmod 644 /.env
   ```

5. **Acesse** `https://seudominio.com/encarts/index.php`

## 🔐 Contas de Teste Criadas Automaticamente

| Tipo | Email | Senha |
|------|-------|-------|
| Admin | admin@encarts.com | admin123 |
| User Free | user@free.com | user123 |
| User Pro | user@pro.com | user123 |

## 📁 Estrutura de Arquivos

```
/encarts/
├── install.php          ← ACESSE ESTE ARQUIVO PARA INSTALAR
├── index.php            ← Login/Register
├── dashboard.php        ← Painel do usuário
├── editor.php           ← Editor visual de encarts
├── .env                 ← Configurações (criado pelo instalador)
├── .htaccess            ← Regras Apache
│
├── /config/
│   └── database.php     ← Conexão PDO
│
├── /classes/
│   ├── Database.php     ← Singleton PDO
│   ├── Auth.php         ← Autenticação
│   ├── User.php         ← CRUD usuários
│   ├── Encart.php       ← CRUD encarts
│   └── Template.php     ← CRUD templates
│
├── /api/
│   ├── auth.php         ← Login/Logout/Register
│   ├── encarts.php      ← CRUD encarts
│   ├── templates.php    ← Templates disponíveis
│   └── upload.php       ← Upload de imagens
│
├── /assets/
│   ├── /css/            ← Estilos CSS
│   ├── /js/             ← Scripts JavaScript
│   └── /uploads/        ← Imagens dos usuários
│
└── /sql/
    └── schema.sql       ← Script do banco
```

## ⚙️ Configurações Personalizadas

Se precisar alterar as credenciais do banco, edite o arquivo `.env`:

```env
DB_HOST=localhost
DB_NAME=u624766619_encartes
DB_USER=u624766619_encartes
DB_PASS=Pt190912!@#
```

## 🔧 Solução de Problemas

### Erro de conexão com banco de dados
- Verifique se as credenciais estão corretas no `.env`
- Confirme se o banco foi criado no painel da Hostinger
- Verifique se o usuário tem permissões no banco

### Erro 403 Forbidden
- Verifique se o `.htaccess` foi enviado corretamente
- Confira as permissões das pastas (755 para diretórios, 644 para arquivos)

### Upload não funciona
- Verifique a permissão da pasta `/assets/uploads/` (deve ser 755)
- Confirme o `upload_max_filesize` e `post_max_size` no php.ini

### Páginas em branco
- Verifique os logs de erro da Hostinger
- Ative `APP_DEBUG=true` no `.env` temporariamente

## 🗑️ Pós-Instalação

Após instalar, **delete o arquivo `install.php`** por segurança:
```bash
rm install.php
```

## 📞 Suporte

Para mais informações, consulte a documentação completa ou entre em contato com o suporte.

---
**Versão:** 1.0.0  
**Desenvolvido para:** Hostinger  
**Tecnologias:** PHP puro, MySQL, Canvas HTML5, Bootstrap 5
