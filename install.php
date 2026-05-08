<?php
/**
 * FlyerSaaS - Instalador do Sistema
 * 
 * Este arquivo deve ser executado apenas na primeira instalação.
 * Após a instalação concluída, este arquivo pode ser removido por segurança.
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Desabilitar verificação de instalação durante o próprio install
define('SKIP_INSTALL_CHECK', true);

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'test_db') {
            // Passo 1: Testar conexão com o banco
            $host = trim($_POST['db_host'] ?? 'localhost');
            $dbname = trim($_POST['db_name'] ?? '');
            $username = trim($_POST['db_user'] ?? '');
            $password = $_POST['db_pass'] ?? '';
            
            if (empty($dbname) || empty($username)) {
                throw new Exception("Preencha todos os campos do banco de dados.");
            }
            
            // Tentar conectar SEM selecionar o banco primeiro (para criar se necessário)
            $dsn = "mysql:host=$host;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Verificar se o banco existe, se não, tentar criar
            $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
            if ($stmt->rowCount() === 0) {
                // Banco não existe, tentar criar
                try {
                    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $success = "Banco de dados '$dbname' criado com sucesso!";
                } catch (PDOException $e) {
                    // Se não conseguir criar, assumir que já existe ou usar mesmo assim
                    // Alguns hosts não permitem CREATE DATABASE via SQL
                    if (strpos($e->getMessage(), 'access denied') !== false) {
                        $success = "Conexão estabelecida. Certifique-se de que o banco '$dbname' foi criado no painel da hospedagem.";
                    } else {
                        throw $e;
                    }
                }
            } else {
                $success = "Conexão com o banco '$dbname' estabelecida com sucesso!";
            }
            
            // Salvar dados na sessão para próximo passo
            $_SESSION['install_data'] = [
                'db_host' => $host,
                'db_name' => $dbname,
                'db_user' => $username,
                'db_pass' => $password
            ];
            
            $step = 2;
            
        } elseif ($_POST['action'] === 'set_url') {
            // Passo 2: Definir URL base
            if (empty($_POST['base_url'])) {
                throw new Exception("A URL base é obrigatória.");
            }
            
            $base_url = rtrim(trim($_POST['base_url']), '/');
            
            // Validar URL
            if (!filter_var($base_url, FILTER_VALIDATE_URL)) {
                throw new Exception("URL inválida. Exemplo: https://seusite.com");
            }
            
            $_SESSION['install_data']['base_url'] = $base_url;
            $step = 3;
            
        } elseif ($_POST['action'] === 'install') {
            // Passo 3: Instalação final
            if (empty($_SESSION['install_data'])) {
                throw new Exception("Dados de instalação não encontrados. Reinicie o processo.");
            }
            
            $data = $_SESSION['install_data'];
            $admin_name = trim($_POST['admin_name'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_pass = $_POST['admin_pass'] ?? '';
            
            if (empty($admin_name) || empty($admin_email) || empty($admin_pass)) {
                throw new Exception("Preencha todos os dados do administrador.");
            }
            
            if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("E-mail do administrador inválido.");
            }
            
            if (strlen($admin_pass) < 6) {
                throw new Exception("A senha deve ter pelo menos 6 caracteres.");
            }
            
            // Conectar ao banco com os dados salvos
            $dsn = "mysql:host={$data['db_host']};dbname={$data['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Criar todas as tabelas (usando CREATE TABLE IF NOT EXISTS)
            $tables_sql = "
                -- Tabela de migrações
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de usuários
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    store_name VARCHAR(150),
                    phone VARCHAR(20),
                    cnpj VARCHAR(20),
                    is_admin TINYINT(1) DEFAULT 0,
                    status TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de planos
                CREATE TABLE IF NOT EXISTS plans (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    duration_days INT DEFAULT 30,
                    images_limit INT DEFAULT 100,
                    flyers_limit INT DEFAULT 5,
                    description TEXT,
                    status TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de assinaturas dos usuários
                CREATE TABLE IF NOT EXISTS user_subscriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    plan_id INT NOT NULL,
                    start_date DATE NOT NULL,
                    end_date DATE NOT NULL,
                    images_used INT DEFAULT 0,
                    flyers_used INT DEFAULT 0,
                    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de galeria de imagens
                CREATE TABLE IF NOT EXISTS gallery_images (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    original_name VARCHAR(255) NOT NULL,
                    stored_name VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    mime_type VARCHAR(50),
                    size INT,
                    hash VARCHAR(32) NOT NULL,
                    category VARCHAR(50),
                    uploaded_by INT,
                    views_count INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
                    UNIQUE KEY unique_hash (hash)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de encartes
                CREATE TABLE IF NOT EXISTS flyers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(150) NOT NULL,
                    description TEXT,
                    status ENUM('draft', 'published') DEFAULT 'draft',
                    pdf_path VARCHAR(500),
                    html_content LONGTEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de itens do encarte
                CREATE TABLE IF NOT EXISTS flyer_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    flyer_id INT NOT NULL,
                    product_name VARCHAR(150) NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    description TEXT,
                    image_id INT,
                    position_order INT DEFAULT 0,
                    FOREIGN KEY (flyer_id) REFERENCES flyers(id) ON DELETE CASCADE,
                    FOREIGN KEY (image_id) REFERENCES gallery_images(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                
                -- Tabela de configurações do sistema
                CREATE TABLE IF NOT EXISTS system_config (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    config_key VARCHAR(50) NOT NULL UNIQUE,
                    config_value TEXT,
                    description VARCHAR(255)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            // Executar criação de tabelas
            $statements = array_filter(array_map('trim', explode(';', $tables_sql)));
            foreach ($statements as $sql) {
                if (!empty($sql)) {
                    $pdo->exec($sql);
                }
            }
            
            // Registrar migração inicial
            $pdo->exec("INSERT IGNORE INTO migrations (migration) VALUES ('001_initial_install')");
            
            // Criar administrador
            $hashed_password = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, is_admin, status) 
                VALUES (?, ?, ?, 1, 1)
                ON DUPLICATE KEY UPDATE name=VALUES(name)
            ");
            $stmt->execute([$admin_name, $admin_email, $hashed_password]);
            
            // Criar plano básico padrão
            $pdo->exec("
                INSERT INTO plans (name, price, duration_days, images_limit, flyers_limit, description, status)
                VALUES ('Básico', 29.90, 30, 100, 5, 'Plano inicial para pequenos mercados', 1)
                ON DUPLICATE KEY UPDATE name=VALUES(name)
            ");
            
            // Criar configurações padrão
            $configs = [
                ['site_name', 'FlyerSaaS', 'Nome do site'],
                ['site_email', $admin_email, 'E-mail de contato'],
                ['maintenance_mode', '0', 'Modo de manutenção (0=desligado, 1=ligado)']
            ];
            
            $stmt_config = $pdo->prepare("
                INSERT INTO system_config (config_key, config_value, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)
            ");
            
            foreach ($configs as $config) {
                $stmt_config->execute($config);
            }
            
            // Criar arquivo .env
            $env_content = "<?php\n";
            $env_content .= "// Configurações do Banco de Dados\n";
            $env_content .= "define('DB_HOST', '" . addslashes($data['db_host']) . "');\n";
            $env_content .= "define('DB_NAME', '" . addslashes($data['db_name']) . "');\n";
            $env_content .= "define('DB_USER', '" . addslashes($data['db_user']) . "');\n";
            $env_content .= "define('DB_PASS', '" . addslashes($data['db_pass']) . "');\n";
            $env_content .= "\n";
            $env_content .= "// Configurações do Sistema\n";
            $env_content .= "define('BASE_URL', '" . addslashes($data['base_url']) . "');\n";
            $env_content .= "define('INSTALLED', true);\n";
            $env_content .= "\n";
            $env_content .= "// Segurança\n";
            $env_content .= "define('SITE_KEY', '" . bin2hex(random_bytes(32)) . "');\n";
            
            $env_file = __DIR__ . '/.env.php';
            if (file_put_contents($env_file, $env_content)) {
                chmod($env_file, 0444); // Somente leitura
            }
            
            // Limpar sessão de instalação
            unset($_SESSION['install_data']);
            
            $success = "Instalação concluída com sucesso! Redirecionando...";
            $step = 4;
            
            // Criar diretórios necessários
            $dirs = [
                __DIR__ . '/assets/uploads/images',
                __DIR__ . '/assets/uploads/flyers'
            ];
            
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                // Criar .htaccess para proteger uploads
                $htaccess = $dir . '/.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "RemoveHandler .php\nAddType text/html .html\n");
                }
            }
            
        }
    } catch (Exception $e) {
        $error = "Erro: " . $e->getMessage();
        if ($e->getPrevious()) {
            $error .= "<br>Detalhe: " . $e->getPrevious()->getMessage();
        }
    }
}

// Detectar URL base automaticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$detected_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$detected_url = rtrim($detected_url, '/');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding-top: 50px; }
        .installer-box { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { width: 30%; text-align: center; padding: 10px; background: #e9ecef; border-radius: 5px; }
        .step.active { background: #0d6efd; color: white; }
        .step.completed { background: #198754; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="installer-box">
            <h2 class="text-center mb-4">🚀 Instalação do FlyerSaaS</h2>
            
            <!-- Indicador de Passos -->
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?>">1. Banco de Dados</div>
                <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">2. URL Base</div>
                <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">3. Administrador</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= nl2br(htmlspecialchars($error)) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= nl2br(htmlspecialchars($success)) ?></div>
            <?php endif; ?>
            
            <!-- Passo 1: Banco de Dados -->
            <?php if ($step === 1): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="test_db">
                    <div class="mb-3">
                        <label class="form-label">Host do Banco de Dados</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                        <small class="text-muted">Geralmente "localhost" na Hostinger</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome do Banco de Dados</label>
                        <input type="text" name="db_name" class="form-control" placeholder="ex: u624766619_encartes" required>
                        <small class="text-muted">Crie o banco no painel da hospedagem antes</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usuário do Banco</label>
                        <input type="text" name="db_user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha do Banco</label>
                        <input type="password" name="db_pass" class="form-control">
                        <small class="text-muted">Deixe em branco se não tiver senha</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Testar Conexão e Continuar</button>
                </form>
                
            <!-- Passo 2: URL Base -->
            <?php elseif ($step === 2): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="set_url">
                    <div class="mb-3">
                        <label class="form-label">URL Base do Sistema</label>
                        <input type="url" name="base_url" class="form-control" value="<?= htmlspecialchars($detected_url) ?>" required>
                        <small class="text-muted">Ex: https://seusite.com ou http://localhost/flyersaas</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Continuar</button>
                    <a href="?step=1" class="btn btn-link w-100 mt-2">Voltar</a>
                </form>
                
            <!-- Passo 3: Administrador -->
            <?php elseif ($step === 3): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="install">
                    <div class="mb-3">
                        <label class="form-label">Nome do Administrador</label>
                        <input type="text" name="admin_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="admin_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="admin_pass" class="form-control" minlength="6" required>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="alert alert-info">
                        <strong>Importante:</strong> O banco de dados deve existir no painel da hospedagem.<br>
                        O instalador criará todas as tabelas automaticamente.
                    </div>
                    <button type="submit" class="btn btn-success w-100">Finalizar Instalação</button>
                    <a href="?step=2" class="btn btn-link w-100 mt-2">Voltar</a>
                </form>
                
            <!-- Passo 4: Conclusão -->
            <?php elseif ($step === 4): ?>
                <div class="text-center">
                    <div class="mb-4">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h4>Instalação Concluída!</h4>
                    <p class="text-muted">O sistema está pronto para uso.</p>
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary">Acessar Sistema</a>
                        <a href="admin/index.php" class="btn btn-secondary">Painel Admin</a>
                    </div>
                    <div class="alert alert-warning mt-4">
                        <strong>Atenção:</strong> Por segurança, remova ou renomeie o arquivo <code>install.php</code> após a instalação.
                    </div>
                </div>
                <script>
                    setTimeout(() => { window.location.href = 'index.php'; }, 3000);
                </script>
            <?php endif; ?>
            
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
