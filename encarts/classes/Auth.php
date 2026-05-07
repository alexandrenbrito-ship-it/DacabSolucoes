<?php
/**
 * /classes/Auth.php
 * Classe para autenticação e gerenciamento de sessões
 * Implementa login, logout, verificação de sessão e rate limiting
 */

class Auth {
    
    private PDO $db;
    private int $sessionLifetime;
    private int $rateLimitAttempts;
    private int $rateLimitWindow;
    
    /**
     * Construtor da classe Auth
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->sessionLifetime = (int)(getenv('SESSION_LIFETIME') ?: 24);
        $this->rateLimitAttempts = (int)(getenv('RATE_LIMIT_ATTEMPTS') ?: 10);
        $this->rateLimitWindow = (int)(getenv('RATE_LIMIT_WINDOW') ?: 15);
    }
    
    /**
     * Realiza login do usuário com email e senha
     * 
     * @param string $email Email do usuário
     * @param string $password Senha em texto puro
     * @return array ['success' => bool, 'message' => string, 'user' => ?array]
     */
    public function login(string $email, string $password): array {
        // Verificar rate limiting por IP
        if ($this->isRateLimited()) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas de login. Aguarde alguns minutos.',
                'user' => null
            ];
        }
        
        try {
            // Buscar usuário pelo email
            $stmt = $this->db->prepare("
                SELECT id, name, email, password_hash, plan, avatar_url, is_active 
                FROM users 
                WHERE email = :email 
                AND is_active = 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Email ou senha inválidos.',
                    'user' => null
                ];
            }
            
            // Verificar senha com password_verify
            if (!password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Email ou senha inválidos.',
                    'user' => null
                ];
            }
            
            // Gerar token de sessão seguro
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->sessionLifetime} hours"));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Criar sessão no banco de dados
            $stmt = $this->db->prepare("
                INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at)
                VALUES (:user_id, :token, :ip_address, :user_agent, :expires_at)
            ");
            $stmt->execute([
                'user_id' => $user['id'],
                'token' => $token,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'expires_at' => $expiresAt
            ]);
            
            // Atualizar last_login
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $stmt->execute(['id' => $user['id']]);
            
            // Remover dados sensíveis
            unset($user['password_hash']);
            
            // Setar cookie com o token
            setcookie('auth_token', $token, [
                'expires' => strtotime("+{$this->sessionLifetime} hours"),
                'path' => '/',
                'secure' => getenv('APP_ENV') === 'production',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso!',
                'user' => $user,
                'token' => $token
            ];
            
        } catch (PDOException $e) {
            error_log("Auth login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
                'user' => null
            ];
        }
    }
    
    /**
     * Realiza logout do usuário invalidando a sessão
     * 
     * @return bool
     */
    public function logout(): bool {
        if (!isset($_COOKIE['auth_token'])) {
            return false;
        }
        
        try {
            $token = $_COOKIE['auth_token'];
            
            // Invalidar token no banco
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE token = :token");
            $stmt->execute(['token' => $token]);
            
            // Remover cookie
            setcookie('auth_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => getenv('APP_ENV') === 'production',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Auth logout error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se o usuário está autenticado
     * 
     * @return bool
     */
    public function isAuthenticated(): bool {
        return $this->getCurrentUser() !== null;
    }
    
    /**
     * Obtém o usuário atualmente autenticado
     * 
     * @return array|null Dados do usuário ou null se não autenticado
     */
    public function getCurrentUser(): ?array {
        if (!isset($_COOKIE['auth_token'])) {
            return null;
        }
        
        try {
            $token = $_COOKIE['auth_token'];
            
            // Buscar sessão válida
            $stmt = $this->db->prepare("
                SELECT s.user_id, s.expires_at, u.id, u.name, u.email, u.plan, u.avatar_url, u.is_active
                FROM sessions s
                INNER JOIN users u ON s.user_id = u.id
                WHERE s.token = :token
                AND s.expires_at > NOW()
                AND u.is_active = 1
            ");
            $stmt->execute(['token' => $token]);
            $session = $stmt->fetch();
            
            if (!$session) {
                return null;
            }
            
            // Remover campos internos
            unset($session['user_id'], $session['expires_at']);
            
            return $session;
            
        } catch (PDOException $e) {
            error_log("Auth getCurrentUser error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica se o IP atual está limitado por rate limiting
     * 
     * @return bool
     */
    private function isRateLimited(): bool {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $windowStart = date('Y-m-d H:i:s', strtotime("-{$this->rateLimitWindow} minutes"));
        
        try {
            // Contar tentativas de login falhas recentes
            // Nota: Em produção, considere usar APCu ou Redis para melhor performance
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as attempts
                FROM sessions
                WHERE ip_address = :ip_address
                AND created_at > :window_start
                AND user_id NOT IN (
                    SELECT id FROM users WHERE is_active = 1
                )
            ");
            $stmt->execute([
                'ip_address' => $ipAddress,
                'window_start' => $windowStart
            ]);
            $result = $stmt->fetch();
            
            return $result['attempts'] >= $this->rateLimitAttempts;
            
        } catch (PDOException $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return false; // Falhar de forma segura
        }
    }
    
    /**
     * Registra uma tentativa de login falha para rate limiting
     * 
     * @param string $email
     */
    public function logFailedAttempt(string $email): void {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $expiresAt = date('Y-m-d H:i:s', strtotime("+1 hour"));
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sessions (user_id, token, ip_address, user_agent, expires_at)
                VALUES (0, :token, :ip_address, :user_agent, :expires_at)
            ");
            $stmt->execute([
                'token' => bin2hex(random_bytes(16)),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'expires_at' => $expiresAt
            ]);
        } catch (PDOException $e) {
            error_log("Failed attempt logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Gera um token CSRF para proteção de formulários
     * 
     * @return string
     */
    public function generateCsrfToken(): string {
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida um token CSRF
     * 
     * @param string $token Token a ser validado
     * @return bool
     */
    public function validateCsrfToken(string $token): bool {
        if (!isset($_SESSION)) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Redireciona para a página de login se não estiver autenticado
     * 
     * @param string $redirectUrl URL para redirecionar após login
     */
    public function requireAuth(string $redirectUrl = '/encarts/'): void {
        if (!$this->isAuthenticated()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Retorna usuário como JSON para APIs
     * 
     * @return array
     */
    public function getUserData(): array {
        $user = $this->getCurrentUser();
        
        if ($user === null) {
            return ['authenticated' => false];
        }
        
        return [
            'authenticated' => true,
            'user' => $user
        ];
    }
}
