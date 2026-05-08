<?php
/**
 * EncartePro - Landing Page Principal
 */

require_once __DIR__ . '/includes/config.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/');
    } else {
        redirect(SITE_URL . '/dashboard/');
    }
}

$pageTitle = 'Crie Encartes Digitais Incríveis';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #e8401c 0%, #f5a623 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.1)"/></svg>');
        opacity: 0.3;
    }
    
    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .hero h1 {
        font-family: var(--font-heading);
        font-size: clamp(2.5rem, 5vw, 4rem);
        font-weight: 800;
        margin-bottom: 1.5rem;
        line-height: 1.1;
    }
    
    .hero p {
        font-size: clamp(1.1rem, 2vw, 1.3rem);
        opacity: 0.95;
        margin-bottom: 2.5rem;
        line-height: 1.6;
    }
    
    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-hero {
        padding: 1rem 2.5rem;
        font-size: 1.1rem;
        border-radius: 50px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .btn-hero:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .btn-white {
        background: white;
        color: var(--primary-color);
    }
    
    .btn-outline-white {
        background: transparent;
        border: 2px solid white;
        color: white;
    }
    
    .btn-outline-white:hover {
        background: white;
        color: var(--primary-color);
    }
    
    /* Sections */
    .section {
        padding: 80px 0;
    }
    
    .section-title {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .section-title h2 {
        font-family: var(--font-heading);
        font-size: clamp(2rem, 4vw, 2.5rem);
        color: var(--dark-color);
        margin-bottom: 1rem;
    }
    
    .section-title p {
        color: var(--gray-color);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    
    /* How It Works */
    .how-it-works {
        background: white;
    }
    
    .steps-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .step-card {
        text-align: center;
        padding: 2rem;
    }
    
    .step-number {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
    }
    
    .step-card h3 {
        font-size: 1.3rem;
        margin-bottom: 1rem;
    }
    
    .step-card p {
        color: var(--gray-color);
        line-height: 1.6;
    }
    
    /* Pricing */
    .pricing {
        background: var(--light-color);
    }
    
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1100px;
        margin: 0 auto;
    }
    
    .pricing-card {
        background: white;
        border-radius: 15px;
        padding: 2.5rem;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .pricing-card:hover {
        transform: translateY(-10px);
    }
    
    .pricing-card.featured {
        border: 3px solid var(--primary-color);
    }
    
    .pricing-card.featured::before {
        content: 'MAIS POPULAR';
        position: absolute;
        top: 20px;
        right: -30px;
        background: var(--primary-color);
        color: white;
        padding: 5px 40px;
        font-size: 0.75rem;
        font-weight: 700;
        transform: rotate(45deg);
    }
    
    .pricing-card h3 {
        font-family: var(--font-heading);
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .pricing-card .price {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 1.5rem 0;
    }
    
    .pricing-card .price span {
        font-size: 1rem;
        color: var(--gray-color);
        font-weight: 400;
    }
    
    .pricing-card .description {
        color: var(--gray-color);
        margin-bottom: 2rem;
    }
    
    .pricing-features {
        list-style: none;
        margin-bottom: 2rem;
        text-align: left;
    }
    
    .pricing-features li {
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .pricing-features li:last-child {
        border-bottom: none;
    }
    
    .check-icon {
        color: var(--success-color);
        font-weight: bold;
    }
    
    /* Gallery */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .gallery-item {
        aspect-ratio: 1;
        background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        transition: transform 0.3s ease;
    }
    
    .gallery-item:hover {
        transform: scale(1.05);
    }
    
    /* Testimonials */
    .testimonials {
        background: white;
    }
    
    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        max-width: 1100px;
        margin: 0 auto;
    }
    
    .testimonial-card {
        background: var(--light-color);
        padding: 2rem;
        border-radius: 15px;
    }
    
    .testimonial-text {
        font-style: italic;
        color: var(--dark-color);
        line-height: 1.8;
        margin-bottom: 1.5rem;
    }
    
    .testimonial-author {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .author-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
    }
    
    .author-info h4 {
        margin-bottom: 0.25rem;
    }
    
    .author-info p {
        color: var(--gray-color);
        font-size: 0.9rem;
    }
    
    /* FAQ */
    .faq {
        background: var(--light-color);
    }
    
    .faq-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .faq-item {
        background: white;
        border-radius: 10px;
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .faq-question {
        padding: 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        transition: background 0.3s ease;
    }
    
    .faq-question:hover {
        background: #f8f9fa;
    }
    
    .faq-answer {
        padding: 0 1.5rem 1.5rem;
        color: var(--gray-color);
        line-height: 1.6;
        display: none;
    }
    
    .faq-item.active .faq-answer {
        display: block;
    }
    
    .faq-icon {
        font-size: 1.5rem;
        transition: transform 0.3s ease;
    }
    
    .faq-item.active .faq-icon {
        transform: rotate(45deg);
    }
    
    /* CTA Final */
    .cta-final {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        text-align: center;
        padding: 80px 0;
    }
    
    .cta-final h2 {
        font-family: var(--font-heading);
        font-size: clamp(2rem, 4vw, 2.5rem);
        margin-bottom: 1rem;
    }
    
    .cta-final p {
        font-size: 1.2rem;
        opacity: 0.95;
        margin-bottom: 2rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .hero {
            padding: 60px 0;
        }
        
        .section {
            padding: 50px 0;
        }
    }
</style>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Crie encartes digitais incríveis em minutos</h1>
        <p>A ferramenta perfeita para pequenos negócios, restaurantes e varejistas criarem materiais promocionais profissionais sem precisar de designer.</p>
        <div class="hero-buttons">
            <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-hero btn-white">
                🚀 Começar Grátis
            </a>
            <a href="#planos" class="btn btn-hero btn-outline-white">
                Ver Planos
            </a>
        </div>
    </div>
</section>

<!-- Como Funciona -->
<section class="section how-it-works">
    <div class="section-title">
        <h2>Como Funciona</h2>
        <p>Crie seu primeiro encarte em 3 passos simples</p>
    </div>
    
    <div class="steps-grid">
        <div class="step-card">
            <div class="step-number">1</div>
            <h3>Cadastre-se</h3>
            <p>Crie sua conta grátis em segundos e ganhe 7 dias de teste no plano Starter.</p>
        </div>
        
        <div class="step-card">
            <div class="step-number">2</div>
            <h3>Escolha um Template</h3>
            <p>Selecione entre 6 templates profissionais prontos para personalizar.</p>
        </div>
        
        <div class="step-card">
            <div class="step-number">3</div>
            <h3>Publique e Compartilhe</h3>
            <p>Personalize com seus produtos e baixe em PDF ou compartilhe nas redes sociais.</p>
        </div>
    </div>
</section>

<!-- Galeria de Templates -->
<section class="section">
    <div class="section-title">
        <h2>Templates Profissionais</h2>
        <p>Modelos prontos para cada tipo de negócio</p>
    </div>
    
    <div class="gallery-grid">
        <div class="gallery-item">🛒<br><small style="font-size: 0.8rem;">Mercado Semanal</small></div>
        <div class="gallery-item">⚡<br><small style="font-size: 0.8rem;">Promoção Relâmpago</small></div>
        <div class="gallery-item">🍽️<br><small style="font-size: 0.8rem;">Cardápio Simples</small></div>
        <div class="gallery-item">🏪<br><small style="font-size: 0.8rem;">Oferta Supermercado</small></div>
        <div class="gallery-item">🎉<br><small style="font-size: 0.8rem;">Aniversário Loja</small></div>
        <div class="gallery-item">🖤<br><small style="font-size: 0.8rem;">Black Friday</small></div>
    </div>
</section>

<!-- Planos e Preços -->
<section class="section pricing" id="planos">
    <div class="section-title">
        <h2>Planos e Preços</h2>
        <p>Escolha o plano ideal para o seu negócio</p>
    </div>
    
    <div class="pricing-grid">
        <!-- Plano Starter -->
        <div class="pricing-card">
            <h3>Starter</h3>
            <p class="description">Perfeito para pequenos negócios</p>
            <div class="price">R$ 29,90<span>/mês</span></div>
            <ul class="pricing-features">
                <li><span class="check-icon">✓</span> 10 encartes por mês</li>
                <li><span class="check-icon">✓</span> Templates básicos</li>
                <li><span class="check-icon">✓</span> Suporte por email</li>
                <li><span class="check-icon">✓</span> Exportação em PDF</li>
            </ul>
            <a href="<?php echo SITE_URL; ?>/auth/register.php?plan=starter" class="btn btn-outline" style="width: 100%;">
                Começar Grátis (7 dias)
            </a>
        </div>
        
        <!-- Plano Pro -->
        <div class="pricing-card featured">
            <h3>Pro</h3>
            <p class="description">Ideal para negócios em crescimento</p>
            <div class="price">R$ 59,90<span>/mês</span></div>
            <ul class="pricing-features">
                <li><span class="check-icon">✓</span> 50 encartes por mês</li>
                <li><span class="check-icon">✓</span> Todos os templates</li>
                <li><span class="check-icon">✓</span> Suporte prioritário</li>
                <li><span class="check-icon">✓</span> Exportação em PDF</li>
                <li><span class="check-icon">✓</span> Sem marca d'água</li>
            </ul>
            <a href="<?php echo SITE_URL; ?>/auth/register.php?plan=pro" class="btn btn-primary" style="width: 100%;">
                Começar Grátis (7 dias)
            </a>
        </div>
        
        <!-- Plano Enterprise -->
        <div class="pricing-card">
            <h3>Enterprise</h3>
            <p class="description">Para grandes volumes</p>
            <div class="price">R$ 149,90<span>/mês</span></div>
            <ul class="pricing-features">
                <li><span class="check-icon">✓</span> Encartes ilimitados</li>
                <li><span class="check-icon">✓</span> Todos os templates</li>
                <li><span class="check-icon">✓</span> Suporte 24/7</li>
                <li><span class="check-icon">✓</span> API de integração</li>
                <li><span class="check-icon">✓</span> White label</li>
            </ul>
            <a href="<?php echo SITE_URL; ?>/auth/register.php?plan=enterprise" class="btn btn-outline" style="width: 100%;">
                Falar com Vendas
            </a>
        </div>
    </div>
</section>

<!-- Depoimentos -->
<section class="section testimonials">
    <div class="section-title">
        <h2>O Que Nossos Clientes Dizem</h2>
        <p>Veja quem já usa e aprova o EncartePro</p>
    </div>
    
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <p class="testimonial-text">"O EncartePro revolucionou a forma como criamos nossas promoções semanais. Economizamos horas de trabalho e o resultado é profissional!"</p>
            <div class="testimonial-author">
                <div class="author-avatar">MS</div>
                <div class="author-info">
                    <h4>Maria Silva</h4>
                    <p>Dono de Mercado</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial-card">
            <p class="testimonial-text">"Simples, rápido e eficiente. Crio meus cardápios promocionais em minutos e meus clientes adoram!"</p>
            <div class="testimonial-author">
                <div class="author-avatar">JO</div>
                <div class="author-info">
                    <h4>João Oliveira</h4>
                    <p>Restaurante Sabor & Arte</p>
                </div>
            </div>
        </div>
        
        <div class="testimonial-card">
            <p class="testimonial-text">"Melhor investimento que fiz para minha loja. Os templates são lindos e muito fáceis de usar."</p>
            <div class="testimonial-author">
                <div class="author-avatar">AS</div>
                <div class="author-info">
                    <h4>Ana Santos</h4>
                    <p>Boutique Fashion</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section faq">
    <div class="section-title">
        <h2>Perguntas Frequentes</h2>
        <p>Tire suas dúvidas sobre o EncartePro</p>
    </div>
    
    <div class="faq-container">
        <div class="faq-item">
            <div class="faq-question">
                Preciso de experiência com design?
                <span class="faq-icon">+</span>
            </div>
            <div class="faq-answer">
                Não! Nossos templates são pré-configurados e extremamente fáceis de usar. Basta escolher um modelo, editar os textos e imagens, e pronto!
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                Posso cancelar quando quiser?
                <span class="faq-icon">+</span>
            </div>
            <div class="faq-answer">
                Sim! Você pode cancelar sua assinatura a qualquer momento, sem taxas ou burocracia. Seu acesso permanece ativo até o final do período pago.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                Quais formas de pagamento são aceitas?
                <span class="faq-icon">+</span>
            </div>
            <div class="faq-answer">
                Aceitamos cartão de crédito, PIX e boleto bancário através do Mercado Pago. O acesso é liberado imediatamente após a confirmação.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                Posso testar antes de assinar?
                <span class="faq-icon">+</span>
            </div>
            <div class="faq-answer">
                Sim! Oferecemos 7 dias grátis no plano Starter para você conhecer todas as funcionalidades sem compromisso.
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question">
                Os encartes ficam salvos?
                <span class="faq-icon">+</span>
            </div>
            <div class="faq-answer">
                Sim! Todos os seus encartes ficam salvos na sua conta e você pode editá-los ou baixá-los quantas vezes quiser.
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="cta-final">
    <div style="max-width: 600px; margin: 0 auto; padding: 0 20px;">
        <h2>Comece hoje mesmo — 7 dias grátis!</h2>
        <p>Não precisa de cartão de crédito para começar. Crie sua conta e descubra como é fácil criar encartes profissionais.</p>
        <a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-hero btn-white">
            🎁 Criar Conta Grátis
        </a>
    </div>
</section>

<script>
    // FAQ Accordion
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const item = question.parentElement;
            const isActive = item.classList.contains('active');
            
            // Fecha todos
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
            
            // Abre o clicado se não estava aberto
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
    
    // Smooth scroll para links internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
