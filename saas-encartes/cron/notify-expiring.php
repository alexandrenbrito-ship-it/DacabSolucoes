<?php
/**
 * EncartePro - Script Cron para Notificação de Assinaturas Expirando
 * Executar diariamente: 0 8 * * * php /caminho/cron/notify-expiring.php
 */

// Define caminho raiz
define('ROOT_PATH', dirname(__DIR__));

// Carrega configurações
require_once ROOT_PATH . '/includes/config.php';

// Verifica se o sistema está instalado
if (!defined('DB_HOST')) {
    echo "Sistema não instalado\n";
    exit(1);
}

// Função para log
function logMessage($message) {
    $logFile = ROOT_PATH . '/cron/logs/notify-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

logMessage("=== Início do processo de notificação de vencimentos ===");

// Obtém dias para notificar das configurações
$notifyDays = (int) getSetting('notify_expiry_days', 7);
logMessage("Dias para notificação: $notifyDays");

// Busca assinaturas que vão vencer em X dias
$pdo = getDB();
if (!$pdo) {
    logMessage("ERRO: Falha na conexão com banco de dados");
    exit(1);
}

// Data alvo para vencimento
$targetDate = date('Y-m-d', strtotime("+{$notifyDays} days"));

$sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.notification_expiry,
               p.name as plan_name, p.price as plan_price
        FROM subscriptions s
        JOIN users u ON s.user_id = u.id
        JOIN plans p ON s.plan_id = p.id
        WHERE s.status = 'active' 
          AND DATE(s.expires_at) = ?
          AND u.notification_expiry = 1
          AND u.status = 'active'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$targetDate]);
$subscriptions = $stmt->fetchAll();

logMessage("Assinaturas encontradas para notificação: " . count($subscriptions));

if (empty($subscriptions)) {
    logMessage("Nenhuma assinatura para notificar hoje");
    exit(0);
}

// Inclui função de envio de email
require_once ROOT_PATH . '/includes/mail.php';

$enviados = 0;
$falhas = 0;

foreach ($subscriptions as $sub) {
    logMessage("Processando usuário: {$sub['user_name']} ({$sub['user_email']})");
    
    // Verifica se já foi enviado nos últimos 2 dias (evita duplicidade)
    $checkSql = "SELECT id FROM notification_logs 
                 WHERE user_id = ? AND type = 'expiry_warning' 
                 AND created_at > DATE_SUB(NOW(), INTERVAL 2 DAY)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$sub['user_id']]);
    
    if ($checkStmt->fetch()) {
        logMessage("Notificação já enviada recentemente para {$sub['user_email']}");
        continue;
    }
    
    // Prepara variáveis do email
    $emailVars = [
        'name' => $sub['user_name'],
        'planName' => $sub['plan_name'],
        'price' => formatMoney($sub['plan_price']),
        'expiresAt' => formatDate($sub['expires_at'], 'd/m/Y'),
        'daysRemaining' => $notifyDays,
        'renewUrl' => SITE_URL . '/dashboard/index.php?action=renew'
    ];
    
    // Envia email
    try {
        $result = sendMail(
            $sub['user_email'],
            $sub['user_name'],
            'Sua assinatura está prestes a vencer!',
            ROOT_PATH . '/emails/subscription-expiring.php',
            $emailVars
        );
        
        if ($result) {
            logMessage("Email enviado com sucesso para {$sub['user_email']}");
            $enviados++;
            
            // Registra no log de notificações (se tabela existir)
            try {
                $logSql = "INSERT INTO notification_logs (user_id, type, sent_at) VALUES (?, 'expiry_warning', NOW())";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([$sub['user_id']]);
            } catch (PDOException $e) {
                // Tabela não existe, ignora
            }
        } else {
            logMessage("Falha ao enviar email para {$sub['user_email']}");
            $falhas++;
        }
    } catch (Exception $e) {
        logMessage("ERRO ao enviar email para {$sub['user_email']}: " . $e->getMessage());
        $falhas++;
    }
}

logMessage("=== Processo finalizado ===");
logMessage("Total enviados: $enviados");
logMessage("Total falhas: $falhas");

exit(0);
