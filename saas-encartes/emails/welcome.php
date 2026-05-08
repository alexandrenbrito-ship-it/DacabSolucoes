<?php
/**
 * EncartePro - Email de Boas-Vindas
 * Template HTML para email enviado após registro
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao {{SITE_NAME}}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 0;">
                <table role="presentation" style="width: 600px; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #e8401c 0%, #f5a623 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 32px; margin: 0; font-weight: 700;">🎨 {{SITE_NAME}}</h1>
                            <p style="color: #ffffff; opacity: 0.9; margin: 10px 0 0 0; font-size: 16px;">Crie encartes incríveis em minutos</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1a1a; font-size: 24px; margin: 0 0 20px 0;">Olá, {{USER_NAME}}! 👋</h2>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Seja muito bem-vindo ao <strong>{{SITE_NAME}}</strong>! Estamos muito felizes em ter você conosco.
                            </p>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Sua conta foi criada com sucesso e você já tem acesso ao nosso <strong>plano Trial de 7 dias grátis</strong> no plano Starter!
                            </p>
                            
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
                            <div style="background: #f8f9fa; border-radius: 8px; padding: 25px; margin: 30px 0;">
                                <h3 style="color: #1a1a1a; font-size: 18px; margin: 0 0 15px 0;">O que você pode fazer agora:</h3>
                                <ul style="color: #555555; font-size: 15px; line-height: 1.8; margin: 0; padding-left: 20px;">
                                    <li>✨ Escolher entre 6 templates profissionais</li>
                                    <li>📱 Criar encartes para redes sociais</li>
                                    <li>📄 Baixar seus encartes em PDF</li>
                                    <li>🎨 Personalizar cores, textos e imagens</li>
                                    <li>⚡ Publicar e compartilhar em segundos</li>
                                </ul>
                            </div>
                            
                            <!-- Verification -->
                            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <p style="color: #856404; font-size: 15px; margin: 0 0 15px 0;">
                                    <strong>⚠️ Verifique seu email:</strong> Para aproveitar todos os recursos, clique no botão abaixo para confirmar seu email.
                                </p>
                                <a href="{{VERIFICATION_LINK}}" style="display: inline-block; padding: 12px 25px; background: #ffc107; color: #1a1a1a; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 6px;">
                                    ✅ Verificar Meu Email
                                </a>
                            </div>
                            
                            <p style="color: #555555; font-size: 16px; line-height: 1.6; margin: 20px 0 0 0;">
                                Se tiver alguma dúvida, nossa equipe de suporte está sempre pronta para ajudar!
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
                                Feito com ❤️ para impulsionar seu negócio
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
