<?php
/**
 * Funções Auxiliares do Sistema
 */

/**
 * Sanitiza dados de entrada para prevenir XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redireciona com mensagem flash
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        session_start();
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Exibe mensagens flash
 */
function displayFlashMessage() {
    session_start();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        $alertClass = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$type] ?? 'alert-info';
        
        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

/**
 * Verifica se o sistema está instalado
 */
function isSystemInstalled() {
    // Verifica arquivo de lock primeiro (mais rápido e confiável)
    $lockFile = __DIR__ . '/../installed.lock';
    if (file_exists($lockFile)) {
        return true;
    }
    
    // Verifica .env.php
    $envFile = __DIR__ . '/../.env.php';
    if (!file_exists($envFile)) {
        return false;
    }
    
    // Verifica se as tabelas existem
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
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
        
        $stmt = $conn->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            if (!in_array($table, $existingTables)) {
                return false;
            }
        }
        
        // Se chegou aqui, cria o lock file para próximas verificações
        file_put_contents($lockFile, date('Y-m-d H:i:s'));
        @chmod($lockFile, 0444);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtém valor de configuração do sistema
 */
function getConfig($key, $default = null) {
    static $configCache = [];
    
    if (isset($configCache[$key])) {
        return $configCache[$key];
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        $value = $result ? $result['config_value'] : $default;
        $configCache[$key] = $value;
        
        return $value;
        
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Formata valor monetário
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata data
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Gera hash único para arquivos
 */
function generateFileHash($filePath) {
    if (file_exists($filePath)) {
        return md5_file($filePath);
    }
    return md5(uniqid(time(), true));
}

/**
 * Verifica permissão de upload baseado no plano
 */
function canUploadImage($userId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Busca assinatura ativa
        $stmt = $conn->prepare("
            SELECT us.images_used, p.images_limit 
            FROM user_subscriptions us
            INNER JOIN plans p ON us.plan_id = p.id
            WHERE us.user_id = ? 
            AND us.status = 'active'
            AND us.end_date > NOW()
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            return false;
        }
        
        return ($subscription['images_used'] < $subscription['images_limit']);
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verifica permissão para criar encarte baseado no plano
 */
function canCreateFlyer($userId) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Busca assinatura ativa
        $stmt = $conn->prepare("
            SELECT us.flyers_used, p.flyers_limit 
            FROM user_subscriptions us
            INNER JOIN plans p ON us.plan_id = p.id
            WHERE us.user_id = ? 
            AND us.status = 'active'
            AND us.end_date > NOW()
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            return false;
        }
        
        return ($subscription['flyers_used'] < $subscription['flyers_limit']);
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Incrementa contador de uso na assinatura
 */
function incrementUsage($userId, $type = 'images') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $column = ($type === 'flyers') ? 'flyers_used' : 'images_used';
        
        $stmt = $conn->prepare("
            UPDATE user_subscriptions 
            SET {$column} = {$column} + 1 
            WHERE user_id = ? 
            AND status = 'active'
            AND end_date > NOW()
        ");
        $stmt->execute([$userId]);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtém URL base do sistema
 */
function getBaseUrl() {
    $envFile = __DIR__ . '/../.env.php';
    if (file_exists($envFile)) {
        $config = include $envFile;
        if (isset($config['BASE_URL'])) {
            return rtrim($config['BASE_URL'], '/');
        }
    }
    
    // Fallback: detecta automaticamente
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . rtrim($path, '/');
}
