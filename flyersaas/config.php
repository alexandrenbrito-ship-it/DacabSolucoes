<?php
/**
 * FlyerSaaS - Sistema SaaS para criação de encartes digitais
 * Arquivo de configuração e autoload
 */

// Definir caminho base
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', __DIR__);

// Iniciar sessão com segurança
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho para o arquivo .env
$envFile = ROOT_PATH . '/.env';

/**
 * Carrega variáveis de ambiente do arquivo .env
 */
function loadEnv($envFile) {
    if (!file_exists($envFile)) {
        return false;
    }
    
    $env = parse_ini_file($envFile);
    if ($env === false) {
        return false;
    }
    
    foreach ($env as $key => $value) {
        if (!defined($key)) {
            define($key, $value);
        }
    }
    return true;
}

// Carregar .env se existir
$envLoaded = loadEnv($envFile);

// Configurações padrão caso não existam
if (!$envLoaded || !defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!$envLoaded || !defined('DB_NAME')) define('DB_NAME', 'flyersaas');
if (!$envLoaded || !defined('DB_USER')) define('DB_USER', 'root');
if (!$envLoaded || !defined('DB_PASS')) define('DB_PASS', '');
if (!$envLoaded || !defined('BASE_URL')) define('BASE_URL', 'http://localhost/flyersaas');
if (!$envLoaded || !defined('INSTALLED')) define('INSTALLED', 'false');

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
    // Verificar se temos as constantes definidas
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        return false;
    }
    
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
        $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $stmt = $pdo->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Normalizar para lowercase para comparação
        $existingTables = array_map('strtolower', $existingTables);
        
        foreach ($requiredTables as $table) {
            if (!in_array(strtolower($table), $existingTables)) {
                return false;
            }
        }
        return true;
    } catch (Exception $e) {
        // Erro de conexão ou outra falha
        error_log("Erro ao verificar tabelas: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica se o sistema está instalado baseado no arquivo .env
 */
function isInstalled() {
    global $envFile;
    
    if (!file_exists($envFile)) {
        return false;
    }
    
    $env = parse_ini_file($envFile);
    if ($env === false) {
        return false;
    }
    
    return isset($env['INSTALLED']) && strtolower($env['INSTALLED']) === 'true';
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

/**
 * Verifica se deve redirecionar para instalação
 * NÃO redireciona se já estiver na página de instalação
 */
function checkInstallation() {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $allowedPages = ['install.php'];
    
    // Se estiver na página de instalação, não verifica nada
    if (in_array($currentPage, $allowedPages)) {
        return;
    }
    
    // Verifica se o arquivo .env existe
    if (!file_exists($envFile)) {
        redirect('install.php');
    }
    
    // Verifica se está marcado como instalado
    if (!isInstalled()) {
        redirect('install.php');
    }
    
    // Verifica se as tabelas existem
    if (!checkDatabaseTables()) {
        redirect('install.php?error=tables_missing');
    }
}

// Executa verificação automática em todas as páginas
checkInstallation();

// Agora que sabemos que está instalado, carregar classes e funções
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

// Criar conexão global com o banco de dados (agora sabemos que está instalado)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $GLOBALS['pdo'] = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Carregar configurações do sistema
    try {
        $stmt = $GLOBALS['pdo']->query("SELECT config_key, config_value FROM system_config");
        $configs = $stmt->fetchAll();
        foreach ($configs as $config) {
            if (!defined('CFG_' . strtoupper($config['config_key']))) {
                define('CFG_' . strtoupper($config['config_key']), $config['config_value']);
            }
        }
    } catch (Exception $e) {
        // Ignorar se não houver configs
    }
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco: " . $e->getMessage());
}
