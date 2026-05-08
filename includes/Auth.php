<?php
/**
 * Sistema de Autenticação
 * Gerencia login, logout e verificação de sessão
 */
class Auth {
    
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function login($email, $password) {
        self::startSession();
        
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = (bool) $user['is_admin'];
                $_SESSION['last_activity'] = time();
                
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'error' => 'E-mail ou senha inválidos.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao conectar com banco de dados.'];
        }
    }
    
    public static function logout() {
        self::startSession();
        session_unset();
        session_destroy();
        session_start(); // Inicia nova sessão vazia
    }
    
    public static function check() {
        self::startSession();
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verifica timeout de sessão (30 minutos)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            self::logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function user() {
        self::startSession();
        
        if (!self::check()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'is_admin' => $_SESSION['is_admin'] ?? false
        ];
    }
    
    public static function isAdmin() {
        self::startSession();
        return self::check() && ($_SESSION['is_admin'] ?? false);
    }
    
    public static function requireLogin() {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /user/dashboard.php');
            exit;
        }
    }
    
    public static function generateCSRFToken() {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
