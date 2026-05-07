-- ============================================================
-- /sql/schema.sql
-- Script de criação do banco de dados Encarts Digital System
-- Compatível com MySQL 5.7+ e MariaDB 10.2+
-- Charset: utf8mb4 (suporte completo a emojis e caracteres especiais)
-- 
-- INSTALAÇÃO HOSTINGER:
-- Este script será executado automaticamente pelo install.php
-- OU pode ser importado manualmente via phpMyAdmin
-- 
-- IMPORTANTE: O banco de dados deve ser criado no painel da Hostinger
-- antes de executar este script. Use: u624766619_encartes
-- ============================================================

-- Seleciona o banco de dados (necessário para importação manual)
-- Substitua 'u624766619_encartes' pelo nome do seu banco se for diferente
USE `u624766619_encartes`;

-- ============================================================
-- TABELA: roles
-- Perfis de acesso do sistema (Admin, User, Editor)
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nome do role: admin, user, editor',
    `description` VARCHAR(255) COMMENT 'Descrição das permissões',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Perfis de acesso do sistema';

-- ============================================================
-- TABELA: plans
-- Planos de assinatura com limites de uso
-- ============================================================
CREATE TABLE IF NOT EXISTS `plans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nome exibido do plano',
    `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identificador único: free, pro, enterprise',
    `price` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Preço mensal',
    `max_encarts` INT UNSIGNED DEFAULT 3 COMMENT 'Quantidade máxima de encartes',
    `max_uploads` INT UNSIGNED DEFAULT 5 COMMENT 'Quantidade máxima de uploads na galeria pessoal',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Plano ativo para novas assinaturas',
    `features` JSON COMMENT 'Características extras em JSON',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_slug` (`slug`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Planos de assinatura';

-- ============================================================
-- TABELA: users
-- Armazena os usuários do sistema com informações de conta e plano
-- Integração com Clerk para autenticação
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `clerk_user_id` VARCHAR(255) UNIQUE NOT NULL COMMENT 'ID único do usuário no Clerk (ex: user_2abc123xyz)',
    `clerk_email` VARCHAR(255) NOT NULL COMMENT 'Email vindo do Clerk',
    `name` VARCHAR(100) NOT NULL COMMENT 'Nome completo do usuário',
    `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'E-mail único para referência',
    `role_id` INT UNSIGNED DEFAULT 2 COMMENT 'Perfil de acesso (1=admin, 2=user, 3=editor)',
    `plan_id` INT UNSIGNED DEFAULT 1 COMMENT 'Plano de assinatura atual',
    `subscription_status` ENUM('active', 'cancelled', 'expired', 'trial') DEFAULT 'active' COMMENT 'Status da assinatura',
    `subscription_expires_at` DATETIME NULL COMMENT 'Data de expiração da assinatura',
    `avatar_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL da imagem de perfil',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação da conta',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',
    `last_login` TIMESTAMP NULL DEFAULT NULL COMMENT 'Último login realizado',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Conta ativa (1) ou desativada (0)',
    
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE SET DEFAULT,
    INDEX `idx_clerk_user_id` (`clerk_user_id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role_id`),
    INDEX `idx_plan` (`plan_id`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de usuários do sistema';

-- ============================================================
-- TABELA: user_sync_log
-- Registro de sincronização de usuários via webhook do Clerk
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sync_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `clerk_user_id` VARCHAR(255) NOT NULL COMMENT 'ID do usuário no Clerk',
    `event_type` VARCHAR(50) NOT NULL COMMENT 'Tipo de evento: user.created, user.updated, user.deleted',
    `payload` JSON COMMENT 'Payload completo do webhook',
    `synced_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da sincronização',
    
    INDEX `idx_clerk_user_id` (`clerk_user_id`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_synced_at` (`synced_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de sincronização de usuários via Clerk webhook';

-- ============================================================
-- TABELA: gallery_categories
-- Categorias para organização da galeria pública
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nome da categoria',
    `slug` VARCHAR(100) UNIQUE NOT NULL COMMENT 'Slug único para URL',
    `description` TEXT COMMENT 'Descrição da categoria',
    `sort_order` INT DEFAULT 0 COMMENT 'Ordem de exibição',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Categoria ativa',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX `idx_slug` (`slug`),
    INDEX `idx_sort` (`sort_order`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorias da galeria pública';

-- ============================================================
-- TABELA: public_gallery_items
-- Itens da galeria pública (gerenciada pelos administradores)
-- ============================================================
CREATE TABLE IF NOT EXISTS `public_gallery_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED COMMENT 'Categoria da imagem',
    `title` VARCHAR(255) COMMENT 'Título descritivo',
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Caminho do arquivo no servidor',
    `file_type` VARCHAR(50) DEFAULT 'image' COMMENT 'Tipo de arquivo',
    `file_size` INT UNSIGNED COMMENT 'Tamanho em bytes',
    `width` INT UNSIGNED COMMENT 'Largura em pixels',
    `height` INT UNSIGNED COMMENT 'Altura em pixels',
    `tags` VARCHAR(255) COMMENT 'Tags separadas por vírgula',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Item ativo',
    `created_by` INT UNSIGNED COMMENT 'ID do admin que fez upload',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_category` (`category_id`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens da galeria pública';

-- ============================================================
-- TABELA: user_galleries
-- Galeria pessoal dos usuários (uploads privados)
-- Substitui/enriquece a tabela user_uploads
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_galleries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário proprietário',
    `title` VARCHAR(255) COMMENT 'Título descritivo da imagem',
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Caminho do arquivo no servidor',
    `original_name` VARCHAR(255) NOT NULL COMMENT 'Nome original do arquivo',
    `file_type` VARCHAR(50) DEFAULT 'image' COMMENT 'Tipo de arquivo',
    `file_size` INT UNSIGNED NOT NULL COMMENT 'Tamanho em bytes',
    `width` INT UNSIGNED COMMENT 'Largura em pixels',
    `height` INT UNSIGNED COMMENT 'Altura em pixels',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Imagem ativa',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Galeria pessoal dos usuários';

-- ============================================================
-- TABELA: templates
-- Templates pré-definidos disponíveis para os usuários
-- ============================================================
CREATE TABLE IF NOT EXISTS `templates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nome descritivo do template',
    `category` ENUM('post', 'story', 'banner', 'cover', 'promo', 'minimal', 'business') DEFAULT 'post' COMMENT 'Categoria do template',
    `canvas_data` JSON NOT NULL COMMENT 'Dados JSON completos do canvas do template',
    `thumbnail_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL da thumbnail de preview',
    `is_premium` TINYINT(1) DEFAULT 0 COMMENT 'Template premium (1) ou gratuito (0)',
    `order_position` INT DEFAULT 0 COMMENT 'Posição de ordenação na listagem',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação do template',
    
    INDEX `idx_category` (`category`),
    INDEX `idx_is_premium` (`is_premium`),
    INDEX `idx_order` (`order_position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Templates pré-definidos para encarts';

-- ============================================================
-- TABELA: encarts
-- Encarts criados pelos usuários com todos os dados do canvas
-- ============================================================
CREATE TABLE IF NOT EXISTS `encarts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário proprietário',
    `title` VARCHAR(200) NOT NULL COMMENT 'Título do encart',
    `canvas_data` JSON NOT NULL COMMENT 'Dados JSON completos do canvas',
    `width` INT UNSIGNED NOT NULL DEFAULT 1080 COMMENT 'Largura do canvas em pixels',
    `height` INT UNSIGNED NOT NULL DEFAULT 1080 COMMENT 'Altura do canvas em pixels',
    `format` ENUM('post', 'story', 'banner', 'cover', 'custom') DEFAULT 'post' COMMENT 'Formato predefinido',
    `thumbnail_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL da thumbnail gerada',
    `is_public` TINYINT(1) DEFAULT 0 COMMENT 'Encart público (1) ou privado (0)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última edição',
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_format` (`format`),
    INDEX `idx_is_public` (`is_public`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Encarts criados pelos usuários';

-- ============================================================
-- INSERÇÃO DE DADOS INICIAIS
-- ============================================================

-- Nota: Os usuários agora são criados automaticamente via Clerk
-- quando fazem o primeiro login. Não é necessário inserir usuários manuais.

-- Inserção de Roles (Perfis de Acesso)
INSERT INTO `roles` (`name`, `description`) VALUES 
('admin', 'Administrador com acesso total ao painel, planos, usuários e galeria pública'),
('user', 'Usuário padrão com acesso aos encartes e galeria pessoal'),
('editor', 'Usuário com permissão para criar conteúdo na galeria pública');

-- Inserção de Planos de Assinatura
INSERT INTO `plans` (`name`, `slug`, `price`, `max_encarts`, `max_uploads`, `features`) VALUES 
('Gratuito', 'free', 0.00, 3, 5, '{"watermark": true, "support": "community", "templates": "basic"}'),
('Profissional', 'pro', 29.90, 50, 100, '{"watermark": false, "support": "priority", "templates": "all", "analytics": true}'),
('Empresarial', 'enterprise', 99.90, 9999, 9999, '{"watermark": false, "support": "dedicated", "templates": "all", "api_access": true, "white_label": true}');

-- Inserção de Categorias da Galeria Pública
INSERT INTO `gallery_categories` (`name`, `slug`, `description`, `sort_order`) VALUES 
('Geral', 'geral', 'Imagens gerais disponíveis para todos os usuários', 1),
('Promoções', 'promocoes', 'Modelos e imagens para promoções e vendas', 2),
('Redes Sociais', 'redes-sociais', 'Assets otimizados para Instagram, Facebook e outras redes', 3),
('Eventos', 'eventos', 'Imagens para eventos, lançamentos e datas comemorativas', 4),
('Institucional', 'institucional', 'Elementos para comunicação institucional das empresas', 5);

-- Templates de exemplo com canvas_data JSON realista
INSERT INTO `templates` (`name`, `category`, `canvas_data`, `thumbnail_url`, `is_premium`, `order_position`) VALUES
(
    'Post Minimalista',
    'post',
    '{
        "version": "1.0",
        "width": 1080,
        "height": 1080,
        "background": {
            "type": "color",
            "value": "#ffffff"
        },
        "elements": [
            {
                "id": "txt-1",
                "type": "text",
                "x": 540,
                "y": 400,
                "width": 600,
                "height": 100,
                "rotation": 0,
                "zIndex": 1,
                "properties": {
                    "text": "Seu Título Aqui",
                    "fontSize": 48,
                    "fontFamily": "Poppins",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#333333",
                    "lineHeight": 1.2
                }
            },
            {
                "id": "txt-2",
                "type": "text",
                "x": 540,
                "y": 520,
                "width": 500,
                "height": 60,
                "rotation": 0,
                "zIndex": 2,
                "properties": {
                    "text": "Subtítulo ou descrição",
                    "fontSize": 24,
                    "fontFamily": "Open Sans",
                    "fontWeight": "normal",
                    "fontStyle": "italic",
                    "textAlign": "center",
                    "color": "#666666",
                    "lineHeight": 1.4
                }
            },
            {
                "id": "shape-1",
                "type": "rectangle",
                "x": 540,
                "y": 900,
                "width": 200,
                "height": 60,
                "rotation": 0,
                "zIndex": 3,
                "properties": {
                    "fillColor": "#3498db",
                    "borderWidth": 0,
                    "borderColor": "transparent",
                    "borderRadius": 30
                }
            },
            {
                "id": "txt-3",
                "type": "text",
                "x": 540,
                "y": 915,
                "width": 180,
                "height": 30,
                "rotation": 0,
                "zIndex": 4,
                "properties": {
                    "text": "Saiba Mais",
                    "fontSize": 18,
                    "fontFamily": "Poppins",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ffffff",
                    "lineHeight": 1
                }
            }
        ]
    }',
    NULL,
    0,
    1
),
(
    'Story Promo',
    'story',
    '{
        "version": "1.0",
        "width": 1080,
        "height": 1920,
        "background": {
            "type": "gradient",
            "direction": "linear",
            "startColor": "#667eea",
            "endColor": "#764ba2",
            "angle": 135
        },
        "elements": [
            {
                "id": "shape-1",
                "type": "circle",
                "x": 540,
                "y": 500,
                "width": 400,
                "height": 400,
                "rotation": 0,
                "zIndex": 1,
                "properties": {
                    "fillColor": "rgba(255,255,255,0.1)",
                    "borderWidth": 4,
                    "borderColor": "#ffffff",
                    "borderRadius": 200
                }
            },
            {
                "id": "txt-1",
                "type": "text",
                "x": 540,
                "y": 750,
                "width": 800,
                "height": 120,
                "rotation": 0,
                "zIndex": 2,
                "properties": {
                    "text": "PROMOÇÃO",
                    "fontSize": 72,
                    "fontFamily": "Montserrat",
                    "fontWeight": "900",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ffffff",
                    "lineHeight": 1
                }
            },
            {
                "id": "txt-2",
                "type": "text",
                "x": 540,
                "y": 880,
                "width": 700,
                "height": 80,
                "rotation": 0,
                "zIndex": 3,
                "properties": {
                    "text": "50% OFF",
                    "fontSize": 96,
                    "fontFamily": "Montserrat",
                    "fontWeight": "900",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#f1c40f",
                    "lineHeight": 1
                }
            },
            {
                "id": "txt-3",
                "type": "text",
                "x": 540,
                "y": 1600,
                "width": 600,
                "height": 50,
                "rotation": 0,
                "zIndex": 4,
                "properties": {
                    "text": "@suaempresa",
                    "fontSize": 28,
                    "fontFamily": "Open Sans",
                    "fontWeight": "normal",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ffffff",
                    "lineHeight": 1.2
                }
            }
        ]
    }',
    NULL,
    0,
    2
),
(
    'Banner Business',
    'banner',
    '{
        "version": "1.0",
        "width": 1200,
        "height": 628,
        "background": {
            "type": "color",
            "value": "#1a1a2e"
        },
        "elements": [
            {
                "id": "shape-1",
                "type": "rectangle",
                "x": 0,
                "y": 0,
                "width": 1200,
                "height": 628,
                "rotation": 0,
                "zIndex": 1,
                "properties": {
                    "fillColor": "rgba(22,33,62,0.8)",
                    "borderWidth": 0,
                    "borderColor": "transparent",
                    "borderRadius": 0
                }
            },
            {
                "id": "txt-1",
                "type": "text",
                "x": 100,
                "y": 200,
                "width": 700,
                "height": 100,
                "rotation": 0,
                "zIndex": 2,
                "properties": {
                    "text": "Soluções Empresariais",
                    "fontSize": 52,
                    "fontFamily": "Roboto",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "left",
                    "color": "#ffffff",
                    "lineHeight": 1.2
                }
            },
            {
                "id": "txt-2",
                "type": "text",
                "x": 100,
                "y": 320,
                "width": 600,
                "height": 80,
                "rotation": 0,
                "zIndex": 3,
                "properties": {
                    "text": "Transforme seu negócio com tecnologia",
                    "fontSize": 28,
                    "fontFamily": "Roboto",
                    "fontWeight": "300",
                    "fontStyle": "normal",
                    "textAlign": "left",
                    "color": "#b8b8b8",
                    "lineHeight": 1.4
                }
            },
            {
                "id": "shape-2",
                "type": "rectangle",
                "x": 100,
                "y": 450,
                "width": 220,
                "height": 60,
                "rotation": 0,
                "zIndex": 4,
                "properties": {
                    "fillColor": "#e94560",
                    "borderWidth": 0,
                    "borderColor": "transparent",
                    "borderRadius": 8
                }
            },
            {
                "id": "txt-3",
                "type": "text",
                "x": 210,
                "y": 470,
                "width": 200,
                "height": 30,
                "rotation": 0,
                "zIndex": 5,
                "properties": {
                    "text": "Contate-nos",
                    "fontSize": 18,
                    "fontFamily": "Roboto",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ffffff",
                    "lineHeight": 1
                }
            }
        ]
    }',
    NULL,
    1,
    3
),
(
    'Cover Facebook Tech',
    'cover',
    '{
        "version": "1.0",
        "width": 820,
        "height": 312,
        "background": {
            "type": "gradient",
            "direction": "linear",
            "startColor": "#0f0c29",
            "endColor": "#302b63",
            "angle": 90
        },
        "elements": [
            {
                "id": "shape-1",
                "type": "circle",
                "x": 700,
                "y": 156,
                "width": 250,
                "height": 250,
                "rotation": 0,
                "zIndex": 1,
                "properties": {
                    "fillColor": "rgba(255,255,255,0.05)",
                    "borderWidth": 2,
                    "borderColor": "rgba(255,255,255,0.2)",
                    "borderRadius": 125
                }
            },
            {
                "id": "txt-1",
                "type": "text",
                "x": 80,
                "y": 120,
                "width": 500,
                "height": 80,
                "rotation": 0,
                "zIndex": 2,
                "properties": {
                    "text": "Tech Solutions",
                    "fontSize": 48,
                    "fontFamily": "Poppins",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "left",
                    "color": "#ffffff",
                    "lineHeight": 1
                }
            },
            {
                "id": "txt-2",
                "type": "text",
                "x": 80,
                "y": 200,
                "width": 400,
                "height": 50,
                "rotation": 0,
                "zIndex": 3,
                "properties": {
                    "text": "Inovação e Tecnologia",
                    "fontSize": 22,
                    "fontFamily": "Open Sans",
                    "fontWeight": "300",
                    "fontStyle": "normal",
                    "textAlign": "left",
                    "color": "#a0a0a0",
                    "lineHeight": 1.2
                }
            }
        ]
    }',
    NULL,
    0,
    4
),
(
    'Post Promo Verão',
    'promo',
    '{
        "version": "1.0",
        "width": 1080,
        "height": 1080,
        "background": {
            "type": "gradient",
            "direction": "linear",
            "startColor": "#ff9a9e",
            "endColor": "#fecfef",
            "angle": 45
        },
        "elements": [
            {
                "id": "shape-1",
                "type": "triangle",
                "x": 900,
                "y": 150,
                "width": 200,
                "height": 200,
                "rotation": 45,
                "zIndex": 1,
                "properties": {
                    "fillColor": "rgba(255,255,255,0.3)",
                    "borderWidth": 0,
                    "borderColor": "transparent"
                }
            },
            {
                "id": "shape-2",
                "type": "circle",
                "x": 200,
                "y": 800,
                "width": 150,
                "height": 150,
                "rotation": 0,
                "zIndex": 2,
                "properties": {
                    "fillColor": "rgba(255,255,255,0.2)",
                    "borderWidth": 0,
                    "borderColor": "transparent",
                    "borderRadius": 75
                }
            },
            {
                "id": "txt-1",
                "type": "text",
                "x": 540,
                "y": 350,
                "width": 700,
                "height": 150,
                "rotation": 0,
                "zIndex": 3,
                "properties": {
                    "text": "VERÃO 2024",
                    "fontSize": 80,
                    "fontFamily": "Anton",
                    "fontWeight": "normal",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ffffff",
                    "lineHeight": 1,
                    "textShadow": {
                        "color": "rgba(0,0,0,0.2)",
                        "blur": 10,
                        "offsetX": 4,
                        "offsetY": 4
                    }
                }
            },
            {
                "id": "txt-2",
                "type": "text",
                "x": 540,
                "y": 520,
                "width": 600,
                "height": 100,
                "rotation": 0,
                "zIndex": 4,
                "properties": {
                    "text": "Coleção Nova\nAté 70% OFF",
                    "fontSize": 42,
                    "fontFamily": "Poppins",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ffffff",
                    "lineHeight": 1.3
                }
            },
            {
                "id": "shape-3",
                "type": "rectangle",
                "x": 540,
                "y": 700,
                "width": 280,
                "height": 70,
                "rotation": 0,
                "zIndex": 5,
                "properties": {
                    "fillColor": "#ffffff",
                    "borderWidth": 0,
                    "borderColor": "transparent",
                    "borderRadius": 35
                }
            },
            {
                "id": "txt-3",
                "type": "text",
                "x": 540,
                "y": 720,
                "width": 260,
                "height": 40,
                "rotation": 0,
                "zIndex": 6,
                "properties": {
                    "text": "COMPRAR AGORA",
                    "fontSize": 20,
                    "fontFamily": "Poppins",
                    "fontWeight": "bold",
                    "fontStyle": "normal",
                    "textAlign": "center",
                    "color": "#ff6b6b",
                    "lineHeight": 1
                }
            }
        ]
    }',
    NULL,
    0,
    5
);

-- ============================================================
-- FIM DO SCRIPT
-- ============================================================
