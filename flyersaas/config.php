<?php
/**
 * FlyerSaaS - Sistema SaaS para criação de encartes digitais
 * Arquivo de configuração e autoload
 */

// Definir caminho base
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', __DIR__);

// Carregar variáveis de ambiente
$envLoaded = false;
if (file_exists(ROOT_PATH . '/.env')) {
    $env = parse_ini_file(ROOT_PATH . '/.env');
    if ($env !== false) {
        foreach ($env as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
        $envLoaded = true;
    }
}

// Configurações padrão caso não existam
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'flyersaas');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/flyersaas');
if (!defined('INSTALLED')) define('INSTALLED', false);

// Iniciar sessão com segurança
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para gerar token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para verificar token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para sanitizar entrada
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Função para redirecionar
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Verifica se todas as tabelas necessárias existem no banco de dados
 * @return bool
 */
function checkDatabaseTables() {
    $requiredTables = [
        'users',
        'plans',
        'user_subscriptions',
        'gallery_images',
        'flyers',
        'flyer_items',
        'system_config',
        'migrations'
    ];
    
    try {
        // Tenta criar conexão
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                return false;
            }
        }
        return true;
    } catch (Exception $e) {
        // Erro de conexão ou outra falha
        return false;
    }
}

// Verificar se está instalado (arquivo .env existe e INSTALLED=true)
function isInstalled() {
    if (!file_exists(ROOT_PATH . '/.env')) {
        return false;
    }
    
    $env = parse_ini_file(ROOT_PATH . '/.env');
    if ($env === false) {
        return false;
    }
    
    return isset($env['INSTALLED']) && $env['INSTALLED'] === 'true';
}

/**
 * Verificação completa de instalação
 * Retorna true se o sistema estiver instalado e pronto para uso
 */
function isSystemReady() {
    // Verifica se o arquivo .env existe e está configurado
    if (!isInstalled()) {
        return false;
    }
    
    // Verifica se as tabelas existem
    if (!checkDatabaseTables()) {
        return false;
    }
    
    return true;
}

// Redirecionar para instalação se necessário
function checkInstallation() {
    // Não verifica se já estiver na página de instalação
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage === 'install.php') {
        return;
    }
    
    // Se não estiver instalado ou faltar tabelas, redireciona para install.php
    if (!isSystemReady()) {
        // Usa caminho relativo se BASE_URL não estiver definido corretamente
        if (defined('BASE_URL') && BASE_URL) {
            redirect(BASE_URL . '/install.php');
        } else {
            redirect('install.php');
        }
    }
}

// Executa verificação automática em todas as páginas
checkInstallation();

// Carregar classes automaticamente
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Carregar funções auxiliares
if (file_exists(ROOT_PATH . '/includes/functions.php')) {
    require_once ROOT_PATH . '/includes/functions.php';
}
