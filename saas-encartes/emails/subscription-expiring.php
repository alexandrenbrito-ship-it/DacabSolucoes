<?php
/**
 * Email de Assinatura Expirando
 * Template HTML para notificação de vencimento de assinatura
 * 
 * Variáveis disponíveis:
 * - $name: Nome do usuário
 * - $planName: Nome do plano
 * - $price: Preço do plano
 * - $expiresAt: Data de vencimento formatada
 * - $daysRemaining: Dias restantes
 * - $renewUrl: URL para renovação
 */

if (!isset($name)) $name = 'Cliente';
if (!isset($planName)) $planName = 'Plano';
if (!isset($price)) $price = 'R$ 0,00';
if (!isset($expiresAt)) $expiresAt = '--/--/----';
if (!isset($daysRemaining)) $daysRemaining = 7;
if (!isset($renewUrl)) $renewUrl = '#';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sua assinatura está prestes a vencer</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'DM Sans', Arial, sans-serif; background-color: #f5f3ee;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f3ee; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e8401c 0%, #f5a623 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">⚠️ Atenção</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; color: #0a0a0f; font-size: 18px; line-height: 1.6;">
                                Olá, <strong><?= htmlspecialchars($name) ?></strong>!
                            </p>
                            
                            <p style="margin: 0 0 25px 0; color: #6b6b6b; font-size: 16px; line-height: 1.6;">
                                Sua assinatura do plano <strong style="color: #e8401c;"><?= htmlspecialchars($planName) ?></strong> está prestes a vencer.
                            </p>
                            
                            <!-- Box de destaque -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fff8f5; border-left: 4px solid #e8401c; border-radius: 8px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 25px;">
                                        <p style="margin: 0 0 15px 0; color: #e8401c; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                            📅 Vencimento em <?= $daysRemaining ?> dias
                                        </p>
                                        <p style="margin: 0 0 10px 0; color: #0a0a0f; font-size: 18px; font-weight: 700;">
                                            <?= $expiresAt ?>
                                        </p>
                                        <p style="margin: 0; color: #6b6b6b; font-size: 14px;">
                                            Plano: <strong><?= htmlspecialchars($planName) ?></strong> (<?= $price ?>)
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0 0; color: #6b6b6b; font-size: 16px; line-height: 1.6;">
                                Para evitar a interrupção do seu acesso e continuar criando encartes incríveis, renove sua assinatura agora mesmo.
                            </p>
                            
                            <!-- Botão CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="<?= $renewUrl ?>" style="display: inline-block; background: linear-gradient(135deg, #e8401c 0%, #f5a623 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 16px rgba(232, 64, 28, 0.3);">
                                            🔄 Renovar Agora
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0 0; color: #9a9a9a; font-size: 14px; line-height: 1.6;">
                                Ou acesse seu painel em <a href="<?= SITE_URL ?>/dashboard/" style="color: #e8401c; text-decoration: none; font-weight: 600;"><?= SITE_URL ?>/dashboard/</a>
                            </p>
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
                                Esta é uma notificação automática sobre sua assinatura.
                            </p>
                            <p style="margin: 0; color: #9a9a9a; font-size: 13px;">
                                © <?= date('Y') ?> <?= getSetting('site_name', 'EncartePro') ?>. Todos os direitos reservados.
                            </p>
                            <p style="margin: 15px 0 0 0; font-size: 12px;">
                                <a href="<?= SITE_URL ?>/dashboard/profile.php" style="color: #9a9a9a; text-decoration: underline;">Gerenciar notificações</a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <!-- Unsubscribe -->
                <table width="600" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
                    <tr>
                        <td align="center" style="color: #9a9a9a; font-size: 12px; padding: 10px;">
                            <p style="margin: 0;">
                                Não deseja mais receber estes emails? 
                                <a href="<?= SITE_URL ?>/dashboard/profile.php#preferences" style="color: #9a9a9a; text-decoration: underline;">Desative as notificações</a> em seu perfil.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
