<?php
/**
 * /classes/Encart.php
 * Classe para CRUD de encarts (posts/banners criados pelos usuários)
 * Operações de criação, leitura, atualização e exclusão de encarts
 */

class Encart {
    
    private PDO $db;
    
    /**
     * Construtor da classe Encart
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Cria um novo encart
     * 
     * @param int $userId ID do usuário proprietário
     * @param string $title Título do encart
     * @param array $canvasData Dados JSON do canvas
     * @param int $width Largura do canvas
     * @param int $height Altura do canvas
     * @param string $format Formato (post/story/banner/cover/custom)
     * @return array ['success' => bool, 'message' => string, 'encart_id' => ?int]
     */
    public function create(int $userId, string $title, array $canvasData, int $width, int $height, string $format = 'post'): array {
        try {
            // Validar dados do canvas
            if (empty($canvasData)) {
                return [
                    'success' => false,
                    'message' => 'Dados do canvas inválidos.',
                    'encart_id' => null
                ];
            }
            
            // Verificar limite do plano do usuário
            $userModel = new User();
            $limits = $userModel->getPlanLimits($userId);
            
            if ($limits['remaining'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Você atingiu o limite de encarts do seu plano. Faça upgrade para criar mais.',
                    'encart_id' => null
                ];
            }
            
            // Preparar dados
            $canvasJson = json_encode($canvasData, JSON_UNESCAPED_UNICODE);
            
            if ($canvasJson === false) {
                return [
                    'success' => false,
                    'message' => 'Erro ao processar dados do canvas.',
                    'encart_id' => null
                ];
            }
            
            // Inserir encart
            $stmt = $this->db->prepare("
                INSERT INTO encarts (user_id, title, canvas_data, width, height, format)
                VALUES (:user_id, :title, :canvas_data, :width, :height, :format)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'title' => htmlspecialchars(trim($title)),
                'canvas_data' => $canvasJson,
                'width' => $width,
                'height' => $height,
                'format' => in_array($format, ['post', 'story', 'banner', 'cover', 'custom']) ? $format : 'custom'
            ]);
            
            return [
                'success' => true,
                'message' => 'Encart criado com sucesso!',
                'encart_id' => (int)$this->db->lastInsertId()
            ];
            
        } catch (PDOException $e) {
            error_log("Encart create error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
                'encart_id' => null
            ];
        }
    }
    
    /**
     * Obtém um encart pelo ID
     * 
     * @param int $id ID do encart
     * @param bool $includeOwner Se deve incluir dados do proprietário
     * @return array|null Dados do encart ou null se não encontrado
     */
    public function getById(int $id, bool $includeOwner = false): ?array {
        try {
            $sql = "SELECT id, user_id, title, canvas_data, width, height, format, thumbnail_url, is_public, created_at, updated_at";
            
            if ($includeOwner) {
                $sql .= ", u.name as owner_name, u.email as owner_email";
            }
            
            $sql .= " FROM encarts e";
            
            if ($includeOwner) {
                $sql .= " LEFT JOIN users u ON e.user_id = u.id";
            }
            
            $sql .= " WHERE e.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch();
            
            if (!$result) {
                return null;
            }
            
            // Decodificar canvas_data
            $result['canvas_data'] = json_decode($result['canvas_data'], true);
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Encart getById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém todos os encarts de um usuário
     * 
     * @param int $userId ID do usuário
     * @param string $orderBy Ordenação (created_at/title/updated_at)
     * @param string $order Direção (ASC/DESC)
     * @param int $limit Limite de resultados
     * @param int $offset Offset para paginação
     * @return array Lista de encarts
     */
    public function getByUser(int $userId, string $orderBy = 'created_at', string $order = 'DESC', int $limit = 50, int $offset = 0): array {
        try {
            // Whitelist de colunas para ordenação
            $allowedOrder = ['created_at', 'title', 'updated_at'];
            $orderBy = in_array($orderBy, $allowedOrder) ? $orderBy : 'created_at';
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            
            $stmt = $this->db->prepare("
                SELECT id, user_id, title, width, height, format, thumbnail_url, is_public, created_at, updated_at
                FROM encarts
                WHERE user_id = :user_id
                ORDER BY $orderBy $order
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll();
            
            // Decodificar canvas_data parcialmente para preview
            foreach ($results as &$result) {
                $encartFull = $this->getById($result['id']);
                $result['canvas_data'] = $encartFull['canvas_data'] ?? null;
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Encart getByUser error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém encarts públicos para galeria
     * 
     * @param string $category Categoria/filtro
     * @param int $limit Limite de resultados
     * @return array Lista de encarts públicos
     */
    public function getPublic(string $category = '', int $limit = 20): array {
        try {
            $sql = "
                SELECT e.id, e.title, e.width, e.height, e.format, e.thumbnail_url, e.created_at,
                       u.name as owner_name
                FROM encarts e
                INNER JOIN users u ON e.user_id = u.id
                WHERE e.is_public = 1
            ";
            
            $params = [];
            
            if (!empty($category)) {
                $sql .= " AND e.format = :category";
                $params['category'] = $category;
            }
            
            $sql .= " ORDER BY e.created_at DESC LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Encart getPublic error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Atualiza um encart existente
     * 
     * @param int $id ID do encart
     * @param int $userId ID do usuário (para verificação de propriedade)
     * @param array $data Dados a serem atualizados
     * @return array ['success' => bool, 'message' => string]
     */
    public function update(int $id, int $userId, array $data): array {
        try {
            // Verificar propriedade
            $encart = $this->getById($id);
            
            if (!$encart) {
                return [
                    'success' => false,
                    'message' => 'Encart não encontrado.'
                ];
            }
            
            if ($encart['user_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este encart.'
                ];
            }
            
            $allowedFields = ['title', 'canvas_data', 'thumbnail_url', 'is_public'];
            $updates = [];
            $params = ['id' => $id];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = :$field";
                    
                    if ($field === 'canvas_data') {
                        $params[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                    } elseif ($field === 'title') {
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
            
            $sql = "UPDATE encarts SET " . implode(', ', $updates) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Encart atualizado com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("Encart update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Duplica um encart existente
     * 
     * @param int $id ID do encart original
     * @param int $userId ID do usuário
     * @param string $newTitle Novo título (opcional)
     * @return array ['success' => bool, 'message' => string, 'encart_id' => ?int]
     */
    public function duplicate(int $id, int $userId, string $newTitle = ''): array {
        try {
            $encart = $this->getById($id);
            
            if (!$encart) {
                return [
                    'success' => false,
                    'message' => 'Encart não encontrado.',
                    'encart_id' => null
                ];
            }
            
            // Verificar se é público ou se pertence ao usuário
            if (!$encart['is_public'] && $encart['user_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para duplicar este encart.',
                    'encart_id' => null
                ];
            }
            
            $title = !empty($newTitle) ? $newTitle : $encart['title'] . ' (Cópia)';
            
            return $this->create(
                $userId,
                $title,
                $encart['canvas_data'],
                $encart['width'],
                $encart['height'],
                $encart['format']
            );
            
        } catch (PDOException $e) {
            error_log("Encart duplicate error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.',
                'encart_id' => null
            ];
        }
    }
    
    /**
     * Deleta um encart
     * 
     * @param int $id ID do encart
     * @param int $userId ID do usuário (para verificação de propriedade)
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete(int $id, int $userId): array {
        try {
            // Verificar propriedade
            $encart = $this->getById($id);
            
            if (!$encart) {
                return [
                    'success' => false,
                    'message' => 'Encart não encontrado.'
                ];
            }
            
            if ($encart['user_id'] != $userId) {
                return [
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir este encart.'
                ];
            }
            
            $stmt = $this->db->prepare("DELETE FROM encarts WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Encart excluído com sucesso!'
            ];
            
        } catch (PDOException $e) {
            error_log("Encart delete error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro interno do servidor. Tente novamente mais tarde.'
            ];
        }
    }
    
    /**
     * Conta o número total de encarts de um usuário
     * 
     * @param int $userId ID do usuário
     * @return int Número de encarts
     */
    public function countByUser(int $userId): int {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM encarts WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Encart countByUser error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Busca encarts por termo no título
     * 
     * @param int $userId ID do usuário
     * @param string $search Termo de busca
     * @return array Lista de encarts encontrados
     */
    public function search(int $userId, string $search): array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, user_id, title, width, height, format, thumbnail_url, is_public, created_at, updated_at
                FROM encarts
                WHERE user_id = :user_id
                AND title LIKE :search
                ORDER BY created_at DESC
            ");
            $stmt->execute([
                'user_id' => $userId,
                'search' => '%' . trim($search) . '%'
            ]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Encart search error: " . $e->getMessage());
            return [];
        }
    }
}
