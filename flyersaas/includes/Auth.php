<?php
/**
 * Classe de Autenticação e Gerenciamento de Usuários
 */
class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registrar novo usuário
     */
    public function register($data) {
        try {
            $this->db->beginTransaction();

            // Verificar se e-mail já existe
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                throw new Exception("E-mail já cadastrado");
            }

            // Hash da senha
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Inserir usuário
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, store_name, phone, cnpj, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                sanitize($data['name']),
                filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                $passwordHash,
                sanitize($data['store_name'] ?? ''),
                sanitize($data['phone'] ?? ''),
                sanitize($data['cnpj'] ?? '')
            ]);

            $userId = $this->db->lastInsertId();

            // Atribuir plano básico por padrão (ou plano selecionado)
            $planId = $data['plan_id'] ?? 1;
            $startDate = date('Y-m-d H:i:s');
            $endDate = date('Y-m-d H:i:s', strtotime('+30 days'));

            $stmt = $this->db->prepare("
                INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, images_used, flyers_used, status)
                VALUES (?, ?, ?, ?, 0, 0, 'active')
            ");
            $stmt->execute([$userId, $planId, $startDate, $endDate]);

            $this->db->commit();
            return ['success' => true, 'user_id' => $userId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Login do usuário
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([filter_var($email, FILTER_SANITIZE_EMAIL)]);
            $user = $stmt->fetch();

            if (!$user) {
                return ['success' => false, 'error' => 'Usuário não encontrado ou inativo'];
            }

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Senha incorreta'];
            }

            // Regenerar ID da sessão para segurança
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['logged_in'] = true;

            return ['success' => true, 'user' => $user];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Logout
     */
    public function logout() {
        session_unset();
        session_destroy();
        session_start();
    }

    /**
     * Verificar se está logado
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Verificar se é admin
     */
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    /**
     * Redirecionar se não estiver logado
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            redirect(BASE_URL . '/login.php');
        }
    }

    /**
     * Redirecionar se não for admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            redirect(BASE_URL . '/user/dashboard.php');
        }
    }

    /**
     * Obter dados do usuário logado
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    /**
     * Obter assinatura atual do usuário
     */
    public function getCurrentSubscription($userId = null) {
        $userId = $userId ?? $_SESSION['user_id'];
        
        $stmt = $this->db->prepare("
            SELECT us.*, p.name as plan_name, p.price, p.images_limit, p.flyers_limit, p.duration_days
            FROM user_subscriptions us
            JOIN plans p ON us.plan_id = p.id
            WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW()
            ORDER BY us.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Atualizar perfil do usuário
     */
    public function updateProfile($userId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET name = ?, store_name = ?, phone = ?, cnpj = ?
                WHERE id = ?
            ");
            $stmt->execute([
                sanitize($data['name']),
                sanitize($data['store_name']),
                sanitize($data['phone']),
                sanitize($data['cnpj']),
                $userId
            ]);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Alterar senha
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'error' => 'Senha atual incorreta'];
            }

            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$passwordHash, $userId]);

            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
