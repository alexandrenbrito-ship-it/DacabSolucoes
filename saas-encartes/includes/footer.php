    </div>
    <!-- Fim do Container Principal -->
    
    <!-- Footer -->
    <footer style="background: var(--dark-color); color: white; padding: 3rem 0; margin-top: 3rem;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="grid grid-3">
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 1rem; color: var(--primary-color);">
                        <?php echo SITE_NAME; ?>
                    </h3>
                    <p style="color: #aaa; line-height: 1.8;">
                        Crie encartes digitais incríveis em minutos e impulsione suas vendas.
                    </p>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 1rem;">Links Rápidos</h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 0.5rem;"><a href="<?php echo SITE_URL; ?>" style="color: #aaa;">Início</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="<?php echo SITE_URL; ?>/auth/register.php" style="color: #aaa;">Começar Grátis</a></li>
                        <li style="margin-bottom: 0.5rem;"><a href="<?php echo SITE_URL; ?>/auth/login.php" style="color: #aaa;">Entrar</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="margin-bottom: 1rem;">Contato</h4>
                    <ul style="list-style: none; color: #aaa;">
                        <li style="margin-bottom: 0.5rem;">Email: contato@encartepro.com.br</li>
                        <li style="margin-bottom: 0.5rem;">Suporte: suporte@encartepro.com.br</li>
                    </ul>
                </div>
            </div>
            
            <div style="border-top: 1px solid #333; margin-top: 2rem; padding-top: 2rem; text-align: center; color: #aaa;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
</body>
</html>
