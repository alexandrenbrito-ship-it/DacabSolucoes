<?php
/**
 * FlyerSaaS - Sistema SaaS para criação de encartes digitais
 * Arquivo de configuração e autoload
 */

// Definir caminho base
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', __DIR__);

// Carregar variáveis de ambiente
if (file_exists(ROOT_PATH . '/.env')) {
    $env = parse_ini_file(ROOT_PATH . '/.env');
    foreach ($env as $key => $value) {
        define($key, $value);
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

// Verificar se está instalado
function isInstalled() {
    return defined('INSTALLED') && INSTALLED === true;
}

// Redirecionar para instalação se necessário
function checkInstallation() {
    if (!isInstalled() && basename($_SERVER['PHP_SELF']) !== 'install.php') {
        redirect(BASE_URL . '/install.php');
    }
}

// Carregar classes automaticamente
spl_autoload_register(function ($class) {
    $file = ROOT_PATH . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
