<?php
/**
 * FlyerSaaS - Página de Registro
 */
require_once 'config.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$error = '';
$success = '';

// Processar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitize($_POST['name'] ?? ''),
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'store_name' => sanitize($_POST['store_name'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'cnpj' => sanitize($_POST['cnpj'] ?? ''),
        'plan_id' => (int)($_POST['plan_id'] ?? 1)
    ];

    // Validações
    if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
        $error = 'Preencha todos os campos obrigatórios';
    } elseif ($data['password'] !== $data['password_confirm']) {
        $error = 'As senhas não coincidem';
    } elseif (strlen($data['password']) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido';
    } else {
        require_once __DIR__ . '/includes/Database.php';
        require_once __DIR__ . '/includes/Auth.php';
        
        $auth = new Auth();
        $result = $auth->register($data);
        
        if ($result['success']) {
            $success = 'Conta criada com sucesso! Redirecionando para login...';
            header('refresh:2;url=' . BASE_URL . '/login.php');
        } else {
            $error = $result['error'];
        }
    }
}

// Carregar planos disponíveis
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/SubscriptionManager.php';
$subManager = new SubscriptionManager();
$plans = $subManager->getAvailablePlans();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding-top: 40px; }
        .register-card { max-width: 700px; margin: 0 auto; }
        .plan-card { cursor: pointer; border: 2px solid transparent; transition: all 0.3s; }
        .plan-card:hover { transform: translateY(-5px); }
        .plan-card.selected { border-color: #0d6efd; background: #f0f7ff; }
    </style>
</head>
<body>
    <div class="container mb-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-primary">🛒 FlyerSaaS</h2>
            <p class="text-muted">Crie sua conta e comece a criar encartes digitais</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card register-card shadow">
            <div class="card-body p-4">
                <form method="POST">
                    <h5 class="mb-3">Dados Pessoais</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">E-mail *</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Dados da Loja</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="store_name" class="form-label">Nome da Loja</label>
                            <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo htmlspecialchars($_POST['store_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="cnpj" class="form-label">CNPJ/CPF</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" value="<?php echo htmlspecialchars($_POST['cnpj'] ?? ''); ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 mt-4">Escolha seu Plano</h5>
                    <div class="row mb-4">
                        <?php foreach ($plans as $plan): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card plan-card h-100" onclick="selectPlan(<?php echo $plan['id']; ?>)">
                                <div class="card-body text-center">
                                    <input type="radio" name="plan_id" value="<?php echo $plan['id']; ?>" id="plan_<?php echo $plan['id']; ?>" class="d-none" <?php echo ((int)($_POST['plan_id'] ?? 1) === $plan['id']) ? 'checked' : ''; ?>>
                                    <h6 class="card-title"><?php echo htmlspecialchars($plan['name']); ?></h6>
                                    <h4 class="text-primary">R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?></h4>
                                    <small class="text-muted">/<?php echo $plan['duration_days']; ?> dias</small>
                                    <hr>
                                    <ul class="list-unstyled small">
                                        <li>📸 <?php echo $plan['images_limit']; ?> imagens</li>
                                        <li>📄 <?php echo $plan['flyers_limit']; ?> encartes</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <h5 class="mb-3">Segurança</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Senha *</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Confirmar Senha *</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Criar Conta</button>
                    </div>
                </form>

                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Já tem conta?</p>
                    <a href="login.php" class="btn btn-outline-primary">Fazer Login</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function selectPlan(planId) {
        document.querySelectorAll('.plan-card').forEach(card => card.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        document.getElementById('plan_' + planId).checked = true;
    }
    
    // Selecionar plano inicial
    document.addEventListener('DOMContentLoaded', function() {
        const checked = document.querySelector('input[name="plan_id"]:checked');
        if (checked) {
            document.getElementById('plan_' + checked.value).closest('.plan-card').classList.add('selected');
        }
    });
    </script>
</body>
</html>
