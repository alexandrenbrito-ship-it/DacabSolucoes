<?php
/**
 * EncartePro - Instalador do Sistema
 * Wizard de 2 etapas para configuração inicial
 */

// Define BASE_PATH antes de incluir config
define('BASE_PATH', dirname(__DIR__));

// Inicia sessão
session_start();

// Verifica se já está instalado
if (file_exists(BASE_PATH . '/.env')) {
    die('O sistema já está instalado. Se deseja reinstalar, exclua o arquivo .env');
}

$errors = [];
$success = '';
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

/**
 * Etapa 1: Configuração do Banco de Dados
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    // Coleta dados do formulário
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = trim($_POST['db_pass'] ?? '');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $siteUrl = trim($_POST['site_url'] ?? '');
    $siteName = trim($_POST['site_name'] ?? 'EncartePro');
    $mailFrom = trim($_POST['mail_from'] ?? '');
    $mpToken = trim($_POST['mp_token'] ?? '');
    $mpPublicKey = trim($_POST['mp_public_key'] ?? '');
    
    // Validações básicas
    if (empty($dbName)) $errors[] = 'Nome do banco de dados é obrigatório';
    if (empty($dbUser)) $errors[] = 'Usuário do banco de dados é obrigatório';
    if (empty($siteUrl)) $errors[] = 'URL do site é obrigatória';
    
    if (empty($errors)) {
        // Testa conexão com o banco de dados
        try {
            $dsn = "mysql:host=" . $dbHost . ";port=" . $dbPort . ";charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Cria o banco de dados se não existir
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $dbName . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . $dbName . "`");
            
            // Cria as tabelas
            createTables($pdo);
            
            // Salva dados na sessão para próxima etapa
            $_SESSION['install_data'] = [
                'db_host' => $dbHost,
                'db_port' => $dbPort,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'site_url' => $siteUrl,
                'site_name' => $siteName,
                'mail_from' => $mailFrom,
                'mp_token' => $mpToken,
                'mp_public_key' => $mpPublicKey
            ];
            
            $step = 2;
            
        } catch (PDOException $e) {
            $errors[] = 'Erro ao conectar com o banco de dados: ' . $e->getMessage();
        }
    }
}

/**
 * Etapa 2: Criar conta Admin
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    // Recupera dados da sessão
    $installData = $_SESSION['install_data'] ?? [];
    
    if (empty($installData)) {
        $errors[] = 'Sessão expirada. Reinicie a instalação.';
        $step = 1;
    } else {
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = trim($_POST['admin_password'] ?? '');
        $adminPasswordConfirm = trim($_POST['admin_password_confirm'] ?? '');
        
        // Validações
        if (empty($adminName)) $errors[] = 'Nome é obrigatório';
        if (empty($adminEmail)) $errors[] = 'Email é obrigatório';
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido';
        if (empty($adminPassword)) $errors[] = 'Senha é obrigatória';
        if (strlen($adminPassword) < 6) $errors[] = 'Senha deve ter no mínimo 6 caracteres';
        if ($adminPassword !== $adminPasswordConfirm) $errors[] = 'Senhas não conferem';
        
        if (empty($errors)) {
            try {
                // Conecta ao banco de dados
                $dsn = "mysql:host=" . $installData['db_host'] . 
                       ";port=" . $installData['db_port'] . 
                       ";dbname=" . $installData['db_name'] . 
                       ";charset=utf8mb4";
                $pdo = new PDO($dsn, $installData['db_user'], $installData['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // Cria usuário admin
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                $verificationToken = bin2hex(random_bytes(32));
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, status, email_verified, verification_token, created_at)
                    VALUES (:name, :email, :password, 'admin', 'active', 1, :token, NOW())
                ");
                
                $stmt->execute([
                    ':name' => $adminName,
                    ':email' => $adminEmail,
                    ':password' => $hashedPassword,
                    ':token' => $verificationToken
                ]);
                
                // Cria arquivo .env
                createEnvFile($installData);
                
                // Limpa sessão de instalação
                unset($_SESSION['install_data']);
                
                $success = true;
                
            } catch (PDOException $e) {
                $errors[] = 'Erro ao criar administrador: ' . $e->getMessage();
            }
        }
    }
}

/**
 * Cria todas as tabelas do banco de dados
 */
function createTables($pdo) {
    // Tabela users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            email_verified TINYINT DEFAULT 0,
            verification_token VARCHAR(100),
            reset_token VARCHAR(100),
            reset_expires DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tabela plans
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            billing_cycle ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',
            features JSON,
            max_encartes INT DEFAULT 0,
            mp_plan_id VARCHAR(100),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_billing_cycle (billing_cycle)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tabela subscriptions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            status ENUM('active', 'expired', 'cancelled', 'trial') DEFAULT 'trial',
            starts_at DATE,
            expires_at DATE,
            mp_payment_id VARCHAR(100),
            mp_subscription_id VARCHAR(100),
            payment_ref VARCHAR(255),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_plan_id (plan_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tabela encartes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS encartes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            template_id VARCHAR(50) NOT NULL,
            data JSON,
            preview_url VARCHAR(255),
            pdf_url VARCHAR(255),
            status ENUM('draft', 'published') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tabela settings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_name VARCHAR(100) NOT NULL UNIQUE,
            value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (key_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insere planos padrão
    $pdo->exec("
        INSERT INTO plans (name, description, price, billing_cycle, features, max_encartes, status) VALUES
        ('Starter', 'Perfeito para pequenos negócios', 29.90, 'monthly', 
         '[\"10 encartes por mês\", \"Templates básicos\", \"Suporte por email\", \"Exportação em PDF\"]', 10, 'active'),
        ('Pro', 'Ideal para negócios em crescimento', 59.90, 'monthly',
         '[\"50 encartes por mês\", \"Todos os templates\", \"Suporte prioritário\", \"Exportação em PDF\", \"Sem marca d\\'água\"]', 50, 'active'),
        ('Enterprise', 'Para grandes volumes', 149.90, 'monthly',
         '[\"Encartes ilimitados\", \"Todos os templates\", \"Suporte 24/7\", \"Exportação em PDF\", \"API de integração\", \"White label\"]', 0, 'active')
    ");
}

/**
 * Cria o arquivo .env
 */
function createEnvFile($data) {
    $envContent = "# Configurações do Banco de Dados\n";
    $envContent .= "DB_HOST=" . $data['db_host'] . "\n";
    $envContent .= "DB_PORT=" . $data['db_port'] . "\n";
    $envContent .= "DB_NAME=" . $data['db_name'] . "\n";
    $envContent .= "DB_USER=" . $data['db_user'] . "\n";
    $envContent .= "DB_PASS=" . $data['db_pass'] . "\n\n";
    
    $envContent .= "# Configurações do Site\n";
    $envContent .= "SITE_URL=" . $data['site_url'] . "\n";
    $envContent .= "SITE_NAME=" . $data['site_name'] . "\n\n";
    
    $envContent .= "# Configurações de Email\n";
    $envContent .= "MAIL_HOST=smtp.example.com\n";
    $envContent .= "MAIL_PORT=587\n";
    $envContent .= "MAIL_USER=" . $data['mail_from'] . "\n";
    $envContent .= "MAIL_PASS=\n";
    $envContent .= "MAIL_FROM=" . $data['mail_from'] . "\n";
    $envContent .= "MAIL_FROM_NAME=" . $data['site_name'] . "\n\n";
    
    $envContent .= "# Mercado Pago\n";
    $envContent .= "MP_ACCESS_TOKEN=" . $data['mp_token'] . "\n";
    $envContent .= "MP_PUBLIC_KEY=" . $data['mp_public_key'] . "\n";
    
    file_put_contents(BASE_PATH . '/.env', $envContent);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - EncartePro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e8401c;
            --secondary-color: #f5a623;
            --dark-color: #1a1a1a;
            --light-color: #f8f9fa;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 3rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        .logo p {
            color: #666;
            margin-top: 0.5rem;
        }
        
        .steps {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem;
            border-bottom: 3px solid #eee;
            color: #999;
            font-weight: 600;
        }
        
        .step.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .step.completed {
            border-bottom-color: var(--success-color);
            color: var(--success-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #c93516;
        }
        
        .errors {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        
        .errors ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        
        .success h2 {
            color: var(--success-color);
            margin-bottom: 1rem;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #004085;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            font-family: 'Syne', sans-serif;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <h1>🎨 EncartePro</h1>
            <p>Instalação do Sistema</p>
        </div>
        
        <?php if ($success): ?>
            <div class="success">
                <h2>✅ Instalação Concluída!</h2>
                <p>O sistema foi instalado com sucesso.</p>
                <p style="margin-top: 1rem;">
                    <strong>Email Admin:</strong> <?php echo htmlspecialchars($_POST['admin_email']); ?><br>
                    <a href="<?php echo SITE_URL ?? 'http://localhost/saas-encartes'; ?>/auth/login.php" 
                       class="btn btn-primary" style="margin-top: 1.5rem; display: inline-block; width: auto;">
                        Ir para Login
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div class="steps">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                    1. Banco de Dados
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                    2. Conta Admin
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php if ($step === 1): ?>
                    <input type="hidden" name="step" value="1">
                    
                    <div class="info-box">
                        💡 Dica: Certifique-se de que o banco de dados MySQL esteja criado e o usuário tenha permissões adequadas.
                    </div>
                    
                    <div class="form-section">
                        <h3>📦 Configurações do Banco de Dados</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_host">Host *</label>
                                <input type="text" id="db_host" name="db_host" value="localhost" required>
                            </div>
                            <div class="form-group">
                                <label for="db_port">Porta</label>
                                <input type="number" id="db_port" name="db_port" value="3306">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="db_name">Nome do Banco *</label>
                                <input type="text" id="db_name" name="db_name" required placeholder="ex: encartepro">
                            </div>
                            <div class="form-group">
                                <label for="db_user">Usuário *</label>
                                <input type="text" id="db_user" name="db_user" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Senha</label>
                            <input type="password" id="db_pass" name="db_pass" placeholder="Deixe vazio se não tiver senha">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>🌐 Configurações do Site</h3>
                        
                        <div class="form-group">
                            <label for="site_url">URL do Site *</label>
                            <input type="url" id="site_url" name="site_url" required 
                                   value="http://localhost/saas-encartes" 
                                   placeholder="https://seusite.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="site_name">Nome do Sistema</label>
                            <input type="text" id="site_name" name="site_name" value="EncartePro">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>📧 Configurações de Email</h3>
                        
                        <div class="form-group">
                            <label for="mail_from">Email Remetente</label>
                            <input type="email" id="mail_from" name="mail_from" 
                                   placeholder="noreply@seusite.com">
                            <small style="color: #666; font-size: 0.85rem;">
                                Este email será usado para envio de notificações
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>💳 Mercado Pago</h3>
                        
                        <div class="form-group">
                            <label for="mp_token">Access Token</label>
                            <input type="text" id="mp_token" name="mp_token" 
                                   placeholder="APP_USR-...">
                            <small style="color: #666; font-size: 0.85rem;">
                                Obtenha em: https://www.mercadopago.com.br/developers
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="mp_public_key">Chave Pública</label>
                            <input type="text" id="mp_public_key" name="mp_public_key" 
                                   placeholder="APP_USR-...">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        Continuar →
                    </button>
                    
                <?php elseif ($step === 2): ?>
                    <input type="hidden" name="step" value="2">
                    
                    <div class="info-box">
                        👤 Crie a conta de administrador do sistema. Você usará estas credenciais para fazer login.
                    </div>
                    
                    <div class="form-section">
                        <h3>👤 Criar Conta Administrador</h3>
                        
                        <div class="form-group">
                            <label for="admin_name">Nome Completo *</label>
                            <input type="text" id="admin_name" name="admin_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Email *</label>
                            <input type="email" id="admin_email" name="admin_email" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_password">Senha *</label>
                                <input type="password" id="admin_password" name="admin_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label for="admin_password_confirm">Confirmar Senha *</label>
                                <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="6">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        ✅ Completar Instalação
                    </button>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="#" onclick="history.back(); return false;" style="color: #666;">
                            ← Voltar
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
