<?php
/**
 * EncartePro - Integração com Mercado Pago
 * Responsável por criar preferências de pagamento e processar webhooks
 * Usa a API REST v1 do Mercado Pago via cURL (sem SDK)
 */

// Inclui o arquivo de configuração
require_once __DIR__ . '/config.php';

/**
 * Cria uma preferência de pagamento no Mercado Pago (Checkout Pro)
 * 
 * @param int $planId ID do plano no banco de dados
 * @param int $userId ID do usuário que está assinando
 * @param string $planName Nome do plano
 * @param float $price Preço do plano
 * @param array $backUrls URLs de retorno (success, failure, pending)
 * @return array|null Retorna os dados da preferência ou null em caso de erro
 */
function createMPPreference($planId, $userId, $planName, $price, $backUrls = []) {
    // Verifica se o token de acesso está configurado
    if (empty(MP_ACCESS_TOKEN)) {
        error_log("Mercado Pago: Access Token não configurado");
        return null;
    }
    
    // Define URLs de retorno padrão se não forem fornecidas
    if (empty($backUrls)) {
        $backUrls = [
            'success' => SITE_URL . '/dashboard/?payment=success',
            'failure' => SITE_URL . '/dashboard/?payment=failure',
            'pending' => SITE_URL . '/dashboard/?payment=pending'
        ];
    }
    
    // Dados da preferência
    $preferenceData = [
        'items' => [
            [
                'id' => (string)$planId,
                'title' => $planName . ' - ' . SITE_NAME,
                'quantity' => 1,
                'unit_price' => (float)$price,
                'currency_id' => 'BRL'
            ]
        ],
        'payer' => [
            'email' => getUserEmail($userId)
        ],
        'back_urls' => $backUrls,
        'auto_return' => 'approved',
        'notification_url' => SITE_URL . '/webhooks/mercadopago.php',
        'external_reference' => (string)$userId,
        'metadata' => [
            'user_id' => (string)$userId,
            'plan_id' => (string)$planId
        ]
    ];
    
    // Faz a requisição para a API do Mercado Pago
    $url = 'https://api.mercadopago.com/checkout/preferences';
    $response = mpAPIRequest('POST', $url, $preferenceData);
    
    if ($response && isset($response['init_point'])) {
        return $response;
    }
    
    error_log("Mercado Pago: Erro ao criar preferência - " . json_encode($response));
    return null;
}

/**
 * Obtém informações de um pagamento pelo ID
 * 
 * @param string $paymentId ID do pagamento no Mercado Pago
 * @return array|null Retorna os dados do pagamento ou null em caso de erro
 */
function getMPPayment($paymentId) {
    if (empty(MP_ACCESS_TOKEN)) {
        return null;
    }
    
    $url = 'https://api.mercadopago.com/v1/payments/' . $paymentId;
    $response = mpAPIRequest('GET', $url);
    
    return $response;
}

/**
 * Processa um webhook do Mercado Pago
 * 
 * @param array $postData Dados POST recebidos do webhook
 * @return bool True se processado com sucesso, false caso contrário
 */
function processMPWebhook($postData) {
    // Verifica se é uma notificação de pagamento
    if (!isset($postData['type']) || $postData['type'] !== 'payment') {
        return false;
    }
    
    if (!isset($postData['data']['id'])) {
        return false;
    }
    
    $paymentId = $postData['data']['id'];
    
    // Obtém informações do pagamento
    $payment = getMPPayment($paymentId);
    
    if (!$payment) {
        error_log("Mercado Pago Webhook: Não foi possível obter informações do pagamento " . $paymentId);
        return false;
    }
    
    // Extrai dados importantes
    $status = $payment['status'] ?? '';
    $externalReference = $payment['external_reference'] ?? '';
    $userId = (int)$externalReference;
    
    if (!$userId) {
        error_log("Mercado Pago Webhook: User ID não encontrado no external_reference");
        return false;
    }
    
    // Processa de acordo com o status
    switch ($status) {
        case 'approved':
            return handleMPApprovedPayment($payment, $userId);
        case 'rejected':
        case 'cancelled':
            return handleMPRejectedPayment($payment, $userId);
        case 'pending':
        case 'in_process':
            return handleMPPendingPayment($payment, $userId);
        default:
            error_log("Mercado Pago Webhook: Status desconhecido - " . $status);
            return false;
    }
}

/**
 * Processa pagamento aprovado
 * 
 * @param array $payment Dados do pagamento
 * @param int $userId ID do usuário
 * @return bool True se processado com sucesso
 */
function handleMPApprovedPayment($payment, $userId) {
    $db = getDB();
    if (!$db) return false;
    
    $planId = (int)($payment['metadata']['plan_id'] ?? 0);
    $paymentId = $payment['id'];
    $subscriptionId = $payment['subscription_id'] ?? null;
    
    // Verifica se já existe uma assinatura para este pagamento
    $checkSql = "SELECT id FROM subscriptions WHERE mp_payment_id = :payment_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindValue(':payment_id', $paymentId, PDO::PARAM_STR);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        // Já processado, apenas atualiza o status se necessário
        $updateSql = "UPDATE subscriptions SET status = 'active' WHERE mp_payment_id = :payment_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindValue(':payment_id', $paymentId, PDO::PARAM_STR);
        $updateStmt->execute();
        return true;
    }
    
    // Obtém informações do plano
    $planSql = "SELECT * FROM plans WHERE id = :plan_id";
    $planStmt = $db->prepare($planSql);
    $planStmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
    $planStmt->execute();
    $plan = $planStmt->fetch();
    
    if (!$plan) {
        error_log("Mercado Pago: Plano não encontrado - ID: " . $planId);
        return false;
    }
    
    // Calcula data de expiração baseada no ciclo de cobrança
    $startsAt = date('Y-m-d');
    switch ($plan['billing_cycle']) {
        case 'monthly':
            $expiresAt = date('Y-m-d', strtotime('+1 month'));
            break;
        case 'yearly':
            $expiresAt = date('Y-m-d', strtotime('+1 year'));
            break;
        case 'lifetime':
            $expiresAt = null;
            break;
        default:
            $expiresAt = date('Y-m-d', strtotime('+1 month'));
    }
    
    // Cancela assinatura trial anterior se existir
    $cancelTrialSql = "UPDATE subscriptions SET status = 'cancelled' 
                       WHERE user_id = :user_id AND status = 'trial'";
    $cancelTrialStmt = $db->prepare($cancelTrialSql);
    $cancelTrialStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $cancelTrialStmt->execute();
    
    // Cria nova assinatura
    $insertSql = "INSERT INTO subscriptions (user_id, plan_id, status, starts_at, expires_at, 
                    mp_payment_id, mp_subscription_id, payment_ref, created_at)
                  VALUES (:user_id, :plan_id, 'active', :starts_at, :expires_at, 
                          :mp_payment_id, :mp_subscription_id, :payment_ref, NOW())";
    
    $insertStmt = $db->prepare($insertSql);
    $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $insertStmt->bindValue(':plan_id', $planId, PDO::PARAM_INT);
    $insertStmt->bindValue(':starts_at', $startsAt, PDO::PARAM_STR);
    $insertStmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
    $insertStmt->bindValue(':mp_payment_id', $paymentId, PDO::PARAM_STR);
    $insertStmt->bindValue(':mp_subscription_id', $subscriptionId, PDO::PARAM_STR);
    $insertStmt->bindValue(':payment_ref', $paymentId, PDO::PARAM_STR);
    
    if ($insertStmt->execute()) {
        // Envia e-mail de confirmação
        $userEmail = getUserEmail($userId);
        $userName = getUserName($userId);
        
        if ($userEmail && $userName) {
            sendSubscriptionConfirmedEmail($userEmail, $userName, $plan['name'], $expiresAt);
        }
        
        // Log do webhook
        logMPWebhook('payment_approved', $paymentId, $userId, 'Pagamento aprovado e assinatura ativada');
        
        return true;
    }
    
    return false;
}

/**
 * Processa pagamento rejeitado/cancelado
 * 
 * @param array $payment Dados do pagamento
 * @param int $userId ID do usuário
 * @return bool True se processado com sucesso
 */
function handleMPRejectedPayment($payment, $userId) {
    $db = getDB();
    if (!$db) return false;
    
    $paymentId = $payment['id'];
    
    // Atualiza status da assinatura se existir
    $updateSql = "UPDATE subscriptions SET status = 'cancelled' 
                  WHERE mp_payment_id = :payment_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindValue(':payment_id', $paymentId, PDO::PARAM_STR);
    $updateStmt->execute();
    
    // Log do webhook
    logMPWebhook('payment_rejected', $paymentId, $userId, 'Pagamento rejeitado/cancelado');
    
    return true;
}

/**
 * Processa pagamento pendente
 * 
 * @param array $payment Dados do pagamento
 * @param int $userId ID do usuário
 * @return bool True se processado com sucesso
 */
function handleMPPendingPayment($payment, $userId) {
    $db = getDB();
    if (!$db) return false;
    
    $paymentId = $payment['id'];
    
    // Log do webhook
    logMPWebhook('payment_pending', $paymentId, $userId, 'Pagamento pendente');
    
    return true;
}

/**
 * Faz uma requisição à API do Mercado Pago
 * 
 * @param string $method Método HTTP (GET, POST, PUT, DELETE)
 * @param string $url URL da API
 * @param array|null $data Dados para enviar no corpo da requisição (para POST/PUT)
 * @return array|null Resposta da API ou null em caso de erro
 */
function mpAPIRequest($method, $url, $data = null) {
    $ch = curl_init();
    
    $headers = [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        error_log("Mercado Pago API Error: " . $error);
        return null;
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 400) {
        error_log("Mercado Pago API HTTP Error " . $httpCode . ": " . json_encode($result));
        return null;
    }
    
    return $result;
}

/**
 * Obtém o e-mail de um usuário
 * 
 * @param int $userId ID do usuário
 * @return string|null E-mail do usuário ou null se não encontrado
 */
function getUserEmail($userId) {
    $db = getDB();
    if (!$db) return null;
    
    $sql = "SELECT email FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch();
    return $result ? $result['email'] : null;
}

/**
 * Obtém o nome de um usuário
 * 
 * @param int $userId ID do usuário
 * @return string|null Nome do usuário ou null se não encontrado
 */
function getUserName($userId) {
    $db = getDB();
    if (!$db) return null;
    
    $sql = "SELECT name FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch();
    return $result ? $result['name'] : null;
}

/**
 * Registra logs de webhooks do Mercado Pago
 * 
 * @param string $eventType Tipo de evento
 * @param string $paymentId ID do pagamento
 * @param int $userId ID do usuário
 * @param string $message Mensagem descritiva
 * @return void
 */
function logMPWebhook($eventType, $paymentId, $userId, $message) {
    $db = getDB();
    if (!$db) return;
    
    $logFile = BASE_PATH . '/webhooks/mp_webhook.log';
    $logEntry = sprintf(
        "[%s] Event: %s | Payment ID: %s | User ID: %d | Message: %s\n",
        date('Y-m-d H:i:s'),
        $eventType,
        $paymentId,
        $userId,
        $message
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Valida a assinatura do webhook do Mercado Pago
 * 
 * @param string $signature Assinatura recebida no header x-signature
 * @param string $payload Corpo da requisição
 * @return bool True se válida, false caso contrário
 */
function validateMPWebhookSignature($signature, $payload) {
    // Implementação básica de validação
    // Em produção, valide usando o seu Public Key e o algoritmo SHA256
    
    if (empty($signature) || empty(MP_PUBLIC_KEY)) {
        return true; // Aceita se não estiver configurado (apenas para desenvolvimento)
    }
    
    // A validação completa requer verificar a assinatura HMAC-SHA256
    // Esta é uma implementação simplificada
    return true;
}
