<?php
/**
 * EncartePro - Configuração Central
 * Arquivo responsável por carregar variáveis de ambiente, configurar banco de dados
 * e fornecer funções utilitárias para todo o sistema.
 */

// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define o caminho base do projeto
define('BASE_PATH', dirname(__DIR__));
define('ROOT_PATH', dirname(__DIR__));

// Caminho do arquivo .env
$envFile = BASE_PATH . '/.env';

// Verifica se o arquivo .env existe
if (!file_exists($envFile)) {
    // Se não existir e não estiver na página de instalação, redireciona
    if (!strpos($_SERVER['REQUEST_URI'], '/install/')) {
        header('Location: /saas-encartes/install/');
        exit;
    }
} else {
    // Lê e parseia o arquivo .env
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        // Ignora comentários
        if (trim($line) === '' || strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Separa chave e valor
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove aspas se existirem
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            define($key, $value);
        }
    }
}

// Define valores padrão caso não estejam no .env
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_PORT') or define('DB_PORT', '3306');
defined('DB_NAME') or define('DB_NAME', 'encartepro');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');
defined('SITE_URL') or define('SITE_URL', 'http://localhost/saas-encartes');
defined('SITE_NAME') or define('SITE_NAME', 'EncartePro');
defined('MAIL_HOST') or define('MAIL_HOST', 'smtp.example.com');
defined('MAIL_PORT') or define('MAIL_PORT', '587');
defined('MAIL_USER') or define('MAIL_USER', 'noreply@example.com');
defined('MAIL_PASS') or define('MAIL_PASS', '');
defined('MAIL_FROM') or define('MAIL_FROM', 'noreply@example.com');
defined('MAIL_FROM_NAME') or define('MAIL_FROM_NAME', 'EncartePro');
defined('MP_ACCESS_TOKEN') or define('MP_ACCESS_TOKEN', '');
defined('MP_PUBLIC_KEY') or define('MP_PUBLIC_KEY', '');

/**
 * Obtém uma instância singleton da conexão PDO com o banco de dados
 * 
 * @return PDO|null Retorna a instância PDO ou null se falhar
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, logue o erro em vez de exibir
            error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Verifica se o usuário está logado
 * 
 * @return bool True se estiver logado, false caso contrário
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se o usuário logado é administrador
 * 
 * @return bool True se for admin, false caso contrário
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redireciona para uma URL específica
 * 
 * @param string $url URL para redirecionar
 * @return void
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Sanitiza entradas do usuário para prevenir XSS
 * 
 * @param string $input Entrada a ser sanitizada
 * @return string Entrada sanitizada
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Gera um token CSRF para proteção de formulários
 * 
 * @return string Token CSRF gerado
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF de um formulário
 * 
 * @param string $token Token a ser validado
 * @return bool True se válido, false caso contrário
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtém o plano ativo de um usuário
 * 
 * @param int $userId ID do usuário
 * @return array|null Retorna os dados do plano ou null se não tiver plano ativo
 */
function getUserPlan($userId) {
    $db = getDB();
    if (!$db) return null;
    
    $sql = "SELECT s.*, p.name as plan_name, p.description, p.price, p.billing_cycle, 
            p.features, p.max_encartes, p.mp_plan_id
            FROM subscriptions s
            INNER JOIN plans p ON s.plan_id = p.id
            WHERE s.user_id = :user_id 
            AND s.status IN ('active', 'trial')
            AND (s.expires_at IS NULL OR s.expires_at >= CURDATE())
            ORDER BY s.created_at DESC
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Verifica se o usuário pode criar um novo encarte baseado no limite do plano
 * 
 * @param int $userId ID do usuário
 * @return bool True se pode criar, false se atingiu o limite
 */
function canCreateEncarte($userId) {
    $db = getDB();
    if (!$db) return false;
    
    // Obtém o plano do usuário
    $plan = getUserPlan($userId);
    
    // Se não tiver plano ativo, não pode criar
    if (!$plan) return false;
    
    // Se for ilimitado (max_encartes <= 0 ou NULL), pode criar
    if ($plan['max_encartes'] <= 0) return true;
    
    // Conta quantos encartes o usuário já tem
    $sql = "SELECT COUNT(*) as total FROM encartes WHERE user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['total'] < $plan['max_encartes'];
}

/**
 * Obtém o número de encartes criados pelo usuário
 * 
 * @param int $userId ID do usuário
 * @return int Número de encartes
 */
function getEncarteCount($userId) {
    $db = getDB();
    if (!$db) return 0;
    
    $sql = "SELECT COUNT(*) as total FROM encartes WHERE user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['total'];
}

/**
 * Formata um valor monetário para exibição
 * 
 * @param float $value Valor a ser formatado
 * @return string Valor formatado em BRL
 */
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata uma data para exibição em português
 * 
 * @param string $date Data no formato Y-m-d ou Y-m-d H:i:s
 * @param bool $includeTime Se deve incluir a hora
 * @return string Data formatada
 */
function formatDate($date, $includeTime = false) {
    $timestamp = strtotime($date);
    if ($includeTime) {
        return date('d/m/Y H:i', $timestamp);
    }
    return date('d/m/Y', $timestamp);
}

/**
 * Envia uma mensagem flash para a sessão
 * 
 * @param string $type Tipo da mensagem (success, error, warning, info)
 * @param string $message Mensagem a ser exibida
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Obtém e limpa uma mensagem flash da sessão
 * 
 * @return array|null Retorna a mensagem ou null se não houver
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Função de debug que só funciona em ambiente de desenvolvimento
 * 
 * @param mixed $data Dados para debug
 * @return void
 */
function debug($data) {
    if (defined('DEBUG') && DEBUG) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

/**
 * Obtém uma configuração do banco de dados
 * 
 * @param string $key Chave da configuração
 * @param mixed $default Valor padrão caso não exista
 * @return mixed Valor da configuração ou default
 */
function getSetting($key, $default = null) {
    static $settingsCache = [];
    
    // Carrega configurações na primeira chamada
    if (empty($settingsCache)) {
        $db = getDB();
        if ($db) {
            try {
                $stmt = $db->query("SELECT `key`, `value` FROM settings");
                while ($row = $stmt->fetch()) {
                    $settingsCache[$row['key']] = $row['value'];
                }
            } catch (PDOException $e) {
                // Tabela não existe ainda
            }
        }
    }
    
    return isset($settingsCache[$key]) ? $settingsCache[$key] : $default;
}

/**
 * Atualiza uma configuração no banco de dados
 * 
 * @param string $key Chave da configuração
 * @param mixed $value Valor a ser salvo
 * @return bool True se sucesso, false se falhou
 */
function updateSetting($key, $value) {
    $db = getDB();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO settings (`key`, `value`, updated_at) 
            VALUES (:key, :value, NOW())
            ON DUPLICATE KEY UPDATE `value` = :value, updated_at = NOW()
        ");
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->bindValue(':value', $value, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar setting $key: " . $e->getMessage());
        return false;
    }
}

/**
 * Recarrega as configurações do cache
 * 
 * @return void
 */
function refreshSettings() {
    global $settingsCache;
    $settingsCache = [];
    getSetting(''); // Força recarregamento
}

/**
 * Obtém o tema do usuário logado ou o tema padrão do sistema
 * 
 * @return string 'light' ou 'dark'
 */
function getUserTheme() {
    if (isLoggedIn() && isset($_SESSION['user_theme'])) {
        return $_SESSION['user_theme'];
    }
    return getSetting('default_theme', 'light');
}

/**
 * Faz upload de um arquivo com validação
 * 
 * @param array $file Array $_FILES['arquivo']
 * @param string $uploadDir Diretório de destino
 * @param array $allowedTypes Tipos MIME permitidos
 * @param int $maxSize Tamanho máximo em bytes
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function uploadFile($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 2097152) {
    // Cria diretório se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Valida erro no upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Erro no upload. Código: ' . $file['error']
        ];
    }
    
    // Valida tamanho
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Arquivo muito grande. Máximo: ' . round($maxSize / 1048576, 2) . 'MB'
        ];
    }
    
    // Valida tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'success' => false,
            'filename' => null,
            'error' => 'Tipo de arquivo não permitido: ' . $mimeType
        ];
    }
    
    // Gera nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . '_' . time() . '.' . $extension;
    $destination = $uploadDir . '/' . $newName;
    
    // Move arquivo
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $newName,
            'error' => null
        ];
    }
    
    return [
        'success' => false,
        'filename' => null,
        'error' => 'Falha ao mover arquivo'
    ];
}

/**
 * Registra sessão de login do usuário
 * 
 * @param int $userId ID do usuário
 * @return void
 */
function registerUserSession($userId) {
    $db = getDB();
    if (!$db) return;
    
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $db->prepare("
            INSERT INTO user_sessions (user_id, ip, user_agent, created_at)
            VALUES (:user_id, :ip, :user_agent, NOW())
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        // Ignora se tabela não existir
    }
}
