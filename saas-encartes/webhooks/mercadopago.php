<?php
/**
 * EncartePro - Webhook do Mercado Pago
 * Recebe e processa notificações de pagamento
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mercadopago.php';

// Registra o início do webhook
file_put_contents(__DIR__ . '/webhook_debug.log', '[' . date('Y-m-d H:i:s') . '] Webhook recebido' . PHP_EOL, FILE_APPEND);

// Obtém os dados da requisição
$input = file_get_contents('php://input');
$postData = json_decode($input, true) ?? $_POST;

// Log dos dados recebidos
file_put_contents(__DIR__ . '/webhook_debug.log', '[' . date('Y-m-d H:i:s') . '] Dados: ' . json_encode($postData) . PHP_EOL, FILE_APPEND);

// Verifica se é uma requisição válida
if (empty($postData)) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Valida a assinatura do webhook (se configurado)
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
if (!validateMPWebhookSignature($signature, $input)) {
    http_response_code(401);
    echo json_encode(['error' => 'Assinatura inválida']);
    exit;
}

// Processa o webhook
try {
    $result = processMPWebhook($postData);
    
    if ($result) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Webhook processado com sucesso']);
    } else {
        http_response_code(200); // Retorna 200 mesmo assim para evitar retries desnecessários
        echo json_encode(['success' => false, 'message' => 'Webhook não processado']);
    }
} catch (Exception $e) {
    error_log("Erro ao processar webhook: " . $e->getMessage());
    file_put_contents(__DIR__ . '/webhook_debug.log', '[' . date('Y-m-d H:i:s') . '] Erro: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao processar webhook']);
}
