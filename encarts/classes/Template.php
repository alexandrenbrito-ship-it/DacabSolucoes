<?php
/**
 * /classes/Template.php
 * Classe para CRUD de templates pré-definidos
 * Operações de leitura e criação de templates para encarts
 */

class Template {
    
    private PDO $db;
    
    /**
     * Construtor da classe Template
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtém todos os templates disponíveis
     * 
     * @param string|null $category Filtro por categoria
     * @param bool $includePremium Incluir templates premium
     * @param string $orderBy Ordenação
     * @return array Lista de templates
     */
    public function getAll(?string $category = null, bool $includePremium = true, string $orderBy = 'order_position'): array {
        try {
            $sql = "SELECT id, name, category, canvas_data, thumbnail_url, is_premium, order_position, created_at FROM templates";
            
            $params = [];
            $where = [];
            
            if ($category !== null && !empty($category)) {
                $where[] = "category = :category";
                $params['category'] = $category;
            }
            
            if (!$includePremium) {
                $where[] = "is_premium = 0";
            }
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            // Whitelist de ordenação
            $allowedOrder = ['order_position', 'name', 'created_at'];
            $orderBy = in_array($orderBy, $allowedOrder) ? $orderBy : 'order_position';
            
            $sql .= " ORDER BY $orderBy ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll();
            
            // Decodificar canvas_data de cada template
            foreach ($results as &$result) {
                $result['canvas_data'] = json_decode($result['canvas_data'], true);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Template getAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém um template pelo ID
     * 
     * @param int $id ID do template
     * @return array|null Dados do template ou null se não encontrado
     */
    public function getById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, category, canvas_data, thumbnail_url, is_premium, order_position, created_at
                FROM templates
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return null;
            }
            
            // Decodificar canvas_data
            $result['canvas_data'] = json_decode($result['canvas_data'], true);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Template getById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém templates por categoria
     * 
     * @param string $category Categoria do template
     * @param bool $includePremium Incluir templates premium
     * @return array Lista de templates da categoria
     */
    public function getByCategory(string $category, bool $includePremium = true): array {
        return $this->getAll($category, $includePremium, 'order_position');
    }
    
    /**
     * Obtém todas as categorias disponíveis
     * 
     * @return array Lista de categorias com contagem de templates
     */
    public function getCategories(): array {
        try {
            $stmt = $this->db->query("
                SELECT category, COUNT(*) as count
                FROM templates
                GROUP BY category
                ORDER BY category ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Template getCategories error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cria um novo template (apenas admin)
     * 
     * @param string $name Nome do template
     * @param string $category Categoria
     * @param array $canvasData Dados do canvas
     * @param bool $isPremium É premium?
     * @param int $orderPosition Posição na ordenação
     * @return array ['success' => bool, 'message' => string, 'template_id' => ?int]
     */
    public function create(string $name, string $category, array $canvasData, bool $isPremium = false, int $orderPosition = 0): array {
        try {
            // Validar categoria
            $allowedCategories = ['post', 'story', 'banner', 'cover', 'promo', 'minimal', 'business'];
            if (!in_array($category, $allowedCategories)) {
                return [
                    'success' => false,
                    'message' => 'Categoria inválida.',
                    'template_id' => null
                ];
            }
            
            // Validar canvas_data
            if (empty($canvasData)) {
                return [
                    'success' => false,
                    'message' => 'Dados do canvas inválidos.',
                    'template_id' => null
                ];
            }
            
            $canvasJson = json_encode($canvasData, JSON_UNESCAPED_UNICODE);
            
            if ($canvasJson === false) {
                return [
                    'success' => false,
                    'message' => 'Erro ao processar dados do canvas.',
                    'template_id' => null
                ];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO templates (name, category, canvas_data, is_premium, order_position)
                VALUES (:name, :category, :canvas_data, :is_premium, :order_position)
            ");
            $stmt->execute([
                'name' => htmlspecialchars(trim($name)),
                'category' => $category,
                'canvas_data' => $canvasJson,
                'is_premium' => $isPremium ? 1 : 0,
                'order_position' => $orderPosition
            ]);
            
            return [
                'success' => true,
                'message' => 'Template criado com sucesso!',
                'template_id' => (int)$this->db->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("Template create error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
                'template_id' => null
            ];
        }
    }
    
    /**
     * Atualiza um template existente
     * 
     * @param int $id ID do template
     * @param array $data Dados a serem atualizados
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $id, array $data): array {
        try {
            $allowedFields = ['name', 'category', 'canvas_data', 'thumbnail_url', 'is_premium', 'order_position'];
            $updates = [];
            $params = ['id' => $id];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    
                    if ($field === 'canvas_data') {
                        $params[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                    } elseif ($field === 'name') {
                        $params[$field] = htmlspecialchars(trim($data[$field]));
                    } elseif ($field === 'category') {
                        $allowedCategories = ['post', 'story', 'banner', 'cover', 'promo', 'minimal', 'business'];
                        if (!in_array($data[$field], $allowedCategories)) {
                            continue;
                        }
                        $params[$field] = $data[$field];
                    } elseif (in_array($field, ['is_premium', 'order_position'])) {
                        $params[$field] = $data[$field];
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
            
            $sql = "UPDATE templates SET " . implode(', ', $updates) . ", created_at = created_at WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Template atualizado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("Template update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Deleta um template
     * 
     * @param int $id ID do template
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM templates WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Template excluído com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("Template delete error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Busca templates por termo no nome
     * 
     * @param string $search Termo de busca
     * @param bool $includePremium Incluir templates premium
     * @return array Lista de templates encontrados
     */
    public function search(string $search, bool $includePremium = true): array {
        try {
            $sql = "
                SELECT id, name, category, canvas_data, thumbnail_url, is_premium, order_position, created_at
                FROM templates
                WHERE name LIKE :search
            ";
            
            if (!$includePremium) {
                $sql .= " AND is_premium = 0";
            }
            
            $sql .= " ORDER BY order_position ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['search' => '%' . trim($search) . '%']);
            
            $results = $stmt->fetchAll();
            
            // Decodificar canvas_data
            foreach ($results as &$result) {
                $result['canvas_data'] = json_decode($result['canvas_data'], true);
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Template search error: " . $e->getMessage());
            return [];
        }
    }
}
