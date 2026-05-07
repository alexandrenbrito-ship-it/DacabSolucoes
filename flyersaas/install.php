<?php
/**
 * FlyerSaaS - Script de Instalação
 * Deve ser executado apenas na primeira vez
 * 
 * Este script verifica se as tabelas existem no banco de dados.
 * Se não existirem, redireciona automaticamente para esta página.
 */

// Desabilitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$installationComplete = false;

// Verificar se já está instalado e as tabelas existem
$configFile = __DIR__ . '/.env';
if (file_exists($configFile)) {
    $env = @parse_ini_file($configFile);
    if ($env !== false && isset($env['INSTALLED']) && $env['INSTALLED'] === 'true') {
        // Arquivo .env diz que está instalado, verificar tabelas
        try {
            $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
            $testConn = new PDO($dsn, $env['DB_USER'], $env['DB_PASS']);
            $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $testConn->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredTables = ['users', 'plans', 'user_subscriptions', 'gallery_images', 'flyers', 'flyer_items', 'system_config', 'migrations'];
            $allTablesExist = true;
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $tables)) {
                    $allTablesExist = false;
                    break;
                }
            }
            
            if ($allTablesExist && $step === 1) {
                // Sistema realmente instalado
                header('Location: login.php');
                exit;
            }
        } catch (Exception $e) {
            // Erro de conexão, continuar com instalação
        }
    }
}

// Processar formulário de instalação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'test_db') {
            // Testar conexão com banco de dados
            $host = $_POST['db_host'];
            $name = $_POST['db_name'];
            $user = $_POST['db_user'];
            $pass = $_POST['db_pass'];
            
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            $testConn = new PDO($dsn, $user, $pass);
            $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo json_encode(['success' => true, 'message' => 'Conexão bem-sucedida!']);
            exit;
        }
        
        if ($_POST['action'] === 'install') {
            // Validar dados
            $required = ['db_host', 'db_name', 'db_user', 'base_url', 'admin_name', 'admin_email', 'admin_password'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Campo {$field} é obrigatório");
                }
            }
            
            // Testar conexão
            $dsn = "mysql:host={$_POST['db_host']};dbname={$_POST['db_name']};charset=utf8mb4";
            $db = new PDO($dsn, $_POST['db_user'], $_POST['db_pass'] ?? '');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar se as tabelas já existem
            $stmt = $db->query("SHOW TABLES");
            $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredTables = ['users', 'plans', 'user_subscriptions', 'gallery_images', 'flyers', 'flyer_items', 'system_config', 'migrations'];
            
            $tablesExist = true;
            foreach ($requiredTables as $table) {
                if (!in_array($table, $existingTables)) {
                    $tablesExist = false;
                    break;
                }
            }
            
            // Se tabelas já existem, apenas atualiza .env e pula criação
            if ($tablesExist) {
                $success = 'Banco de dados já possui todas as tabelas! Criando arquivo de configuração...';
            } else {
                $success = 'Criando tabelas no banco de dados...';
            }
            
            // Criar arquivo .env
            $envContent = sprintf(
                "DB_HOST=%s\nDB_NAME=%s\nDB_USER=%s\nDB_PASS=%s\nBASE_URL=%s\nINSTALLED=true",
                $_POST['db_host'],
                $_POST['db_name'],
                $_POST['db_user'],
                $_POST['db_pass'] ?? '',
                rtrim($_POST['base_url'], '/')
            );
            
            if (!file_put_contents($configFile, $envContent)) {
                throw new Exception("Não foi possível criar o arquivo .env");
            }
            
            // Incluir config para carregar as novas definições
            require_once __DIR__ . '/config.php';
            
            // Re-inicializar constantes manualmente
            define('DB_HOST', $_POST['db_host']);
            define('DB_NAME', $_POST['db_name']);
            define('DB_USER', $_POST['db_user']);
            define('DB_PASS', $_POST['db_pass'] ?? '');
            define('BASE_URL', rtrim($_POST['base_url'], '/'));
            define('INSTALLED', true);
            define('ROOT_PATH', __DIR__);
            
            // Se tabelas não existem, criar agora
            if (!$tablesExist) {
                // Carregar classes
                require_once __DIR__ . '/includes/Database.php';
                require_once __DIR__ . '/includes/Migration.php';
                
                // Executar migrações
                $migration = new Migration();
                $result = $migration->createInitialTables();
                
                if (!$result['success']) {
                    throw new Exception("Erro ao criar tabelas: " . $result['error']);
                }
                
                // Inserir dados iniciais
                $seedResult = $migration->seedInitialData();
                if (!$seedResult['success']) {
                    // Log warning but continue
                    error_log("Warning: " . $seedResult['error']);
                }
            }
            
            // Verificar se admin já existe
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
            $stmt->execute();
            $adminExists = $stmt->fetchColumn() > 0;
            
            // Criar administrador apenas se não existir
            if (!$adminExists) {
                $passwordHash = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (name, email, password, is_admin, status, created_at)
                    VALUES (?, ?, ?, 1, 'active', NOW())
                ");
                $stmt->execute([$_POST['admin_name'], $_POST['admin_email'], $passwordHash]);
            }
            
            $success = 'Instalação concluída com sucesso! Redirecionando...';
            $step = 4;
            $installationComplete = true;
            
            // Remover permissões de escrita do .env após instalação (segurança)
            @chmod($configFile, 0444);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
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
        body { background: #f5f5f5; padding-top: 50px; }
        .install-card { max-width: 700px; margin: 0 auto; }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step { width: 30px; height: 30px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .step.active { background: #0d6efd; color: white; }
        .step.completed { background: #198754; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card install-card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">🚀 Instalação do FlyerSaaS</h4>
            </div>
            <div class="card-body">
                <!-- Indicador de passos -->
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
                    <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">2</div>
                    <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">3</div>
                    <div class="step <?php echo $step >= 4 ? 'completed' : ''; ?>">✓</div>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Passo 1: Boas-vindas -->
                <?php if ($step === 1): ?>
                <h5>Bem-vindo ao FlyerSaaS!</h5>
                <p>Este assistente irá guiá-lo pela instalação do sistema.</p>
                <div class="alert alert-info">
                    <strong>Pré-requisitos:</strong>
                    <ul class="mb-0 mt-2">
                        <li>PHP 7.4 ou superior</li>
                        <li>MySQL 5.7 ou superior</li>
                        <li>Permissão de escrita na pasta do projeto</li>
                    </ul>
                </div>
                <a href="?step=2" class="btn btn-primary">Continuar</a>

                <!-- Passo 2: Configuração do Banco -->
                <?php elseif ($step === 2): ?>
                <h5>Configuração do Banco de Dados</h5>
                <p class="text-muted">Informe os dados de conexão com seu banco de dados MySQL.</p>
                <form id="dbForm" method="POST">
                    <input type="hidden" name="action" value="test_db">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Host do MySQL *</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                            <small class="text-muted">Geralmente "localhost" ou endereço fornecido pela hospedagem</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do Banco *</label>
                            <input type="text" name="db_name" class="form-control" placeholder="flyersaas" required>
                            <small class="text-muted">O banco deve estar criado no MySQL</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuário do Banco *</label>
                            <input type="text" name="db_user" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Senha do Banco</label>
                            <input type="password" name="db_pass" class="form-control">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-plug"></i> Testar Conexão
                    </button>
                </form>
                <hr>
                <form method="POST" id="installForm">
                    <input type="hidden" name="action" value="install">
                    <input type="hidden" name="db_host" id="final_db_host" value="">
                    <input type="hidden" name="db_name" id="final_db_name" value="">
                    <input type="hidden" name="db_user" id="final_db_user" value="">
                    <input type="hidden" name="db_pass" id="final_db_pass" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">URL Base do Sistema *</label>
                        <input type="url" name="base_url" class="form-control" 
                               value="<?php 
                                   $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                                   $host = $_SERVER['HTTP_HOST'];
                                   $path = rtrim(dirname($_SERVER['PHP_SELF']), '/');
                                   echo $protocol . '://' . $host . $path;
                               ?>" 
                               required>
                        <small class="text-muted">Ex: https://meusistema.com ou http://localhost/flyersaas</small>
                    </div>
                    <div class="alert alert-info">
                        <strong>Atenção:</strong> As tabelas serão criadas automaticamente no banco de dados.
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-database"></i> Criar Tabelas e Continuar
                    </button>
                </form>
                <a href="?step=1" class="btn btn-link">Voltar</a>

                <!-- Passo 3: Admin -->
                <?php elseif ($step === 3): ?>
                <h5>Criar Administrador Principal</h5>
                <p class="text-muted">Informe os dados do primeiro usuário administrador do sistema.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="install">
                    <input type="hidden" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? ''); ?>">
                    <input type="hidden" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>">
                    <input type="hidden" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>">
                    <input type="hidden" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                    <input type="hidden" name="base_url" value="<?php echo htmlspecialchars($_POST['base_url'] ?? ''); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="admin_name" class="form-control" placeholder="Ex: João Silva" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="admin_email" class="form-control" placeholder="admin@empresa.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha *</label>
                        <input type="password" name="admin_password" class="form-control" required minlength="6">
                        <small class="text-muted">Mínimo de 6 caracteres</small>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Este usuário terá acesso completo ao painel administrativo.
                    </div>
                    <button type="submit" class="btn btn-primary">Finalizar Instalação</button>
                </form>
                <a href="?step=2" class="btn btn-link">Voltar</a>

                <!-- Passo 4: Conclusão -->
                <?php elseif ($step === 4): ?>
                <div class="text-center">
                    <div class="display-1 text-success">✓</div>
                    <h4>Instalação Concluída!</h4>
                    <p>O sistema está pronto para uso.</p>
                    <div class="alert alert-warning">
                        <strong>Importante:</strong> Por segurança, remova ou renomeie o arquivo <code>install.php</code>.
                    </div>
                    <a href="login.php" class="btn btn-primary">Ir para Login</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('dbForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('final_db_host').value = formData.get('db_host');
                document.getElementById('final_db_name').value = formData.get('db_name');
                document.getElementById('final_db_user').value = formData.get('db_user');
                document.getElementById('final_db_pass').value = formData.get('db_pass');
                alert('Conexão testada com sucesso! Agora clique em "Criar Tabelas e Continuar".');
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(err => alert('Erro ao testar conexão'));
    });
    </script>
</body>
</html>
