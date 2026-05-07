<?php
/**
 * /api/auth.php
 * API de autenticação - Login, Logout, Register
 * Recebe e retorna JSON
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Apenas métodos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Carregar classes necessárias
require_once dirname(__DIR__) . '/classes/Database.php';
require_once dirname(__DIR__) . '/classes/Auth.php';
require_once dirname(__DIR__) . '/classes/User.php';

// Ler JSON do body
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$action = $input['action'] ?? '';
$auth = new Auth();
$userModel = new User();

switch ($action) {
    case 'login':
        handleLogin($auth, $userModel, $input);
        break;
        
    case 'logout':
        handleLogout($auth);
        break;
        
    case 'register':
        handleRegister($userModel, $input);
        break;
        
    case 'check':
        handleCheck($auth);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida. Use: login, logout, register, check']);
}

/**
 * Handler para login
 */
function handleLogin(Auth $auth, User $userModel, array $input): void {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios.']);
        return;
    }
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido.']);
        return;
    }
    
    $result = $auth->login($email, $password);
    
    if (!$result['success']) {
        // Log tentativa falha para rate limiting
        $auth->logFailedAttempt($email);
        http_response_code(401);
    } else {
        http_response_code(200);
    }
    
    unset($result['token']); // Não retornar token no response
    echo json_encode($result);
}

/**
 * Handler para logout
 */
function handleLogout(Auth $auth): void {
    $result = $auth->logout();
    
    if ($result) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso.']);
    } else {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Nenhuma sessão ativa.']);
    }
}

/**
 * Handler para registro de novo usuário
 */
function handleRegister(User $userModel, array $input): void {
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $plan = trim($input['plan'] ?? 'free');
    
    // Validações
    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
        return;
    }
    
    if (strlen($name) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'O nome deve ter pelo menos 3 caracteres.']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido.']);
        return;
    }
    
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.']);
        return;
    }
    
    $result = $userModel->create($name, $email, $password, $plan);
    
    if (!$result['success']) {
        http_response_code(400);
    } else {
        http_response_code(201);
    }
    
    echo json_encode($result);
}

/**
 * Handler para verificar status de autenticação
 */
function handleCheck(Auth $auth): void {
    $userData = $auth->getUserData();
    http_response_code(200);
    echo json_encode($userData);
}
