<?php
/**
 * FlyerSaaS - Funções Auxiliares
 */

/**
 * Verifica se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Verifica se o usuário é administrador
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Redireciona usuário não logado para login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/login.php');
    }
}

/**
 * Redireciona usuário não administrador para dashboard
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

/**
 * Obtém dados do usuário logado
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Obtém assinatura ativa do usuário
 */
function getUserSubscription($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return null;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT us.*, p.name as plan_name, p.price, p.images_limit, p.flyers_limit, p.duration_days
            FROM user_subscriptions us
            JOIN plans p ON us.plan_id = p.id
            WHERE us.user_id = ? 
            AND us.status = 'active'
            AND us.end_date > NOW()
            ORDER BY us.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Verifica se usuário pode fazer upload de imagem
 */
function canUploadImage($userId = null) {
    $subscription = getUserSubscription($userId);
    
    if (!$subscription) {
        return false;
    }
    
    return $subscription['images_used'] < $subscription['images_limit'];
}

/**
 * Verifica se usuário pode criar encarte
 */
function canCreateFlyer($userId = null) {
    $subscription = getUserSubscription($userId);
    
    if (!$subscription) {
        return false;
    }
    
    return $subscription['flyers_used'] < $subscription['flyers_limit'];
}

/**
 * Incrementa contador de imagens usadas
 */
function incrementImagesUsed($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE user_subscriptions 
            SET images_used = images_used + 1 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date > NOW()
        ");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Incrementa contador de encartes usados
 */
function incrementFlyersUsed($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            UPDATE user_subscriptions 
            SET flyers_used = flyers_used + 1 
            WHERE user_id = ? 
            AND status = 'active' 
            AND end_date > NOW()
        ");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gera hash MD5 de um arquivo
 */
function getFileHash($filePath) {
    return md5_file($filePath);
}

/**
 * Formata valor monetário
 */
function formatCurrency($value) {
    return number_format($value, 2, ',', '.');
}

/**
 * Formata data
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Trunca texto
 */
function truncate($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Valida e-mail
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitiza nome de arquivo
 */
function sanitizeFileName($name) {
    // Remove caracteres especiais
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    // Remove múltiplos underscores
    $name = preg_replace('/_+/', '_', $name);
    return strtolower($name);
}

/**
 * Obtém configuração do sistema
 */
function getConfig($key, $default = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Atualiza configuração do sistema
 */
function setConfig($key, $value, $description = null) {
    try {
        $db = Database::getInstance()->getConnection();
        
        if ($description) {
            $stmt = $db->prepare("
                INSERT INTO system_config (config_key, config_value, description)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), description = VALUES(description)
            ");
            return $stmt->execute([$key, $value, $description]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO system_config (config_key, config_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
            ");
            return $stmt->execute([$key, $value]);
        }
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Envia mensagem flash
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Obtém e limpa mensagem flash
 */
function getFlashMessage($type = null) {
    if ($type) {
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    
    $messages = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $messages;
}

/**
 * Verifica se sistema está em manutenção
 */
function isMaintenanceMode() {
    return getConfig('maintenance_mode', '0') === '1';
}

/**
 * Redireciona se sistema estiver em manutenção (exceto para admin)
 */
function checkMaintenanceMode() {
    if (isMaintenanceMode() && !isAdmin()) {
        setFlashMessage('warning', 'Sistema em manutenção. Tente novamente mais tarde.');
        redirect(BASE_URL . '/login.php');
    }
}
