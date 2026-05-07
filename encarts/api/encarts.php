<?php
/**
 * /api/encarts.php
 * API para CRUD de encarts
 * GET - Listar encarts do usuário ou buscar por ID
 * POST - Criar novo encart
 * PUT - Atualizar encart existente
 * DELETE - Excluir encart
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar classes necessárias
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/ClerkAuth.php';
require_once dirname(__DIR__) . '/classes/Encart.php';
require_once dirname(__DIR__) . '/classes/User.php';

$encartModel = new Encart();
$userModel = new User();

// Verificar autenticação para todas as ações exceto GET público
$action = $_GET['action'] ?? '';

// Permitir GET sem auth apenas para ação 'public'
if ($action !== 'public') {
    $user = ClerkAuth::requireAuth();
    $userId = (int)$user['id'];
} else {
    $userId = 0;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGet($encartModel, $userId, $action);
        break;
        
    case 'POST':
        handlePost($encartModel, $userModel, $userId);
        break;
        
    case 'PUT':
        handlePut($encartModel, $userId);
        break;
        
    case 'DELETE':
        handleDelete($encartModel, $userId);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}

/**
 * Handler para requisições GET
 */
function handleGet(Encart $encartModel, int $userId, string $action): void {
    switch ($action) {
        case 'public':
            // Lista encarts públicos (galeria)
            $category = $_GET['category'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 20), 50);
            $encarts = $encartModel->getPublic($category, $limit);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $encarts,
                'count' => count($encarts)
            ]);
            break;
            
        case 'search':
            // Busca encarts do usuário por termo
            $term = trim($_GET['q'] ?? '');
            if (empty($term)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Termo de busca obrigatório.']);
                return;
            }
            $encarts = $encartModel->search($userId, $term);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $encarts,
                'count' => count($encarts)
            ]);
            break;
            
        case 'single':
        default:
            // Obter encart específico ou lista do usuário
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
            
            if ($id !== null) {
                // Buscar encart específico
                $encart = $encartModel->getById($id, true);
                
                if (!$encart) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Encart não encontrado.']);
                    return;
                }
                
                // Verificar permissão (se não for público e não for dono)
                if (!$encart['is_public'] && $encart['user_id'] != $userId) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
                    return;
                }
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $encart
                ]);
            } else {
                // Listar encarts do usuário
                $orderBy = $_GET['orderBy'] ?? 'created_at';
                $order = $_GET['order'] ?? 'DESC';
                $limit = min((int)($_GET['limit'] ?? 50), 100);
                $offset = max(0, (int)($_GET['offset'] ?? 0));
                
                $encarts = $encartModel->getByUser($userId, $orderBy, $order, $limit, $offset);
                $total = $encartModel->countByUser($userId);
                
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'data' => $encarts,
                    'pagination' => [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'hasMore' => ($offset + $limit) < $total
                    ]
                ]);
            }
            break;
    }
}

/**
 * Handler para requisições POST (criar)
 */
function handlePost(Encart $encartModel, User $userModel, int $userId): void {
    // Ler JSON do body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        return;
    }
    
    // Verificar limite do plano
    $limits = $userModel->getPlanLimits($userId);
    if ($limits['remaining'] <= 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Você atingiu o limite de encarts do seu plano.',
            'plan' => $limits['plan'],
            'used' => $limits['used'],
            'limit' => $limits['limit']
        ]);
        return;
    }
    
    // Validar dados obrigatórios
    $title = trim($input['title'] ?? '');
    $canvasData = $input['canvas_data'] ?? null;
    $width = (int)($input['width'] ?? 1080);
    $height = (int)($input['height'] ?? 1080);
    $format = trim($input['format'] ?? 'post');
    
    if (empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Título é obrigatório.']);
        return;
    }
    
    if (!is_array($canvasData) || empty($canvasData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados do canvas são obrigatórios.']);
        return;
    }
    
    $result = $encartModel->create($userId, $title, $canvasData, $width, $height, $format);
    
    if (!$result['success']) {
        http_response_code(400);
    } else {
        http_response_code(201);
    }
    
    echo json_encode($result);
}

/**
 * Handler para requisições PUT (atualizar)
 */
function handlePut(Encart $encartModel, int $userId): void {
    // Ler JSON do body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        return;
    }
    
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do encart é obrigatório.']);
        return;
    }
    
    // Preparar dados para atualização
    $data = [];
    
    if (isset($input['title'])) {
        $data['title'] = trim($input['title']);
    }
    
    if (isset($input['canvas_data']) && is_array($input['canvas_data'])) {
        $data['canvas_data'] = $input['canvas_data'];
    }
    
    if (isset($input['thumbnail_url'])) {
        $data['thumbnail_url'] = $input['thumbnail_url'];
    }
    
    if (isset($input['is_public'])) {
        $data['is_public'] = (bool)$input['is_public'];
    }
    
    if (empty($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nenhum dado válido para atualizar.']);
        return;
    }
    
    $result = $encartModel->update($id, $userId, $data);
    
    if (!$result['success']) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }
    
    echo json_encode($result);
}

/**
 * Handler para requisições DELETE (excluir)
 */
function handleDelete(Encart $encartModel, int $userId): void {
    // Tentar obter ID da query string ou do body
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($id === null) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
    }
    
    $id = (int)$id;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID do encart é obrigatório.']);
        return;
    }
    
    $result = $encartModel->delete($id, $userId);
    
    if (!$result['success']) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }
    
    echo json_encode($result);
}
