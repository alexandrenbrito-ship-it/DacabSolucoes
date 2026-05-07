<?php
/**
 * Classe de Migração do Banco de Dados
 * Responsável por criar e atualizar tabelas
 */
class Migration {
    private $db;
    private $migrationsPath;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->migrationsPath = ROOT_PATH . '/database/migrations';
        $this->ensureMigrationsTable();
    }

    /**
     * Garantir que a tabela de migrações existe
     */
    private function ensureMigrationsTable() {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Obter lista de migrações já executadas
     */
    public function getExecutedMigrations() {
        $stmt = $this->db->query("SELECT migration_name FROM migrations ORDER BY id ASC");
        return array_column($stmt->fetchAll(), 'migration_name');
    }

    /**
     * Obter lista de arquivos de migração disponíveis
     */
    public function getAvailableMigrations() {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d+_[a-z_]+\.(sql|php)$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Obter migrações pendentes
     */
    public function getPendingMigrations() {
        $executed = $this->getExecutedMigrations();
        $available = $this->getAvailableMigrations();

        return array_diff($available, $executed);
    }

    /**
     * Executar todas as migrações pendentes
     */
    public function runPendingMigrations() {
        $pending = $this->getPendingMigrations();
        $results = [];

        if (empty($pending)) {
            return ['success' => true, 'message' => 'Nenhuma migração pendente', 'executed' => []];
        }

        try {
            $this->db->beginTransaction();

            foreach ($pending as $migration) {
                $result = $this->executeMigration($migration);
                $results[] = $result;

                if (!$result['success']) {
                    throw new Exception("Erro na migração {$migration}: " . $result['error']);
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Todas as migrações executadas com sucesso', 'executed' => $results];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Erro ao executar migrações', 'error' => $e->getMessage(), 'executed' => $results];
        }
    }

    /**
     * Executar uma migração específica
     */
    public function executeMigration($migrationName) {
        $filePath = $this->migrationsPath . '/' . $migrationName;

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'Arquivo não encontrado', 'migration' => $migrationName];
        }

        try {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            if ($extension === 'sql') {
                $sql = file_get_contents($filePath);
                // Executar múltiplas statements
                $this->db->exec($sql);
            } elseif ($extension === 'php') {
                $callback = require $filePath;
                if (is_callable($callback)) {
                    $callback($this->db);
                }
            }

            // Registrar migração como executada
            $stmt = $this->db->prepare("INSERT INTO migrations (migration_name) VALUES (?)");
            $stmt->execute([$migrationName]);

            return ['success' => true, 'migration' => $migrationName];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'migration' => $migrationName];
        }
    }

    /**
     * Executar todas as migrações (para instalação inicial)
     */
    public function runAll() {
        return $this->runPendingMigrations();
    }

    /**
     * Verificar se o banco está atualizado
     */
    public function isUpToDate() {
        return empty($this->getPendingMigrations());
    }

    /**
     * Criar todas as tabelas necessárias para instalação inicial
     */
    public function createInitialTables() {
        $sql = "
            -- Tabela de usuários
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                store_name VARCHAR(100),
                phone VARCHAR(20),
                cnpj VARCHAR(20),
                is_admin TINYINT(1) DEFAULT 0,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Tabela de planos
            CREATE TABLE IF NOT EXISTS plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                duration_days INT NOT NULL DEFAULT 30,
                images_limit INT NOT NULL DEFAULT 10,
                flyers_limit INT NOT NULL DEFAULT 2,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Tabela de assinaturas dos usuários
            CREATE TABLE IF NOT EXISTS user_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                plan_id INT NOT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                images_used INT NOT NULL DEFAULT 0,
                flyers_used INT NOT NULL DEFAULT 0,
                status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_end_date (end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Tabela de galeria de imagens
            CREATE TABLE IF NOT EXISTS gallery_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                original_name VARCHAR(255) NOT NULL,
                stored_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size INT NOT NULL,
                hash VARCHAR(64) NOT NULL,
                category VARCHAR(50),
                uploaded_by INT NOT NULL,
                views_count INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_hash (hash),
                INDEX idx_uploaded_by (uploaded_by),
                INDEX idx_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Tabela de encartes
            CREATE TABLE IF NOT EXISTS flyers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(100) NOT NULL,
                description TEXT,
                status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
                pdf_path VARCHAR(500),
                html_content LONGTEXT,
                template VARCHAR(50) DEFAULT 'default',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Tabela de itens do encarte
            CREATE TABLE IF NOT EXISTS flyer_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                flyer_id INT NOT NULL,
                product_name VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                description TEXT,
                image_id INT,
                position_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (flyer_id) REFERENCES flyers(id) ON DELETE CASCADE,
                FOREIGN KEY (image_id) REFERENCES gallery_images(id) ON DELETE SET NULL,
                INDEX idx_flyer_id (flyer_id),
                INDEX idx_position (position_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            -- Tabela de configurações do sistema
            CREATE TABLE IF NOT EXISTS system_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                config_key VARCHAR(100) NOT NULL UNIQUE,
                config_value TEXT,
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key (config_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        try {
            $this->db->exec($sql);
            return ['success' => true, 'message' => 'Tabelas criadas com sucesso'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Inserir dados iniciais (seeders)
     */
    public function seedInitialData() {
        try {
            // Inserir planos padrão
            $plans = [
                ['Básico', 'Plano ideal para pequenos mercados', 29.90, 30, 50, 2],
                ['Profissional', 'Para mercados em crescimento', 59.90, 30, 200, 5],
                ['Enterprise', 'Solução completa para grandes redes', 149.90, 30, 1000, 20]
            ];

            $stmt = $this->db->prepare("
                INSERT INTO plans (name, description, price, duration_days, images_limit, flyers_limit, status)
                VALUES (?, ?, ?, ?, ?, ?, 'active')
                ON DUPLICATE KEY UPDATE name = VALUES(name)
            ");

            foreach ($plans as $plan) {
                $stmt->execute($plan);
            }

            // Inserir configurações padrão
            $configs = [
                ['site_name', 'FlyerSaaS', 'Nome do sistema'],
                ['site_email', 'contato@flyersaas.com', 'E-mail de contato'],
                ['maintenance_mode', '0', 'Modo de manutenção (0=desligado, 1=ligado)'],
                ['max_upload_size', '5242880', 'Tamanho máximo de upload em bytes (5MB)']
            ];

            $stmt = $this->db->prepare("
                INSERT INTO system_config (config_key, config_value, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");

            foreach ($configs as $config) {
                $stmt->execute($config);
            }

            return ['success' => true, 'message' => 'Dados iniciais inseridos'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
