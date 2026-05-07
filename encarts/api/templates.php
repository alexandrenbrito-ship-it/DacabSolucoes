<?php
/**
 * /api/templates.php
 * API para listagem e busca de templates
 * GET - Listar templates disponíveis
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar classes necessárias
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/ClerkAuth.php';
require_once dirname(__DIR__) . '/classes/Template.php';

$templateModel = new Template();

// Verificar autenticação (opcional - templates públicos podem ser acessados sem login)
$isPro = false;
try {
    $user = ClerkAuth::requireAuth();
    $isPro = isset($user['plan']) && $user['plan'] === 'pro';
} catch (Exception $e) {
    // Usuário não autenticado - permitir acesso apenas a templates gratuitos
}

// Apenas método GET permitido
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Parâmetros da query string
$action = $_GET['action'] ?? 'list';
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$includePremium = $isPro; // Usuários pro veem templates premium

switch ($action) {
    case 'categories':
        // Listar categorias disponíveis
        $categories = $templateModel->getCategories();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $categories,
            'count' => count($categories)
        ]);
        break;
        
    case 'search':
        // Buscar templates por nome
        if (empty($search)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Termo de busca obrigatório.']);
            return;
        }
        $templates = $templateModel->search($search, $includePremium);
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $templates,
            'count' => count($templates)
        ]);
        break;
        
    case 'single':
        // Obter template específico pelo ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if ($id === null || $id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }
        
        $template = $templateModel->getById($id);
        
        if (!$template) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Template não encontrado.']);
            return;
        }
        
        // Verificar se é premium e usuário não é pro
        if ($template['is_premium'] && !$isPro) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Template disponível apenas para usuários Pro.',
                'is_premium' => true
            ]);
            return;
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $template
        ]);
        break;
        
    case 'list':
    default:
        // Listar todos os templates (com filtro opcional de categoria)
        $templates = $templateModel->getAll($category, $includePremium);
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $templates,
            'count' => count($templates),
            'user_plan' => $isPro ? 'pro' : 'free'
        ]);
        break;
}
