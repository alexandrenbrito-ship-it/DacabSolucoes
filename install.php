<?php
/**
 * Página de Instalação do FlyerSaaS
 * Guia o usuário através da configuração inicial do sistema
 */

// Inicia sessão
session_start();

// Define caminho base
define('BASE_PATH', __DIR__);

// Carrega funções auxiliares
require_once BASE_PATH . '/includes/functions.php';

// Se já estiver instalado, redireciona para login
if (isSystemInstalled()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$step = $_GET['step'] ?? 1;
$dbConnected = false;

// Processa formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Passo 1: Dados do Banco
    if ($action === 'test_db') {
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            $errors[] = "Preencha todos os campos do banco de dados.";
        } else {
            // Testa conexão
            try {
                $dsn = "mysql:host=$dbHost;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                
                // Verifica/cria banco
                $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
                
                // Testa conexão com o banco específico
                $dsnWithDb = "$dsn;dbname=$dbName";
                $pdoTest = new PDO($dsnWithDb, $dbUser, $dbPass, $options);
                
                $_SESSION['install_db'] = [
                    'host' => $dbHost,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass
                ];
                
                $dbConnected = true;
                $step = 2;
                
            } catch (PDOException $e) {
                $errors[] = "Erro ao conectar: " . $e->getMessage();
            }
        }
    }
    
    // Passo 2: URL Base
    if ($action === 'save_url') {
        if (!isset($_SESSION['install_db'])) {
            $errors[] = "Dados do banco não encontrados. Volte ao início.";
            $step = 1;
        } else {
            $baseUrl = trim($_POST['base_url'] ?? '');
            
            if (empty($baseUrl)) {
                // Detecta automaticamente
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $path = dirname($_SERVER['SCRIPT_NAME']);
                $baseUrl = $protocol . '://' . $host . rtrim($path, '/');
            }
            
            $_SESSION['install_base_url'] = $baseUrl;
            $step = 3;
        }
    }
    
    // Passo 3: Admin e Instalação Final
    if ($action === 'install') {
        if (!isset($_SESSION['install_db']) || !isset($_SESSION['install_base_url'])) {
            $errors[] = "Dados de instalação incompletos. Volte ao início.";
            $step = 1;
        } else {
            $adminName = trim($_POST['admin_name'] ?? '');
            $adminEmail = trim($_POST['admin_email'] ?? '');
            $adminPassword = $_POST['admin_password'] ?? '';
            $adminConfirm = $_POST['admin_confirm'] ?? '';
            
            if (empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
                $errors[] = "Preencha todos os dados do administrador.";
            } elseif ($adminPassword !== $adminConfirm) {
                $errors[] = "As senhas do administrador não coincidem.";
            } elseif (strlen($adminPassword) < 6) {
                $errors[] = "A senha deve ter pelo menos 6 caracteres.";
            } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "E-mail do administrador inválido.";
            } else {
                // Executa instalação
                try {
                    $dbConfig = $_SESSION['install_db'];
                    $baseUrl = $_SESSION['install_base_url'];
                    
                    // Conecta ao banco
                    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ];
                    $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
                    
                    // Cria todas as tabelas
                    createTables($conn);
                    
                    // Cria arquivo .env.php
                    $envContent = "<?php\nreturn [\n    'DB_HOST' => '" . addslashes($dbConfig['host']) . "',\n    'DB_NAME' => '" . addslashes($dbConfig['name']) . "',\n    'DB_USER' => '" . addslashes($dbConfig['user']) . "',\n    'DB_PASS' => '" . addslashes($dbConfig['pass']) . "',\n    'DB_CHARSET' => 'utf8mb4',\n    'BASE_URL' => '" . addslashes($baseUrl) . "',\n];\n";
                    
                    $envFile = BASE_PATH . '/.env.php';
                    if (file_put_contents($envFile, $envContent) === false) {
                        throw new Exception("Não foi possível criar o arquivo .env.php. Verifique as permissões da pasta.");
                    }
                    @chmod($envFile, 0444);
                    
                    // Cria arquivo de lock
                    $lockFile = BASE_PATH . '/installed.lock';
                    file_put_contents($lockFile, date('Y-m-d H:i:s'));
                    @chmod($lockFile, 0444);
                    
                    // Cria administrador
                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        INSERT INTO users (name, email, password, is_admin, status, created_at) 
                        VALUES (?, ?, ?, 1, 'active', NOW())
                        ON DUPLICATE KEY UPDATE name=VALUES(name)
                    ");
                    $stmt->execute([$adminName, $adminEmail, $hashedPassword]);
                    
                    // Limpa sessão de instalação
                    unset($_SESSION['install_db'], $_SESSION['install_base_url']);
                    
                    $step = 4; // Conclusão
                    
                } catch (Exception $e) {
                    $errors[] = "Erro na instalação: " . $e->getMessage();
                }
            }
        }
    }
}

/**
 * Cria todas as tabelas do banco de dados
 */
function createTables($conn) {
    $tables = [
        // Tabela migrations
        "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela users
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            store_name VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(50) DEFAULT NULL,
            cnpj VARCHAR(20) DEFAULT NULL,
            is_admin TINYINT(1) DEFAULT 0,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela plans
        "CREATE TABLE IF NOT EXISTS plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            duration_days INT NOT NULL DEFAULT 30,
            images_limit INT NOT NULL DEFAULT 100,
            flyers_limit INT NOT NULL DEFAULT 5,
            description TEXT DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela user_subscriptions
        "CREATE TABLE IF NOT EXISTS user_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            images_used INT NOT NULL DEFAULT 0,
            flyers_used INT NOT NULL DEFAULT 0,
            status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
            INDEX idx_user_status (user_id, status),
            INDEX idx_end_date (end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela gallery_images
        "CREATE TABLE IF NOT EXISTS gallery_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            size INT NOT NULL DEFAULT 0,
            hash VARCHAR(32) NOT NULL,
            category VARCHAR(100) DEFAULT NULL,
            uploaded_by INT NOT NULL,
            views_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_hash (hash),
            INDEX idx_category (category),
            INDEX idx_uploaded_by (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela flyers
        "CREATE TABLE IF NOT EXISTS flyers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            pdf_path VARCHAR(500) DEFAULT NULL,
            html_content LONGTEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela flyer_items
        "CREATE TABLE IF NOT EXISTS flyer_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            flyer_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT DEFAULT NULL,
            image_id INT DEFAULT NULL,
            position_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (flyer_id) REFERENCES flyers(id) ON DELETE CASCADE,
            FOREIGN KEY (image_id) REFERENCES gallery_images(id) ON DELETE SET NULL,
            INDEX idx_flyer (flyer_id),
            INDEX idx_position (flyer_id, position_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // Tabela system_config
        "CREATE TABLE IF NOT EXISTS system_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(100) NOT NULL UNIQUE,
            config_value TEXT DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $sql) {
        $conn->exec($sql);
    }
    
    // Insere planos padrão se não existirem
    $conn->exec("
        INSERT IGNORE INTO plans (name, price, duration_days, images_limit, flyers_limit, description, status) 
        VALUES 
        ('Básico', 29.90, 30, 100, 5, 'Plano ideal para pequenos mercados', 'active'),
        ('Profissional', 59.90, 30, 500, 15, 'Para mercados em crescimento', 'active'),
        ('Enterprise', 99.90, 30, 2000, 50, 'Para redes de supermercados', 'active')
    ");
    
    // Insere configurações padrão
    $conn->exec("
        INSERT IGNORE INTO system_config (config_key, config_value, description) 
        VALUES 
        ('site_name', 'FlyerSaaS', 'Nome do sistema'),
        ('site_logo', '', 'URL do logo'),
        ('contact_email', '', 'E-mail de contato'),
        ('maintenance_mode', '0', 'Modo de manutenção (0=desligado, 1=ligado)')
    ");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-card { max-width: 600px; margin: 50px auto; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { width: 30%; text-align: center; padding: 10px; border-radius: 8px; background: rgba(255,255,255,0.2); color: white; }
        .step.active { background: white; color: #667eea; font-weight: bold; }
        .step.completed { background: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <div class="card shadow-lg">
                <div class="card-header bg-white text-center py-4">
                    <h2 class="mb-0 text-primary">🚀 Instalação do FlyerSaaS</h2>
                    <p class="text-muted mb-0">Configure seu sistema em 3 passos simples</p>
                </div>
                
                <div class="card-body p-4">
                    <!-- Indicador de Passos -->
                    <div class="step-indicator">
                        <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                            1. Banco de Dados
                        </div>
                        <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                            2. URL do Sistema
                        </div>
                        <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                            3. Administrador
                        </div>
                    </div>
                    
                    <!-- Mensagens de Erro -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= sanitize($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Passo 1: Banco de Dados -->
                    <?php if ($step == 1): ?>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="test_db">
                            
                            <div class="mb-3">
                                <label class="form-label">Host do MySQL *</label>
                                <input type="text" class="form-control" name="db_host" value="localhost" required>
                                <small class="text-muted">Geralmente "localhost" ou endereço do servidor</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nome do Banco de Dados *</label>
                                <input type="text" class="form-control" name="db_name" required>
                                <small class="text-muted">Crie o banco no painel da hospedagem antes</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Usuário do MySQL *</label>
                                <input type="text" class="form-control" name="db_user" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Senha do MySQL</label>
                                <input type="password" class="form-control" name="db_pass">
                                <small class="text-muted">Deixe em branco se não tiver senha</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                Testar Conexão e Continuar →
                            </button>
                        </form>
                        
                    <!-- Passo 2: URL Base -->
                    <?php elseif ($step == 2): ?>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="save_url">
                            
                            <div class="alert alert-success">
                                ✅ Conexão com banco de dados estabelecida com sucesso!
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">URL Base do Sistema</label>
                                <?php
                                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $path = dirname($_SERVER['SCRIPT_NAME']);
                                $detectedUrl = $protocol . '://' . $host . rtrim($path, '/');
                                ?>
                                <input type="url" class="form-control" name="base_url" value="<?= $detectedUrl ?>">
                                <small class="text-muted">URL completa onde o sistema estará acessível</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    Continuar →
                                </button>
                                <a href="?step=1" class="btn btn-outline-secondary">Voltar</a>
                            </div>
                        </form>
                        
                    <!-- Passo 3: Criar Administrador -->
                    <?php elseif ($step == 3): ?>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="install">
                            
                            <div class="alert alert-info">
                                <strong>Último passo!</strong> Crie a conta do administrador principal.
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" name="admin_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">E-mail *</label>
                                <input type="email" class="form-control" name="admin_email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Senha *</label>
                                <input type="password" class="form-control" name="admin_password" minlength="6" required>
                                <small class="text-muted">Mínimo de 6 caracteres</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirmar Senha *</label>
                                <input type="password" class="form-control" name="admin_confirm" required>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success flex-grow-1">
                                    🎉 Instalar Sistema
                                </button>
                                <a href="?step=2" class="btn btn-outline-secondary">Voltar</a>
                            </div>
                        </form>
                        
                    <!-- Passo 4: Conclusão -->
                    <?php elseif ($step == 4): ?>
                        <div class="text-center py-4">
                            <div class="display-1 mb-3">🎉</div>
                            <h3 class="text-success mb-3">Instalação Concluída!</h3>
                            <p class="text-muted mb-4">
                                Seu sistema FlyerSaaS está pronto para uso.<br>
                                Use o e-mail e senha do administrador para acessar.
                            </p>
                            
                            <div class="d-grid gap-2 d-md-block">
                                <a href="login.php" class="btn btn-primary btn-lg">
                                    Ir para Login →
                                </a>
                            </div>
                            
                            <div class="alert alert-warning mt-4 text-start">
                                <strong>⚠️ Importante:</strong><br>
                                Por segurança, você pode remover o arquivo <code>install.php</code> após confirmar que tudo está funcionando.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center text-white mt-3">
                <small>FlyerSaaS v1.0 - Sistema de Encartes Digitais</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
