<?php
/**
 * Plan.php
 * Classe para gerenciamento de Planos de Assinatura
 */

class Plan {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Lista todos os planos ativos
     */
    public function getAllActive(): array {
        $sql = "SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtém um plano por ID
     */
    public function getById(int $id): ?array {
        $sql = "SELECT * FROM plans WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Obtém um plano por slug
     */
    public function getBySlug(string $slug): ?array {
        $sql = "SELECT * FROM plans WHERE slug = :slug AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Cria um novo plano (apenas admin)
     */
    public function create(array $data): int {
        $sql = "INSERT INTO plans (name, slug, price, max_encarts, max_uploads, features, is_active) 
                VALUES (:name, :slug, :price, :max_encarts, :max_uploads, :features, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $data['slug'], PDO::PARAM_STR);
        $stmt->bindValue(':price', $data['price'] ?? 0.00, PDO::PARAM_STR);
        $stmt->bindValue(':max_encarts', $data['max_encarts'] ?? 3, PDO::PARAM_INT);
        $stmt->bindValue(':max_uploads', $data['max_uploads'] ?? 5, PDO::PARAM_INT);
        $stmt->bindValue(':features', json_encode($data['features'] ?? []), PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
        $stmt->execute();
        
        return (int) $this->db->lastInsertId();
    }
    
    /**
     * Atualiza um plano existente (apenas admin)
     */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE plans SET 
                name = :name,
                slug = :slug,
                price = :price,
                max_encarts = :max_encarts,
                max_uploads = :max_uploads,
                features = :features,
                is_active = :is_active
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $data['slug'], PDO::PARAM_STR);
        $stmt->bindValue(':price', $data['price'] ?? 0.00, PDO::PARAM_STR);
        $stmt->bindValue(':max_encarts', $data['max_encarts'] ?? 3, PDO::PARAM_INT);
        $stmt->bindValue(':max_uploads', $data['max_uploads'] ?? 5, PDO::PARAM_INT);
        $stmt->bindValue(':features', json_encode($data['features'] ?? []), PDO::PARAM_STR);
        $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Remove um plano (apenas admin)
     */
    public function delete(int $id): bool {
        $sql = "DELETE FROM plans WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    /**
     * Verifica limites do plano do usuário
     */
    public function checkLimits(int $userId, string $type): array {
        $sql = "SELECT p.*, u.id as user_id 
                FROM users u
                INNER JOIN plans p ON u.plan_id = p.id
                WHERE u.id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            return ['allowed' => false, 'message' => 'Plano não encontrado'];
        }
        
        $currentCount = 0;
        $limit = 0;
        $limitName = '';
        
        if ($type === 'encarts') {
            $countSql = "SELECT COUNT(*) as count FROM encarts WHERE user_id = :user_id";
            $limit = (int) $plan['max_encarts'];
            $limitName = 'encartes';
        } elseif ($type === 'uploads') {
            $countSql = "SELECT COUNT(*) as count FROM user_galleries WHERE user_id = :user_id";
            $limit = (int) $plan['max_uploads'];
            $limitName = 'uploads';
        } else {
            return ['allowed' => false, 'message' => 'Tipo inválido'];
        }
        
        $stmt = $this->db->prepare($countSql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentCount = (int) $result['count'];
        
        $allowed = $currentCount < $limit;
        
        return [
            'allowed' => $allowed,
            'current' => $currentCount,
            'limit' => $limit,
            'remaining' => max(0, $limit - $currentCount),
            'limit_name' => $limitName,
            'plan_name' => $plan['name'],
            'message' => $allowed 
                ? "Você pode criar mais {$limitName}" 
                : "Limite de {$limitName} atingido no plano {$plan['name']}"
        ];
    }
    
    /**
     * Obtém todas as estatísticas de uso dos planos
     */
    public function getPlanStats(): array {
        $sql = "SELECT 
                    p.id,
                    p.name,
                    p.slug,
                    p.price,
                    p.max_encarts,
                    p.max_uploads,
                    COUNT(u.id) as total_users,
                    SUM(CASE WHEN u.subscription_status = 'active' THEN 1 ELSE 0 END) as active_users
                FROM plans p
                LEFT JOIN users u ON p.id = u.plan_id
                GROUP BY p.id
                ORDER BY p.price ASC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
