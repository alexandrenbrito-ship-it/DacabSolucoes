<?php
/**
 * /api/admin.php
 * API de Administração - Gerenciamento de usuários, planos e galeria pública
 * Apenas administradores podem acessar
 */

require_once '../config/database.php';
require_once '../classes/ClerkAuth.php';
require_once '../classes/User.php';
require_once '../classes/Plan.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Autenticar usuário
    $user = ClerkAuth::requireAuth();
    
    // Verificar se é admin
    $userModel = new User();
    if (!$userModel->isAdmin($user['id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores.']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_users':
            // Listar todos os usuários
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $result = $userModel->getAll($page, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_user':
            // Obter detalhes de um usuário
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) {
                throw new Exception('ID do usuário não informado');
            }
            $userData = $userModel->getById($userId);
            if (!$userData) {
                throw new Exception('Usuário não encontrado');
            }
            echo json_encode(['success' => true, 'data' => $userData]);
            break;
            
        case 'update_role':
            // Atualizar role do usuário
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($data['user_id'] ?? 0);
            $roleId = (int)($data['role_id'] ?? 0);
            
            if (!$userId || !$roleId) {
                throw new Exception('Dados inválidos');
            }
            
            $result = $userModel->updateRole($userId, $roleId);
            echo json_encode($result);
            break;
            
        case 'update_plan':
            // Atualizar plano do usuário
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($data['user_id'] ?? 0);
            $planId = (int)($data['plan_id'] ?? 0);
            
            if (!$userId || !$planId) {
                throw new Exception('Dados inválidos');
            }
            
            $result = $userModel->updatePlan($userId, $planId);
            echo json_encode($result);
            break;
            
        case 'deactivate_user':
            // Desativar usuário
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = (int)($data['user_id'] ?? 0);
            
            if (!$userId) {
                throw new Exception('ID do usuário não informado');
            }
            
            $result = $userModel->deactivate($userId);
            echo json_encode($result);
            break;
            
        case 'get_plans':
            // Listar todos os planos
            $planModel = new Plan();
            $plans = $planModel->getAllActive();
            echo json_encode(['success' => true, 'data' => $plans]);
            break;
            
        case 'get_plan_stats':
            // Estatísticas dos planos
            $planModel = new Plan();
            $stats = $planModel->getPlanStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'create_plan':
            // Criar novo plano
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $planModel = new Plan();
            $planId = $planModel->create($data);
            
            echo json_encode(['success' => true, 'message' => 'Plano criado com sucesso', 'plan_id' => $planId]);
            break;
            
        case 'update_plan_item':
            // Atualizar plano existente
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $planId = (int)($data['id'] ?? 0);
            
            if (!$planId) {
                throw new Exception('ID do plano não informado');
            }
            
            $planModel = new Plan();
            $result = $planModel->update($planId, $data);
            
            echo json_encode($result);
            break;
            
        case 'delete_plan':
            // Remover plano
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $planId = (int)($data['id'] ?? 0);
            
            if (!$planId) {
                throw new Exception('ID do plano não informado');
            }
            
            $planModel = new Plan();
            $result = $planModel->delete($planId);
            
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
