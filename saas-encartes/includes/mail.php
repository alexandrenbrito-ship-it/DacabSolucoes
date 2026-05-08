<?php
/**
 * EncartePro - Sistema de Envio de E-mails
 * Responsável pelo envio de e-mails transacionais usando PHPMailer ou mail() nativo
 */

// Inclui o arquivo de configuração
require_once __DIR__ . '/config.php';

/**
 * Envia um e-mail usando template HTML
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $subject Assunto do e-mail
 * @param string $templateFile Caminho para o arquivo de template (dentro de /emails/)
 * @param array $vars Variáveis para substituir no template
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendMail($to, $toName, $subject, $templateFile, $vars = []) {
    // Verifica se o arquivo de template existe
    $templatePath = __DIR__ . '/../emails/' . basename($templateFile);
    if (!file_exists($templatePath)) {
        error_log("Template de e-mail não encontrado: " . $templatePath);
        return false;
    }
    
    // Carrega o conteúdo do template
    $body = file_get_contents($templatePath);
    
    // Substitui as variáveis no template
    foreach ($vars as $key => $value) {
        $body = str_replace('{{' . $key . '}}', $value, $body);
    }
    
    // Substitui variáveis globais do sistema
    $body = str_replace('{{SITE_NAME}}', SITE_NAME, $body);
    $body = str_replace('{{SITE_URL}}', SITE_URL, $body);
    $body = str_replace('{{CURRENT_YEAR}}', date('Y'), $body);
    
    // Tenta usar PHPMailer se disponível
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return sendWithPHPMailer($to, $toName, $subject, $body);
    }
    
    // Fallback para mail() nativo
    return sendWithNativeMail($to, $toName, $subject, $body);
}

/**
 * Envia e-mail usando PHPMailer
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $subject Assunto do e-mail
 * @param string $body Corpo do e-mail em HTML
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendWithPHPMailer($to, $toName, $subject, $body) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configurações do servidor
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;
        
        // Configurações de remetente e destinatário
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        
        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Versão em texto puro (opcional)
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail com PHPMailer: " . $e->getMessage());
        return false;
    }
}

/**
 * Envia e-mail usando a função mail() nativa do PHP
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $subject Assunto do e-mail
 * @param string $body Corpo do e-mail em HTML
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendWithNativeMail($to, $toName, $subject, $body) {
    // Headers para e-mail HTML
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';
    $headers[] = 'Reply-To: ' . MAIL_FROM;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    // Codifica o assunto para UTF-8
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    
    // Envia o e-mail
    $result = mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    
    if (!$result) {
        error_log("Falha ao enviar e-mail com mail() nativo");
    }
    
    return $result;
}

/**
 * Envia e-mail de boas-vindas para novo usuário
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $verificationToken Token de verificação de email
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendWelcomeEmail($to, $toName, $verificationToken) {
    $verificationLink = SITE_URL . '/auth/verify-email.php?token=' . $verificationToken;
    
    return sendMail(
        $to,
        $toName,
        'Bem-vindo ao ' . SITE_NAME . '!',
        'welcome.php',
        [
            'USER_NAME' => $toName,
            'VERIFICATION_LINK' => $verificationLink,
            'DASHBOARD_LINK' => SITE_URL . '/dashboard/',
            'TRIAL_DAYS' => '7'
        ]
    );
}

/**
 * Envia e-mail de redefinição de senha
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $resetToken Token de redefinição de senha
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendResetPasswordEmail($to, $toName, $resetToken) {
    $resetLink = SITE_URL . '/auth/reset-password.php?token=' . $resetToken;
    
    return sendMail(
        $to,
        $toName,
        'Redefinição de Senha - ' . SITE_NAME,
        'reset-password.php',
        [
            'USER_NAME' => $toName,
            'RESET_LINK' => $resetLink,
            'EXPIRE_HOURS' => '1'
        ]
    );
}

/**
 * Envia e-mail de confirmação de assinatura
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $planName Nome do plano assinado
 * @param string $expiresAt Data de vencimento da assinatura
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendSubscriptionConfirmedEmail($to, $toName, $planName, $expiresAt) {
    return sendMail(
        $to,
        $toName,
        'Assinatura Confirmada - ' . SITE_NAME,
        'subscription-confirmed.php',
        [
            'USER_NAME' => $toName,
            'PLAN_NAME' => $planName,
            'EXPIRES_AT' => formatDate($expiresAt),
            'DASHBOARD_LINK' => SITE_URL . '/dashboard/'
        ]
    );
}

/**
 * Envia e-mail de verificação de e-mail (reenvio)
 * 
 * @param string $to Email do destinatário
 * @param string $toName Nome do destinatário
 * @param string $verificationToken Token de verificação de email
 * @return bool True se enviado com sucesso, false caso contrário
 */
function sendEmailVerificationEmail($to, $toName, $verificationToken) {
    $verificationLink = SITE_URL . '/auth/verify-email.php?token=' . $verificationToken;
    
    return sendMail(
        $to,
        $toName,
        'Verifique seu e-mail - ' . SITE_NAME,
        'welcome.php',
        [
            'USER_NAME' => $toName,
            'VERIFICATION_LINK' => $verificationLink,
            'DASHBOARD_LINK' => SITE_URL . '/dashboard/',
            'TRIAL_DAYS' => '7'
        ]
    );
}
