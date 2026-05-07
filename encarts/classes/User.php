<?php
/**
 * /classes/User.php
 * Classe para CRUD de usuários
 * Operações de criação, leitura, atualização e exclusão de usuários
 */

class User {
    
    private PDO $db;
    
    /**
     * Construtor da classe User
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria um novo usuário
     * 
     * @param string $name Nome do usuário
     * @param string $email Email único
     * @param string $password Senha em texto puro
     * @param string $plan Plano (free/pro)
     * @return array ['success' => bool, 'message' => string, 'user_id' => ?int]
     */
    public function create(string $name, string $email, string $password, string $plan = 'free'): array {
        try {
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Email inválido.',
                    'user_id' => null
                ];
            }
            
            // Verificar se email já existe
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Este email já está cadastrado.',
                    'user_id' => null
                ];
            }
            
            // Validar senha (mínimo 6 caracteres)
            if (strlen($password) < 6) {
                return [
                    'success' => false,
                    'message' => 'A senha deve ter pelo menos 6 caracteres.',
                    'user_id' => null
                ];
            }
            
            // Hash da senha com bcrypt
            $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
            
            // Inserir usuário
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password_hash, plan)
                VALUES (:name, :email, :password_hash, :plan)
            ");
            $stmt->execute([
                'name' => htmlspecialchars(trim($name)),
                'email' => strtolower(trim($email)),
                'password_hash' => $passwordHash,
                'plan' => in_array($plan, ['free', 'pro']) ? $plan : 'free'
            ]);
            
            return [
                'success' => true,
                'message' => 'Usuário criado com sucesso!',
                'user_id' => (int)$this->db->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("User create error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
                'user_id' => null
            ];
        }
    }
    
    /**
     * Obtém um usuário pelo ID
     * 
     * @param int $id ID do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function getById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, plan, avatar_url, created_at, last_login, is_active
                FROM users
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("User getById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém um usuário pelo email
     * 
     * @param string $email Email do usuário
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function getByEmail(string $email): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, plan, avatar_url, created_at, last_login, is_active
                FROM users
                WHERE email = :email
            ");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("User getByEmail error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza dados do usuário
     * 
     * @param int $id ID do usuário
     * @param array $data Dados a serem atualizados
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $id, array $data): array {
        try {
            $allowedFields = ['name', 'avatar_url', 'plan'];
            $updates = [];
            $params = ['id' => $id];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $field === 'name' ? htmlspecialchars(trim($data[$field])) : $data[$field];
                }
            }
            
            if (empty($updates)) {
                return [
                    'success' => false,
                    'message' => 'Nenhum dado válido para atualizar.'
                ];
            }
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Dados atualizados com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("User update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Atualiza a senha do usuário
     * 
     * @param int $id ID do usuário
     * @param string $currentPassword Senha atual
     * @param string $newPassword Nova senha
     * @return array ['success' => bool, 'message' => string]
     */
    public function updatePassword(int $id, string $currentPassword, string $newPassword): array {
        try {
            // Buscar hash da senha atual
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuário não encontrado.'
                ];
            }
            
            // Verificar senha atual
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Senha atual incorreta.'
                ];
            }
            
            // Validar nova senha
            if (strlen($newPassword) < 6) {
                return [
                    'success' => false,
                    'message' => 'A nova senha deve ter pelo menos 6 caracteres.'
                ];
            }
            
            // Atualizar senha
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = :password_hash WHERE id = :id");
            $stmt->execute([
                'password_hash' => $passwordHash,
                'id' => $id
            ]);
            
            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("User updatePassword error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Deleta um usuário (soft delete - desativa)
     * 
     * @param int $id ID do usuário
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id): array {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Conta desativada com sucesso.'
            ];
            
        } catch (PDOException $e) {
            error_log("User delete error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Conta o número de encarts de um usuário
     * 
     * @param int $userId ID do usuário
     * @return int Número de encarts
     */
    public function countEncarts(int $userId): int {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM encarts WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("User countEncarts error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica o limite de encarts baseado no plano do usuário
     * 
     * @param int $userId ID do usuário
     * @return array ['plan' => string, 'limit' => int, 'used' => int, 'remaining' => int]
     */
    public function getPlanLimits(int $userId): array {
        $user = $this->getById($userId);
        
        if (!$user) {
            return [
                'plan' => 'free',
                'limit' => 5,
                'used' => 0,
                'remaining' => 5
            ];
        }
        
        $limits = [
            'free' => 5,
            'pro' => PHP_INT_MAX
        ];
        
        $limit = $limits[$user['plan']] ?? 5;
        $used = $this->countEncarts($userId);
        $remaining = max(0, $limit - $used);
        
        return [
            'plan' => $user['plan'],
            'limit' => $limit,
            'used' => $used,
            'remaining' => $remaining
        ];
    }
}
