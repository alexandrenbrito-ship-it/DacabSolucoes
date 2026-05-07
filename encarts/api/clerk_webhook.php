<?php
/**
 * /api/clerk_webhook.php
 * Webhook handler para eventos do Clerk
 * Recebe eventos: user.created, user.updated, user.deleted
 */

require_once __DIR__ . '/../config/clerk.php';
require_once __DIR__ . '/../classes/Database.php';

header('Content-Type: application/json; charset=utf-8');

// Apenas métodos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ler payload bruto
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Obter assinatura do webhook
$svixSignature = $headers['Svix-Signature'] ?? $headers['svix-signature'] ?? '';
$svixId = $headers['Svix-ID'] ?? $headers['svix-id'] ?? '';
$svixTimestamp = $headers['Svix-Timestamp'] ?? $headers['svix-timestamp'] ?? '';

// Validar assinatura se CLERK_WEBHOOK_SECRET estiver configurado
if (!empty(CLERK_WEBHOOK_SECRET)) {
    if (empty($svixSignature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing Svix-Signature header']);
        exit;
    }
    
    // Verificar timestamp (evitar replay attacks - janela de 5 minutos)
    $timestamp = (int)$svixTimestamp;
    if (abs(time() - $timestamp) > 300) {
        http_response_code(400);
        echo json_encode(['error' => 'Webhook timestamp too old']);
        exit;
    }
    
    // Validar assinatura usando HMAC-SHA256
    $signedContent = "$svixId.$svixTimestamp.$payload";
    
    // O segredo do Clerk webhook vem em formato base64
    $secret = base64_decode(CLERK_WEBHOOK_SECRET);
    if (!$secret) {
        $secret = CLERK_WEBHOOK_SECRET;
    }
    
    $expectedSignature = base64_encode(hash_hmac('sha256', $signedContent, $secret, true));
    
    // Extrair a assinatura v1 do header (formato: "v1,xxx")
    $providedSignatures = explode(',', $svixSignature);
    $signatureValid = false;
    
    foreach ($providedSignatures as $sig) {
        [$version, $hash] = explode(',', $sig);
        if ($version === 'v1' && hash_equals($expectedSignature, $hash)) {
            $signatureValid = true;
            break;
        }
    }
    
    if (!$signatureValid) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Parse do payload JSON
$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload']);
    exit;
}

// Obter tipo do evento
$eventType = $data['type'] ?? '';
$eventData = $data['data'] ?? [];

// Processar eventos do Clerk
$db = Database::getInstance()->getConnection();

try {
    switch ($eventType) {
        case 'user.created':
        case 'user.updated':
            handleUserUpsert($db, $eventData, $eventType);
            break;
            
        case 'user.deleted':
            handleUserDelete($db, $eventData);
            break;
            
        default:
            // Evento não tratado, apenas logar
            error_log("Clerk webhook: Unhandled event type: $eventType");
            break;
    }
    
    // Logar sincronização
    logSync($db, $eventData['id'] ?? '', $eventType, $data);
    
    // Responder sucesso para o Clerk
    http_response_code(200);
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Clerk webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Cria ou atualiza usuário no banco
 */
function handleUserUpsert(PDO $db, array $data, string $eventType): void {
    $clerkUserId = $data['id'] ?? null;
    if (!$clerkUserId) {
        throw new Exception("Missing user ID in webhook data");
    }
    
    // Extrair email primário
    $email = null;
    if (isset($data['email_addresses']) && is_array($data['email_addresses'])) {
        foreach ($data['email_addresses'] as $ea) {
            if (($ea['verified'] ?? false) || true) {
                $email = $ea['email_address'] ?? null;
                break;
            }
        }
    }
    if (!$email) {
        $email = $data['primary_email_address'] ?? '';
    }
    
    // Extrair nome
    $firstName = $data['first_name'] ?? '';
    $lastName = $data['last_name'] ?? '';
    $name = trim("$firstName $lastName");
    if (empty($name)) {
        $name = $data['username'] ?? explode('@', $email)[0] ?? 'Usuário';
    }
    
    // Extrair avatar
    $avatarUrl = $data['image_url'] ?? $data['profile_image_url'] ?? null;
    
    // Verificar se usuário já existe
    $stmt = $db->prepare("SELECT id FROM users WHERE clerk_user_id = :clerk_user_id");
    $stmt->execute(['clerk_user_id' => $clerkUserId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Atualizar usuário existente
        $stmt = $db->prepare("
            UPDATE users 
            SET clerk_email = :clerk_email,
                name = :name,
                email = :email,
                avatar_url = :avatar_url,
                updated_at = NOW(),
                last_login = CASE WHEN :event_type = 'user.created' THEN NULL ELSE last_login END,
                is_active = 1
            WHERE clerk_user_id = :clerk_user_id
        ");
        $stmt->execute([
            'clerk_email' => $email,
            'name' => $name,
            'email' => $email,
            'avatar_url' => $avatarUrl,
            'clerk_user_id' => $clerkUserId,
            'event_type' => $eventType
        ]);
    } else {
        // Criar novo usuário
        $stmt = $db->prepare("
            INSERT INTO users (clerk_user_id, clerk_email, name, email, plan, avatar_url, is_active)
            VALUES (:clerk_user_id, :clerk_email, :name, :email, 'free', :avatar_url, 1)
        ");
        $stmt->execute([
            'clerk_user_id' => $clerkUserId,
            'clerk_email' => $email,
            'name' => $name,
            'email' => $email,
            'avatar_url' => $avatarUrl
        ]);
    }
}

/**
 * Marca usuário como inativo quando deletado no Clerk
 */
function handleUserDelete(PDO $db, array $data): void {
    $clerkUserId = $data['id'] ?? null;
    if (!$clerkUserId) {
        return;
    }
    
    $stmt = $db->prepare("
        UPDATE users 
        SET is_active = 0, 
            updated_at = NOW() 
        WHERE clerk_user_id = :clerk_user_id
    ");
    $stmt->execute(['clerk_user_id' => $clerkUserId]);
}

/**
 * Registra sincronização no log
 */
function logSync(PDO $db, string $clerkUserId, string $eventType, array $payload): void {
    try {
        $stmt = $db->prepare("
            INSERT INTO user_sync_log (clerk_user_id, event_type, payload)
            VALUES (:clerk_user_id, :event_type, :payload)
        ");
        $stmt->execute([
            'clerk_user_id' => $clerkUserId,
            'event_type' => $eventType,
            'payload' => json_encode($payload)
        ]);
    } catch (Exception $e) {
        error_log("Failed to log Clerk sync: " . $e->getMessage());
    }
}

/**
 * Compatibilidade para getallheaders() em alguns servidores
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
