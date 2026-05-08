<?php
/**
 * EncartePro - Email de Redefinição de Senha
 * Template HTML para email de recuperação de senha
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - {{SITE_NAME}}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e8401c 0%, #f5a623 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 32px; margin: 0; font-weight: 700;">🔐 {{SITE_NAME}}</h1>
                            <p style="color: #ffffff; opacity: 0.9; margin: 10px 0 0 0; font-size: 16px;">Redefinição de Senha</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1a1a; font-size: 24px; margin: 0 0 20px 0;">Olá, {{USER_NAME}}!</h2>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Recebemos uma solicitação para redefinir a senha da sua conta no <strong>{{SITE_NAME}}</strong>.
                            </p>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Clique no botão abaixo para criar uma nova senha:
                            </p>
                            
                            <!-- CTA Button -->
                            <table role="presentation" style="margin: 30px 0; border-collapse: collapse;">
                                <tr>
                                    <td align="center" style="border-radius: 8px; background: #e8401c;">
                                        <a href="{{RESET_LINK}}" style="display: inline-block; padding: 16px 40px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">
                                            🔑 Redefinir Minha Senha
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Warning Box -->
                            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <p style="color: #856404; font-size: 15px; margin: 0 0 10px 0;">
                                    <strong>⏰ Importante:</strong> Este link expira em <strong>{{EXPIRE_HOURS}} hora(s)</strong>.
                                </p>
                                <p style="color: #856404; font-size: 14px; margin: 0;">
                                    Se você não solicitou esta redefinição, pode ignorar este email. Sua senha permanecerá inalterada.
                                </p>
                            </div>
                            
                            <!-- Security Info -->
                            <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <h3 style="color: #1a1a1a; font-size: 16px; margin: 0 0 10px 0;">🛡️ Dicas de Segurança:</h3>
                                <ul style="color: #555555; font-size: 14px; line-height: 1.8; margin: 0; padding-left: 20px;">
                                    <li>Use uma senha única que você não usa em outros sites</li>
                                    <li>Inclua letras maiúsculas, minúsculas, números e símbolos</li>
                                    <li>Tenha pelo menos 8 caracteres</li>
                                    <li>Não compartilhe sua senha com ninguém</li>
                                </ul>
                            </div>
                            
                            <p style="color: #555555; font-size: 15px; line-height: 1.6; margin: 20px 0 0 0;">
                                Precisa de ajuda? Entre em contato com nosso suporte.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #1a1a1a; padding: 30px; text-align: center;">
                            <p style="color: #ffffff; font-size: 14px; margin: 0 0 10px 0;">
                                © {{CURRENT_YEAR}} {{SITE_NAME}}. Todos os direitos reservados.
                            </p>
                            <p style="color: #999999; font-size: 13px; margin: 0;">
                                Este é um email automático, por favor não responda.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
