<?php
/**
 * EncartePro - Email de Confirmação de Assinatura
 * Template HTML para email enviado após confirmação de pagamento
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Confirmada - {{SITE_NAME}}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 32px; margin: 0; font-weight: 700;">✅ Pagamento Confirmado!</h1>
                            <p style="color: #ffffff; opacity: 0.9; margin: 10px 0 0 0; font-size: 16px;">Sua assinatura está ativa</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1a1a; font-size: 24px; margin: 0 0 20px 0;">Olá, {{USER_NAME}}! 🎉</h2>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Ótimas notícias! Seu pagamento foi confirmado e sua assinatura do <strong>{{SITE_NAME}}</strong> está ativa.
                            </p>
                            
                            <!-- Plan Info Box -->
                            <div style="background: linear-gradient(135deg, #e8401c 0%, #f5a623 100%); border-radius: 10px; padding: 25px; margin: 25px 0; color: white;">
                                <h3 style="color: #ffffff; font-size: 20px; margin: 0 0 15px 0; text-align: center;">📋 Detalhes da Sua Assinatura</h3>
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                            <span style="opacity: 0.9;">Plano:</span>
                                        </td>
                                        <td style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.2); text-align: right; font-weight: 600;">
                                            {{PLAN_NAME}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">
                                            <span style="opacity: 0.9;">Status:</span>
                                        </td>
                                        <td style="padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.2); text-align: right; font-weight: 600;">
                                            ✅ Ativo
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 10px 0;">
                                            <span style="opacity: 0.9;">Próximo vencimento:</span>
                                        </td>
                                        <td style="padding: 10px 0; text-align: right; font-weight: 600;">
                                            {{EXPIRES_AT}}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- CTA Button -->
                            <table role="presentation" style="margin: 30px 0; border-collapse: collapse;">
                                <tr>
                                    <td align="center" style="border-radius: 8px; background: #e8401c;">
                                        <a href="{{DASHBOARD_LINK}}" style="display: inline-block; padding: 16px 40px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: 600; border-radius: 8px;">
                                            🚀 Acessar Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Features -->
                            <div style="background: #f8f9fa; border-radius: 8px; padding: 25px; margin: 25px 0;">
                                <h3 style="color: #1a1a1a; font-size: 18px; margin: 0 0 15px 0;">Agora você pode:</h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div style="background: white; padding: 15px; border-radius: 6px;">
                                        <span style="font-size: 24px;">✨</span>
                                        <p style="margin: 10px 0 0 0; color: #555; font-size: 14px;">Criar encartes ilimitados*</p>
                                    </div>
                                    <div style="background: white; padding: 15px; border-radius: 6px;">
                                        <span style="font-size: 24px;">🎨</span>
                                        <p style="margin: 10px 0 0 0; color: #555; font-size: 14px;">Todos os templates</p>
                                    </div>
                                    <div style="background: white; padding: 15px; border-radius: 6px;">
                                        <span style="font-size: 24px;">📄</span>
                                        <p style="margin: 10px 0 0 0; color: #555; font-size: 14px;">Exportar em PDF</p>
                                    </div>
                                    <div style="background: white; padding: 15px; border-radius: 6px;">
                                        <span style="font-size: 24px;">💬</span>
                                        <p style="margin: 10px 0 0 0; color: #555; font-size: 14px;">Suporte prioritário</p>
                                    </div>
                                </div>
                                <p style="color: #999; font-size: 12px; margin: 15px 0 0 0; text-align: center;">*De acordo com o limite do seu plano</p>
                            </div>
                            
                            <!-- Payment Info -->
                            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <p style="color: #004085; font-size: 15px; margin: 0;">
                                    <strong>📧 Recibo:</strong> Você receberá um email com o recibo do pagamento em breve. 
                                    Guarde este email para seus registros.
                                </p>
                            </div>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 20px 0 0 0;">
                                Obrigado por escolher o <strong>{{SITE_NAME}}</strong>! Estamos felizes em fazer parte do crescimento do seu negócio.
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
                                Dúvidas? Entre em contato com nosso suporte.
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
