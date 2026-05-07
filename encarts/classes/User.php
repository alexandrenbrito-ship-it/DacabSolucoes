<?php
/**
 * /classes/User.php
 * Classe para CRUD de usuários com integração Clerk e sistema de planos/roles
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
     * Cria um novo usuário via Clerk (sincronização automática)
     * 
     * @param string $clerkUserId ID do usuário no Clerk
     * @param string $email Email do usuário
     * @param string $name Nome do usuário
     * @param string|null $avatarUrl URL do avatar
     * @return array ['success' => bool, 'message' => string, 'user_id' => ?int]
     */
    public function createFromClerk(string $clerkUserId, string $email, string $name, ?string $avatarUrl = null): array {
        try {
            // Verificar se usuário já existe pelo clerk_user_id
            $stmt = $this->db->prepare("SELECT id FROM users WHERE clerk_user_id = :clerk_user_id");
            $stmt->execute(['clerk_user_id' => $clerkUserId]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => true,
                    'message' => 'Usuário já existe.',
                    'user_id' => (int)$stmt->fetchColumn(),
                    'created' => false
                ];
            }
            
            // Inserir usuário com plano free e role user por padrão
            $stmt = $this->db->prepare("
                INSERT INTO users (clerk_user_id, clerk_email, name, email, role_id, plan_id, avatar_url)
                VALUES (:clerk_user_id, :clerk_email, :name, :email, 2, 1, :avatar_url)
            ");
            $stmt->execute([
                'clerk_user_id' => $clerkUserId,
                'clerk_email' => strtolower(trim($email)),
                'name' => htmlspecialchars(trim($name)),
                'email' => strtolower(trim($email)),
                'avatar_url' => $avatarUrl
            ]);
            
            return [
                'success' => true,
                'message' => 'Usuário criado com sucesso!',
                'user_id' => (int)$this->db->lastInsertId(),
                'created' => true
            ];
            
        } catch (PDOException $e) {
            error_log("User createFromClerk error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
                'user_id' => null,
                'created' => false
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
                SELECT u.*, r.name as role_name, p.name as plan_name, p.slug as plan_slug,
                       p.max_encarts, p.max_uploads
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE u.id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("User getById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém um usuário pelo Clerk ID
     * 
     * @param string $clerkUserId ID do usuário no Clerk
     * @return array|null Dados do usuário ou null se não encontrado
     */
    public function getByClerkId(string $clerkUserId): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name, p.name as plan_name, p.slug as plan_slug,
                       p.max_encarts, p.max_uploads
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE u.clerk_user_id = :clerk_user_id
            ");
            $stmt->execute(['clerk_user_id' => $clerkUserId]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("User getByClerkId error: " . $e->getMessage());
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
                SELECT u.*, r.name as role_name, p.name as plan_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN plans p ON u.plan_id = p.id
                WHERE u.email = :email
            ");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch() ?: null;
        } catch (PDOException $e) {
            error_log("User getByEmail error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza dados do usuário (apenas admin ou o próprio usuário)
     * 
     * @param int $id ID do usuário
     * @param array $data Dados a serem atualizados
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $id, array $data): array {
        try {
            $allowedFields = ['name', 'avatar_url', 'role_id', 'plan_id', 'subscription_status', 'subscription_expires_at'];
            $updates = [];
            $params = ['id' => $id];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    if ($field === 'name') {
                        $params[$field] = htmlspecialchars(trim($data[$field]));
                    } else {
                        $params[$field] = $data[$field];
                    }
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
     * Atualiza o plano do usuário (apenas admin)
     * 
     * @param int $userId ID do usuário
     * @param int $planId ID do novo plano
     * @return array ['success' => bool, 'message' => string]
     */
    public function updatePlan(int $userId, int $planId): array {
        try {
            // Verificar se plano existe
            $stmt = $this->db->prepare("SELECT id FROM plans WHERE id = :id AND is_active = 1");
            $stmt->execute(['id' => $planId]);
            
            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Plano inválido ou inativo.'
                ];
            }
            
            $stmt = $this->db->prepare("UPDATE users SET plan_id = :plan_id, subscription_status = 'active' WHERE id = :id");
            $stmt->execute([
                'plan_id' => $planId,
                'id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'Plano atualizado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("User updatePlan error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Atualiza o role do usuário (apenas admin)
     * 
     * @param int $userId ID do usuário
     * @param int $roleId ID do novo role
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateRole(int $userId, int $roleId): array {
        try {
            // Verificar se role existe
            $stmt = $this->db->prepare("SELECT id FROM roles WHERE id = :id");
            $stmt->execute(['id' => $roleId]);
            
            if (!$stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Perfil inválido.'
                ];
            }
            
            $stmt = $this->db->prepare("UPDATE users SET role_id = :role_id WHERE id = :id");
            $stmt->execute([
                'role_id' => $roleId,
                'id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'Perfil atualizado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("User updateRole error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Desativa um usuário (apenas admin)
     * 
     * @param int $id ID do usuário
     * @return array ['success' => bool, 'message' => string]
     */
    public function deactivate(int $id): array {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Usuário desativado com sucesso.'
            ];
            
        } catch (PDOException $e) {
            error_log("User deactivate error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Lista todos os usuários com paginação
     * 
     * @param int $page Página atual
     * @param int $limit Registros por página
     * @return array ['users' => array, 'total' => int, 'pages' => int]
     */
    public function getAll(int $page = 1, int $limit = 20): array {
        try {
            $offset = ($page - 1) * $limit;
            
            // Total de usuários
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM users");
            $total = (int) $stmt->fetch()['total'];
            $pages = ceil($total / $limit);
            
            // Lista de usuários com contagem de encarts
            $stmt = $this->db->prepare("
                SELECT u.*, r.name as role_name, p.name as plan_name, p.slug as plan_slug,
                       (SELECT COUNT(*) FROM encarts WHERE user_id = u.id) as encarts_count
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN plans p ON u.plan_id = p.id
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'users' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'pages' => $pages
            ];
            
        } catch (PDOException $e) {
            error_log("User getAll error: " . $e->getMessage());
            return [
                'users' => [],
                'total' => 0,
                'pages' => 0
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
     * Conta o número de uploads na galeria pessoal do usuário
     * 
     * @param int $userId ID do usuário
     * @return int Número de uploads
     */
    public function countUploads(int $userId): int {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM user_galleries WHERE user_id = :user_id AND is_active = 1");
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("User countUploads error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica os limites do plano do usuário
     * 
     * @param int $userId ID do usuário
     * @return array ['plan' => string, 'max_encarts' => int, 'max_uploads' => int, 'used_encarts' => int, 'used_uploads' => int, 'remaining_encarts' => int, 'remaining_uploads' => int]
     */
    public function getPlanLimits(int $userId): array {
        $user = $this->getById($userId);
        
        if (!$user) {
            return [
                'plan' => 'free',
                'max_encarts' => 3,
                'max_uploads' => 5,
                'used_encarts' => 0,
                'used_uploads' => 0,
                'remaining_encarts' => 3,
                'remaining_uploads' => 5
            ];
        }
        
        $usedEncarts = $this->countEncarts($userId);
        $usedUploads = $this->countUploads($userId);
        
        return [
            'plan' => $user['plan_slug'] ?? 'free',
            'plan_name' => $user['plan_name'] ?? 'Gratuito',
            'max_encarts' => (int)($user['max_encarts'] ?? 3),
            'max_uploads' => (int)($user['max_uploads'] ?? 5),
            'used_encarts' => $usedEncarts,
            'used_uploads' => $usedUploads,
            'remaining_encarts' => max(0, (int)($user['max_encarts'] ?? 3) - $usedEncarts),
            'remaining_uploads' => max(0, (int)($user['max_uploads'] ?? 5) - $usedUploads)
        ];
    }
    
    /**
     * Verifica se o usuário é administrador
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    public function isAdmin(int $userId): bool {
        $user = $this->getById($userId);
        return $user && strtolower($user['role_name'] ?? '') === 'admin';
    }
    
    /**
     * Verifica se o usuário tem role específico
     * 
     * @param int $userId ID do usuário
     * @param string $roleName Nome do role (admin, user, editor)
     * @return bool
     */
    public function hasRole(int $userId, string $roleName): bool {
        $user = $this->getById($userId);
        return $user && strtolower($user['role_name'] ?? '') === strtolower($roleName);
    }
}
