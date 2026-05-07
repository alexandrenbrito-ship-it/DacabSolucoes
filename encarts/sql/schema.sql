-- ============================================================
-- /sql/schema.sql
-- Script de criação do banco de dados Encarts Digital System
-- Compatível com MySQL 5.7+ e MariaDB 10.2+
-- Charset: utf8mb4 (suporte completo a emojis e caracteres especiais)
-- ============================================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS `encarts_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `encarts_db`;

-- ============================================================
-- TABELA: users
-- Armazena os usuários do sistema com informações de conta e plano
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL COMMENT 'Nome completo do usuário',
    `email` VARCHAR(255) NOT NULL UNIQUE COMMENT 'E-mail único para login',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Hash da senha (bcrypt)',
    `plan` ENUM('free', 'pro') DEFAULT 'free' COMMENT 'Tipo de plano: free ou pro',
    `avatar_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL da imagem de perfil',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação da conta',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',
    `last_login` TIMESTAMP NULL DEFAULT NULL COMMENT 'Último login realizado',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Conta ativa (1) ou desativada (0)',
    
    INDEX `idx_email` (`email`),
    INDEX `idx_plan` (`plan`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de usuários do sistema';

-- ============================================================
-- TABELA: sessions
-- Gerencia sessões ativas dos usuários com tokens seguros
-- ============================================================
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário dono da sessão',
    `token` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Token de sessão criptograficamente seguro',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP do cliente (IPv4 ou IPv6)',
    `user_agent` TEXT COMMENT 'User agent do navegador',
    `expires_at` TIMESTAMP NOT NULL COMMENT 'Data de expiração da sessão',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação da sessão',
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de sessões ativas';

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
-- TABELA: user_uploads
-- Registro de arquivos enviados pelos usuários
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_uploads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'ID do usuário que fez o upload',
    `filename` VARCHAR(255) NOT NULL COMMENT 'Nome do arquivo no servidor (hash)',
    `original_name` VARCHAR(255) NOT NULL COMMENT 'Nome original do arquivo',
    `file_size` INT UNSIGNED NOT NULL COMMENT 'Tamanho em bytes',
    `mime_type` VARCHAR(100) NOT NULL COMMENT 'Tipo MIME real do arquivo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data do upload',
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de uploads de imagens dos usuários';

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

-- Usuários de teste (senhas: admin123, user123, pro123)
-- Hash gerado com password_hash('senha', PASSWORD_BCRYPT)
INSERT INTO `users` (`name`, `email`, `password_hash`, `plan`, `is_active`) VALUES
('Administrador', 'admin@encarts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pro', 1),
('Usuário Free', 'free@encarts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'free', 1),
('Usuário Pro', 'pro@encarts.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pro', 1);

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
