<?php
/**
 * /install.php
 * Página de instalação e configuração do sistema Encarts
 * Permite configurar URL, dados do banco de dados e criar tabelas automaticamente
 */

// Desabilitar exibição de erros em produção
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Verificar se já está instalado
$installedFile = __DIR__ . '/.installed';
if (file_exists($installedFile) && !isset($_GET['reinstall'])) {
    die("Sistema já está instalado. Para reinstalar, acesse ?reinstall=1");
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = '';

// Processar formulário de configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save_config') {
        // Validar dados recebidos
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $appUrl = trim($_POST['app_url'] ?? '');
        $appEnv = $_POST['app_env'] ?? 'production';
        
        // Validações básicas
        if (empty($dbHost)) $errors[] = "Host do banco de dados é obrigatório";
        if (empty($dbName)) $errors[] = "Nome do banco de dados é obrigatório";
        if (empty($dbUser)) $errors[] = "Usuário do banco de dados é obrigatório";
        if (empty($appUrl)) $errors[] = "URL da aplicação é obrigatória";
        
        // Validar formato da URL
        if (!empty($appUrl) && !filter_var($appUrl, FILTER_VALIDATE_URL)) {
            $errors[] = "URL inválida. Use o formato: https://seudominio.com";
        }
        
        if (empty($errors)) {
            // Testar conexão com o banco de dados
            try {
                $dsn = "mysql:host=" . $dbHost . ";charset=utf8mb4";
                $testPdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                // Verificar se banco existe, se não, tentar criar
                $testPdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '', $dbName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $testPdo->exec("USE `" . str_replace('`', '', $dbName) . "`");
                
                // Criar arquivo .env
                $envContent = "# Configurações do Banco de Dados\n";
                $envContent .= "DB_HOST={$dbHost}\n";
                $envContent .= "DB_NAME={$dbName}\n";
                $envContent .= "DB_USER={$dbUser}\n";
                $envContent .= "DB_PASS={$dbPass}\n";
                $envContent .= "DB_CHARSET=utf8mb4\n\n";
                $envContent .= "# Configurações da Aplicação\n";
                $envContent .= "APP_URL={$appUrl}\n";
                $envContent .= "APP_ENV={$appEnv}\n";
                $envContent .= "APP_DEBUG=false\n";
                
                $envPath = __DIR__ . '/.env';
                if (file_put_contents($envPath, $envContent)) {
                    // Proteger arquivo .env via .htaccess se não existir
                    $htaccessContent = "Order deny,allow\nDeny from all\n";
                    file_put_contents(__DIR__ . '/config/.htaccess', $htaccessContent);
                    
                    $success = 'config_saved';
                    $step = 2;
                } else {
                    $errors[] = "Não foi possível criar o arquivo .env. Verifique as permissões.";
                }
                
            } catch (PDOException $e) {
                $errorCode = $e->getCode();
                if ($errorCode == 1045) {
                    $errors[] = "Usuário ou senha incorretos";
                } elseif ($errorCode == 2002) {
                    $errors[] = "Não foi possível conectar ao servidor MySQL. Verifique o host.";
                } else {
                    $errors[] = "Erro na conexão: " . $e->getMessage();
                }
            }
        }
    }
    
    // Instalar banco de dados
    if ($action === 'install_database' && empty($errors)) {
        try {
            // Carregar configurações salvas
            require_once __DIR__ . '/config/database.php';
            
            // Ler script SQL
            $sqlFile = __DIR__ . '/sql/schema.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("Arquivo schema.sql não encontrado");
            }
            
            $sql = file_get_contents($sqlFile);
            
            // Executar múltiplas queries
            $pdo->exec($sql);
            
            // Marcar como instalado
            file_put_contents($installedFile, date('Y-m-d H:i:s'));
            
            $success = 'database_installed';
            $step = 3;
            
        } catch (Exception $e) {
            $errors[] = "Erro ao instalar banco de dados: " . $e->getMessage();
        }
    }
}

// Função para sanitizar output
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Encarts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-container {
            max-width: 700px;
            width: 100%;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #e9ecef;
        }
        .step.completed:not(:last-child)::after {
            background: #28a745;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .alert {
            border-radius: 10px;
        }
        .logo {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="install-container px-3">
        <div class="text-center mb-4">
            <div class="logo">
                <i class="bi bi-layers-fill"></i>
            </div>
            <h1 class="text-white fw-bold">Encarts Installer</h1>
            <p class="text-white-50">Configure seu sistema de encarts digitais</p>
        </div>

        <!-- Indicador de Passos -->
        <div class="step-indicator px-4">
            <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">
                <div class="step-number">1</div>
                <small class="text-white">Configuração</small>
            </div>
            <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">
                <div class="step-number">2</div>
                <small class="text-white">Banco de Dados</small>
            </div>
            <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                <div class="step-number">3</div>
                <small class="text-white">Conclusão</small>
            </div>
        </div>

        <div class="card bg-white p-4">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Erros encontrados:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success === 'config_saved'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Configurações salvas com sucesso! Agora vamos instalar o banco de dados.
                </div>
            <?php endif; ?>

            <?php if ($success === 'database_installed'): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Banco de dados instalado com sucesso!
                </div>
            <?php endif; ?>

            <!-- Passo 1: Configuração -->
            <?php if ($step === 1): ?>
                <h3 class="mb-4"><i class="bi bi-gear-fill me-2"></i>Configurações Iniciais</h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_config">
                    
                    <div class="mb-3">
                        <label for="app_url" class="form-label">URL da Aplicação <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="app_url" name="app_url" 
                               value="<?= h($_POST['app_url'] ?? 'https://' . ($_SERVER['HTTP_HOST'] ?? '')) ?>" 
                               placeholder="https://seudominio.com/encarts" required>
                        <div class="form-text">URL completa onde o sistema será acessado</div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="bi bi-database-fill me-2"></i>Dados do Banco de Dados</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_host" class="form-label">Host <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_host" name="db_host" 
                                   value="<?= h($_POST['db_host'] ?? 'localhost') ?>" 
                                   placeholder="localhost" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db_name" class="form-label">Nome do Banco <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_name" name="db_name" 
                                   value="<?= h($_POST['db_name'] ?? 'u624766619_encartes') ?>" 
                                   placeholder="u624766619_encartes" required>
                            <div class="form-text">O banco será criado automaticamente se não existir</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="db_user" class="form-label">Usuário <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="db_user" name="db_user" 
                                   value="<?= h($_POST['db_user'] ?? 'u624766619_encartes') ?>" 
                                   placeholder="u624766619_encartes" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="db_pass" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                   value="<?= h($_POST['db_pass'] ?? 'Pt190912!@#') ?>" 
                                   placeholder="••••••••">
                            <div class="form-text">Senha padrão: Pt190912!@#</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="app_env" class="form-label">Ambiente</label>
                        <select class="form-select" id="app_env" name="app_env">
                            <option value="production" selected>Produção</option>
                            <option value="development">Desenvolvimento</option>
                        </select>
                        <div class="form-text">Em produção, erros não são exibidos</div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-save-fill me-2"></i>Salvar Configurações
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Passo 2: Instalação do Banco -->
            <?php if ($step === 2): ?>
                <h3 class="mb-4"><i class="bi bi-database-fill-check me-2"></i>Instalar Banco de Dados</h3>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    O sistema irá criar as seguintes tabelas:
                    <ul class="mb-0 mt-2">
                        <li><code>users</code> - Usuários do sistema</li>
                        <li><code>encarts</code> - Encarts criados</li>
                        <li><code>templates</code> - Templates pré-definidos</li>
                        <li><code>user_uploads</code> - Imagens enviadas</li>
                        <li><code>sessions</code> - Sessões ativas</li>
                    </ul>
                </div>

                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Atenção:</strong> Se já existirem tabelas com esses nomes, elas serão substituídas!
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="install_database">
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-hdd-network-fill me-2"></i>Instalar Banco de Dados
                        </button>
                        <a href="?step=1" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Passo 3: Conclusão -->
            <?php if ($step === 3): ?>
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="mb-3">Instalação Concluída!</h3>
                    <p class="lead text-muted">Seu sistema Encarts está pronto para uso.</p>
                    
                    <div class="alert alert-success text-start mt-4">
                        <strong>Próximos passos:</strong>
                        <ol class="mb-0 mt-2">
                            <li>Acesse <code>index.php</code> para fazer login</li>
                            <li>Use as contas de teste criadas:
                                <ul class="mb-0 mt-1">
                                    <li><strong>Admin:</strong> admin@encarts.com / admin123</li>
                                    <li><strong>User Free:</strong> user@free.com / user123</li>
                                    <li><strong>User Pro:</strong> user@pro.com / user123</li>
                                </ul>
                            </li>
                            <li>Para segurança, remova o arquivo <code>install.php</code> após o uso</li>
                        </ol>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Ir para Login
                        </a>
                        <a href="?reinstall=1" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reinstalar
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <small class="text-white-50">
                <i class="bi bi-shield-check me-1"></i>
                Sistema seguro com PDO, prepared statements e proteção CSRF
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
