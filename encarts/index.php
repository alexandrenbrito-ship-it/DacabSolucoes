<?php
/**
 * /encarts/index.php
 * Landing page com login Clerk
 */

// Carregar configuração do Clerk
require_once __DIR__ . '/config/clerk.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encarts - Crie Designs Profissionais</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/encarts/assets/css/style.css">
    
    <!-- Clerk JS -->
    <script src="https://cdn.jsdelivr.net/npm/@clerk/clerk-js@latest/dist/clerk.browser.js"></script>
    
    <style>
        .feature-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        #clerk-login {
            max-width: 400px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">
                <i class="bi bi-layers-fill"></i>
                Encarts
            </a>
            <ul class="navbar-nav">
                <li><a href="#features" class="nav-link">Recursos</a></li>
                <li><a href="#pricing" class="nav-link">Planos</a></li>
                <li><button class="btn btn-primary" id="loginBtn">Entrar</button></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-4">Crie Designs Profissionais em Minutos</h1>
            <p class="lead mb-4 opacity-75">Editor visual intuitivo para posts, stories, banners e muito mais.<br>Sem necessidade de experiência com design.</p>
            <button class="btn btn-light btn-lg px-5" id="signupBtn">
                <i class="bi bi-rocket-takeoff"></i> Começar Gratuitamente
            </button>
        </div>
    </section>

    <!-- Login Section (oculta por padrão) -->
    <section class="py-5" id="loginSection" style="display: none;">
        <div class="container">
            <div id="clerk-login"></div>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Por que escolher o Encarts?</h2>
                <p class="text-muted">Tudo o que você precisa para criar designs incríveis</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="feature-icon"><i class="bi bi-palette"></i></div>
                    <h5>Templates Prontos</h5>
                    <p class="text-muted">Centenas de templates profissionais para todas as ocasiões</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon"><i class="bi bi-mouse"></i></div>
                    <h5>Editor Visual</h5>
                    <p class="text-muted">Arraste e solte elementos facilmente no canvas</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon"><i class="bi bi-download"></i></div>
                    <h5>Exportação Fácil</h5>
                    <p class="text-muted">Baixe em PNG ou JPG com qualidade profissional</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Planos Simples</h2>
                <p class="text-muted">Escolha o plano ideal para você</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body text-center p-4">
                            <h4 class="fw-bold">Free</h4>
                            <div class="display-5 fw-bold my-3">R$0</div>
                            <p class="text-muted">Para sempre gratuito</p>
                            <hr>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Até 5 encarts</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Templates básicos</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Exportação em PNG/JPG</li>
                                <li class="mb-2"><i class="bi bi-x-circle-fill text-muted me-2"></i>Templates premium</li>
                            </ul>
                            <button class="btn btn-outline-primary w-100 mt-3" id="signupBtn2">Começar Grátis</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="card h-100 border-primary">
                        <div class="card-header bg-primary text-white text-center">MAIS POPULAR</div>
                        <div class="card-body text-center p-4">
                            <h4 class="fw-bold">Pro</h4>
                            <div class="display-5 fw-bold my-3">R$29<span class="fs-6 text-muted">/mês</span></div>
                            <p class="text-muted">Para profissionais</p>
                            <hr>
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Encarts ilimitados</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todos os templates</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Elementos premium</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Suporte prioritário</li>
                            </ul>
                            <button class="btn btn-primary w-100 mt-3" id="signupBtn3">Assinar Pro</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 border-top">
        <div class="container text-center text-muted">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Encarts. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/encarts/assets/js/app.js"></script>
    <script>
        // Inicializar Clerk
        const clerkPublishableKey = '<?php echo CLERK_PUBLISHABLE_KEY; ?>';
        let clerk = null;
        
        async function initClerk() {
            if (!clerkPublishableKey) {
                console.error('CLERK_PUBLISHABLE_KEY não configurado');
                return;
            }
            
            try {
                await clerk.load({
                    signInUrl: window.location.origin + '/encarts/',
                    signUpUrl: window.location.origin + '/encarts/'
                });
                
                // Verificar se já está logado
                if (clerk.user) {
                    window.location.href = '/encarts/dashboard.php';
                    return;
                }
                
                // Montar componente de sign-in
                clerk.mountSignIn(document.getElementById('clerk-login'), {
                    afterSignInUrl: '/encarts/dashboard.php',
                    afterSignUpUrl: '/encarts/dashboard.php'
                });
            } catch (error) {
                console.error('Erro ao inicializar Clerk:', error);
            }
        }
        
        // Mostrar formulário de login
        function showLoginForm() {
            document.getElementById('loginSection').style.display = 'block';
            document.getElementById('loginSection').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Clerk
            initClerk();
            
            // Botões de login/signup
            document.getElementById('loginBtn')?.addEventListener('click', showLoginForm);
            document.getElementById('signupBtn')?.addEventListener('click', showLoginForm);
            document.getElementById('signupBtn2')?.addEventListener('click', showLoginForm);
            document.getElementById('signupBtn3')?.addEventListener('click', showLoginForm);
        });
    </script>
</body>
</html>
