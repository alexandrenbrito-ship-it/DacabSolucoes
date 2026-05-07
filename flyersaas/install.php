<?php
/**
 * FlyerSaaS - Install Script
 * Instalação do sistema - cria banco de dados e primeiro administrador
 */

// Desabilitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// Se já estiver instalado, redirecionar
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (strpos($envContent, 'INSTALLED=true') !== false) {
        // Verificar se as tabelas existem usando conexão direta
        $env = parse_ini_file(__DIR__ . '/.env');
        if ($env !== false && isset($env['DB_HOST'], $env['DB_NAME'], $env['DB_USER'])) {
            try {
                $dsn = "mysql:host=" . $env['DB_HOST'] . ";dbname=" . $env['DB_NAME'] . ";charset=utf8mb4";
                $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $tables = ['users', 'plans', 'user_subscriptions', 'gallery_images', 'flyers', 'flyer_items', 'system_config', 'migrations'];
                $missingTables = [];
                
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() === 0) {
                        $missingTables[] = $table;
                    }
                }
                
                if (empty($missingTables)) {
                    header('Location: index.php');
                    exit;
                }
            } catch (Exception $e) {
                // Continuar com instalação se houver erro
            }
        }
    }
}

$errors = [];
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'test_connection') {
        // Testar conexão com o banco
        $host = trim($_POST['db_host'] ?? '');
        $dbname = trim($_POST['db_name'] ?? '');
        $username = trim($_POST['db_user'] ?? '');
        $password = $_POST['db_pass'] ?? '';
        
        if (empty($host) || empty($dbname) || empty($username)) {
            $errors[] = 'Preencha todos os campos obrigatórios do banco de dados.';
        } else {
            try {
                $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $pdo = new PDO($dsn, $username, $password, $options);
                $pdo->exec("SET NAMES utf8mb4");
                
                // Testar permissões
                $pdo->query("SELECT 1");
                
                $_SESSION['db_config'] = [
                    'host' => $host,
                    'name' => $dbname,
                    'user' => $username,
                    'pass' => $password,
                ];
                
                echo json_encode(['success' => true, 'message' => 'Conexão estabelecida com sucesso!']);
                exit;
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro na conexão: ' . $e->getMessage()]);
                exit;
            }
        }
    }
    
    if ($_POST['action'] === 'install') {
        // Instalar sistema
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $baseUrl = trim($_POST['base_url'] ?? '');
        $adminName = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $adminConfirm = $_POST['admin_password_confirm'] ?? '';
        
        // Validações
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            $errors[] = 'Dados do banco de dados incompletos.';
        }
        
        if (empty($baseUrl)) {
            $errors[] = 'URL base é obrigatória.';
        } else {
            // Validar URL
            if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'URL base inválida. Exemplo: https://seusite.com';
            }
        }
        
        if (empty($adminName) || empty($adminEmail) || empty($adminPassword)) {
            $errors[] = 'Todos os dados do administrador são obrigatórios.';
        }
        
        if ($adminPassword !== $adminConfirm) {
            $errors[] = 'As senhas do administrador não coincidem.';
        }
        
        if (strlen($adminPassword) < 6) {
            $errors[] = 'A senha do administrador deve ter pelo menos 6 caracteres.';
        }
        
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail do administrador inválido.';
        }
        
        if (empty($errors)) {
            try {
                // Testar conexão novamente
                $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                $pdo->exec("SET NAMES utf8mb4");
                
                // Criar arquivo .env
                $envContent = "# FlyerSaaS Configuration\n";
                $envContent .= "# Generated on: " . date('Y-m-d H:i:s') . "\n\n";
                $envContent .= "DB_HOST={$dbHost}\n";
                $envContent .= "DB_NAME={$dbName}\n";
                $envContent .= "DB_USER={$dbUser}\n";
                $envContent .= "DB_PASS={$dbPass}\n";
                $envContent .= "BASE_URL={$baseUrl}\n";
                $envContent .= "INSTALLED=true\n";
                $envContent .= "APP_NAME=FlyerSaaS\n";
                $envContent .= "APP_VERSION=1.0.0\n";
                
                $envPath = __DIR__ . '/.env';
                if (file_put_contents($envPath, $envContent) === false) {
                    throw new Exception('Não foi possível criar o arquivo .env. Verifique as permissões da pasta.');
                }
                
                // Tornar .env somente leitura
                chmod($envPath, 0444);
                
                // Executar migrações
                $migrationsPath = __DIR__ . '/database/migrations/';
                $migrationsExecuted = 0;
                
                if (is_dir($migrationsPath)) {
                    $files = glob($migrationsPath . '*.sql');
                    sort($files);
                    
                    // Criar tabela migrations se não existir
                    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        migration VARCHAR(255) NOT NULL,
                        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                    
                    foreach ($files as $file) {
                        $migrationName = basename($file);
                        
                        // Verificar se já foi executada
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
                        $stmt->execute([$migrationName]);
                        
                        if ($stmt->fetchColumn() == 0) {
                            $sql = file_get_contents($file);
                            
                            // Dividir em múltiplos statements
                            $statements = array_filter(
                                array_map('trim', explode(';', $sql)),
                                function($s) { return !empty($s) && !preg_match('/^--/', $s); }
                            );
                            
                            foreach ($statements as $statement) {
                                if (!empty(trim($statement))) {
                                    $pdo->exec($statement);
                                }
                            }
                            
                            // Registrar migração
                            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
                            $stmt->execute([$migrationName]);
                            $migrationsExecuted++;
                        }
                    }
                }
                
                // Criar administrador
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$adminEmail]);
                
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, is_admin, status, created_at) 
                        VALUES (?, ?, ?, 1, 'active', NOW())
                    ");
                    $stmt->execute([$adminName, $adminEmail, $hashedPassword]);
                    
                    // Criar plano básico padrão se não existir
                    $stmt = $pdo->query("SELECT COUNT(*) FROM plans");
                    if ($stmt->fetchColumn() == 0) {
                        $pdo->exec("
                            INSERT INTO plans (name, price, duration_days, images_limit, flyers_limit, description, status) 
                            VALUES 
                            ('Básico', 29.90, 30, 50, 5, 'Plano inicial para pequenos mercados', 'active'),
                            ('Profissional', 79.90, 30, 200, 15, 'Plano recomendado para mercados médios', 'active'),
                            ('Enterprise', 149.90, 30, 500, 50, 'Plano completo para grandes redes', 'active')
                        ");
                    }
                    
                    // Configurações padrão
                    $pdo->exec("
                        INSERT INTO system_config (config_key, config_value, description) 
                        VALUES 
                        ('app_name', 'FlyerSaaS', 'Nome da aplicação'),
                        ('app_email', '', 'E-mail de contato'),
                        ('maintenance_mode', '0', 'Modo de manutenção (0=desligado, 1=ligado)'),
                        ('max_upload_size', '5242880', 'Tamanho máximo de upload em bytes (5MB)')
                        ON DUPLICATE KEY UPDATE config_value=config_value
                    ");
                }
                
                $success = 'Instalação concluída com sucesso! Redirecionando...';
                
                // Limpar sessão
                unset($_SESSION['db_config']);
                
                // Redirecionar após 3 segundos
                header('Refresh: 3; url=index.php');
                
            } catch (Exception $e) {
                $errors[] = 'Erro na instalação: ' . $e->getMessage();
                
                // Remover .env se houve erro
                if (file_exists($envPath)) {
                    chmod($envPath, 0644);
                    unlink($envPath);
                }
            }
        }
    }
}

// Detectar URL base automaticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
             (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$detectedUrl = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 90%;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo i {
            font-size: 60px;
            color: #667eea;
        }
        .logo h1 {
            color: #333;
            font-size: 32px;
            margin-top: 10px;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #666;
            position: relative;
            z-index: 1;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-label {
            position: absolute;
            top: 45px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            color: #666;
            white-space: nowrap;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .alert {
            border-radius: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading i {
            font-size: 40px;
            color: #667eea;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success-box {
            text-align: center;
            padding: 40px;
        }
        .success-box i {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <i class="fas fa-store"></i>
            <h1>FlyerSaaS</h1>
            <p class="text-muted">Sistema de Encartes Digitais para Supermercados</p>
        </div>

        <?php if ($success): ?>
            <!-- Tela de Sucesso -->
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <h2 class="text-success">Instalação Concluída!</h2>
                <p class="lead"><?= htmlspecialchars($success) ?></p>
                <div class="alert alert-info mt-4">
                    <strong>Próximos passos:</strong><br>
                    • Você será redirecionado para a página de login<br>
                    • Use o e-mail e senha do administrador que você criou<br>
                    • Acesse o painel admin em <code>/admin/</code>
                </div>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-sign-in-alt"></i> Ir para Login
                </a>
            </div>
        <?php else: ?>
            <!-- Indicador de Passos -->
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?>">
                    1
                    <span class="step-label">Banco de Dados</span>
                </div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?>">
                    2
                    <span class="step-label">Configuração</span>
                </div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                    3
                    <span class="step-label">Admin</span>
                </div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                    4
                    <span class="step-label">Concluir</span>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Passo 1: Dados do Banco -->
            <div class="form-section <?= $step === 1 ? 'active' : '' ?>" id="step1">
                <h4 class="mb-4"><i class="fas fa-database"></i> Configurar Banco de Dados</h4>
                
                <div class="info-box">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Informações necessárias:</strong><br>
                    Você precisará dos dados de acesso ao MySQL fornecidos pela sua hospedagem (Hostinger, etc).
                </div>

                <form id="dbForm">
                    <input type="hidden" name="action" value="test_connection">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_host" class="form-label">Host do Banco *</label>
                            <input type="text" class="form-control" id="db_host" name="db_host" 
                                   value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                            <small class="text-muted">Geralmente é "localhost"</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="db_name" class="form-label">Nome do Banco *</label>
                            <input type="text" class="form-control" id="db_name" name="db_name" 
                                   value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
                            <small class="text-muted">Crie o banco no painel da hospedagem</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_user" class="form-label">Usuário do Banco *</label>
                            <input type="text" class="form-control" id="db_user" name="db_user" 
                                   value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="db_pass" class="form-label">Senha do Banco</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                   value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                            <small class="text-muted">Deixe vazio se não tiver senha</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </form>

                <div class="loading" id="testLoading">
                    <i class="fas fa-spinner"></i>
                    <p class="mt-2">Testando conexão...</p>
                </div>
            </div>

            <!-- Passo 2: URL Base -->
            <div class="form-section <?= $step === 2 ? 'active' : '' ?>" id="step2">
                <h4 class="mb-4"><i class="fas fa-globe"></i> Configuração do Sistema</h4>
                
                <div class="info-box">
                    <i class="fas fa-link"></i> 
                    <strong>URL Base:</strong><br>
                    Esta é a URL onde seu sistema estará acessível. Será usada para links e redirecionamentos.
                </div>

                <form method="POST" action="?step=3">
                    <input type="hidden" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '') ?>">
                    <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>">
                    <input type="hidden" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>">
                    <input type="hidden" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                    
                    <div class="mb-3">
                        <label for="base_url" class="form-label">URL Base do Sistema *</label>
                        <input type="url" class="form-control" id="base_url" name="base_url" 
                               value="<?= htmlspecialchars($_POST['base_url'] ?? $detectedUrl) ?>" required>
                        <small class="text-muted">Ex: https://seusite.com ou http://localhost/flyersaas</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="?step=1" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Próximo <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Passo 3: Administrador -->
            <div class="form-section <?= $step === 3 ? 'active' : '' ?>" id="step3">
                <h4 class="mb-4"><i class="fas fa-user-shield"></i> Criar Administrador</h4>
                
                <div class="info-box">
                    <i class="fas fa-user-cog"></i> 
                    <strong>Primeiro Acesso:</strong><br>
                    Crie a conta do administrador principal. Você usará estes dados para acessar o painel.
                </div>

                <form method="POST" action="?step=4">
                    <input type="hidden" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? '') ?>">
                    <input type="hidden" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>">
                    <input type="hidden" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>">
                    <input type="hidden" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                    <input type="hidden" name="base_url" value="<?= htmlspecialchars($_POST['base_url'] ?? $detectedUrl) ?>">
                    
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="admin_name" name="admin_name" 
                               value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">E-mail *</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" 
                               value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="admin_password" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="admin_password_confirm" class="form-label">Confirmar Senha *</label>
                            <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="?step=2" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" name="action" value="install" class="btn btn-primary">
                            <i class="fas fa-check"></i> Instalar Sistema
                        </button>
                    </div>
                </form>
            </div>

            <!-- Passo 4: Instalação -->
            <div class="form-section <?= $step === 4 ? 'active' : '' ?>" id="step4">
                <div class="text-center py-5">
                    <div class="loading" style="display: block;">
                        <i class="fas fa-spinner"></i>
                        <h4 class="mt-3">Instalando Sistema...</h4>
                        <p class="text-muted">Por favor, aguarde. Isso pode levar alguns segundos.</p>
                    </div>
                </div>
                
                <?php
                // Auto-submit do formulário se estiver no passo 4
                if ($step === 4 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
                    echo '<script>
                        document.addEventListener("DOMContentLoaded", function() {
                            setTimeout(function() {
                                document.getElementById("autoInstallForm").submit();
                            }, 500);
                        });
                    </script>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Testar conexão com AJAX
    document.getElementById('dbForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const loading = document.getElementById('testLoading');
        
        loading.style.display = 'block';
        
        fetch('install.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success) {
                // Salvar dados na sessão via form hidden e ir para próximo passo
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?step=2';
                
                const fields = ['db_host', 'db_name', 'db_user', 'db_pass'];
                fields.forEach(field => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = field;
                    input.value = document.getElementById(field).value;
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            alert('Erro na requisição: ' + error);
        });
    });
    </script>
</body>
</html>
