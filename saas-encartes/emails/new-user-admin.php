<?php
/**
 * Email de Notificação para Admin - Novo Usuário
 * Template HTML para notificar admin sobre novo cadastro
 * 
 * Variáveis disponíveis:
 * - $userName: Nome do usuário
 * - $userEmail: Email do usuário
 * - $registeredAt: Data de cadastro formatada
 * - $adminUrl: URL para painel admin
 */

if (!isset($userName)) $userName = 'Novo Usuário';
if (!isset($userEmail)) $userEmail = 'email@exemplo.com';
if (!isset($registeredAt)) $registeredAt = date('d/m/Y H:i');
if (!isset($adminUrl)) $adminUrl = SITE_URL . '/admin/users.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo usuário cadastrado</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'DM Sans', Arial, sans-serif; background-color: #f5f3ee;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f3ee; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">🎉 Novo Cadastro</h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9;">Um novo usuário acabou de se registrar</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 25px 0; color: #0a0a0f; font-size: 16px; line-height: 1.6;">
                                Olá, Administrador!
                            </p>
                            
                            <p style="margin: 0 0 25px 0; color: #6b6b6b; font-size: 16px; line-height: 1.6;">
                                Temos um novo usuário cadastrado no <?= getSetting('site_name', 'EncartePro') ?>:
                            </p>
                            
                            <!-- Box de informações -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0fdf4; border: 1px solid #40916c; border-radius: 8px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-bottom: 15px; border-bottom: 1px solid #d0e8d8;">
                                                    <p style="margin: 0; color: #9a9a9a; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">👤 Nome</p>
                                                    <p style="margin: 5px 0 0 0; color: #0a0a0f; font-size: 18px; font-weight: 700;"><?= htmlspecialchars($userName) ?></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 15px 0; border-bottom: 1px solid #d0e8d8;">
                                                    <p style="margin: 0; color: #9a9a9a; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">📧 Email</p>
                                                    <p style="margin: 5px 0 0 0; color: #0a0a0f; font-size: 16px; font-weight: 600;"><?= htmlspecialchars($userEmail) ?></p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top: 15px;">
                                                    <p style="margin: 0; color: #9a9a9a; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">📅 Data/Hora</p>
                                                    <p style="margin: 5px 0 0 0; color: #0a0a0f; font-size: 16px;"><?= $registeredAt ?></p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0 0; color: #6b6b6b; font-size: 16px; line-height: 1.6;">
                                Acesse o painel administrativo para gerenciar este usuário, verificar seu plano e acompanhar sua atividade.
                            </p>
                            
                            <!-- Botão CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= $adminUrl ?>" style="display: inline-block; background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 16px rgba(45, 106, 79, 0.3);">
                                            👁️ Ver no Painel
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Divider -->
                    <tr>
                        <td style="border-top: 1px solid #d4d0c8; padding: 0;"></td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #faf9f7; padding: 30px; text-align: center;">
                            <p style="margin: 0 0 15px 0; color: #9a9a9a; font-size: 14px;">
                                Esta é uma notificação automática do sistema.
                            </p>
                            <p style="margin: 0; color: #9a9a9a; font-size: 13px;">
                                © <?= date('Y') ?> <?= getSetting('site_name', 'EncartePro') ?>. Painel Administrativo.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <!-- Info adicional -->
                <table width="600" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td align="center" style="color: #9a9a9a; font-size: 12px; padding: 10px;">
                            <p style="margin: 0;">
                                💡 Dica: Você pode desativar estas notificações em 
                                <a href="<?= SITE_URL ?>/admin/settings.php#notifications" style="color: #9a9a9a; text-decoration: underline;">Configurações do Sistema</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
