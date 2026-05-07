<?php
/**
 * /encarts/index.php
 * Landing page / Login / Registro
 */

// Iniciar sessão para CSRF
session_start();

// Carregar classes
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();

// Se já estiver autenticado, redirecionar para dashboard
if ($auth->isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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
                <li><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Entrar</button></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="text-center text-white">
            <h1 class="display-4 fw-bold mb-4">Crie Designs Profissionais em Minutos</h1>
            <p class="lead mb-4 opacity-75">Editor visual intuitivo para posts, stories, banners e muito mais.<br>Sem necessidade de experiência com design.</p>
            <button class="btn btn-light btn-lg px-5" data-bs-toggle="modal" data-bs-target="#registerModal">
                <i class="bi bi-rocket-takeoff"></i> Começar Gratuitamente
            </button>
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
                            <button class="btn btn-outline-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#registerModal">Começar Grátis</button>
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
                            <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#registerModal">Assinar Pro</button>
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

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Entrar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div id="loginError" class="alert alert-danger d-none"></div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Não tem conta? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Cadastre-se</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Criar Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" name="password" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Plano</label>
                            <select class="form-select" name="plan">
                                <option value="free">Free - Gratuito</option>
                                <option value="pro">Pro - R$29/mês</option>
                            </select>
                        </div>
                        <div id="registerError" class="alert alert-danger d-none"></div>
                        <button type="submit" class="btn btn-primary w-100">Criar Conta</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Já tem conta? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Entrar</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/encarts/assets/js/app.js"></script>
    <script>
        // Login Form
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const email = formData.get('email');
            const password = formData.get('password');
            
            try {
                const result = await login(email, password);
                if (result.success) {
                    window.location.href = '/encarts/dashboard.php';
                } else {
                    const errorDiv = document.getElementById('loginError');
                    errorDiv.textContent = result.message;
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Login error:', error);
            }
        });

        // Register Form
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            try {
                const result = await register(
                    formData.get('name'),
                    formData.get('email'),
                    formData.get('password'),
                    formData.get('plan')
                );
                
                if (result.success) {
                    showToast('Conta criada com sucesso! Faça login.', 'success');
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                        modal.hide();
                        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                        loginModal.show();
                    }, 1500);
                } else {
                    const errorDiv = document.getElementById('registerError');
                    errorDiv.textContent = result.message;
                    errorDiv.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Register error:', error);
            }
        });
    </script>
</body>
</html>
