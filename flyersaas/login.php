<?php
/**
 * FlyerSaaS - Página de Login
 */
require_once 'config.php';

// Se já estiver logado, redirecionar
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        redirect(BASE_URL . '/admin/index.php');
    } else {
        redirect(BASE_URL . '/user/dashboard.php');
    }
}

$error = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Preencha e-mail e senha';
    } else {
        require_once __DIR__ . '/includes/Database.php';
        require_once __DIR__ . '/includes/Auth.php';
        
        $auth = new Auth();
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            if ($result['user']['is_admin']) {
                redirect(BASE_URL . '/admin/index.php');
            } else {
                redirect(BASE_URL . '/user/dashboard.php');
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary">🛒 FlyerSaaS</h2>
                    <p class="text-muted">Crie encartes digitais incríveis</p>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">Entrar</button>
                </form>

                <hr>
                
                <div class="text-center">
                    <p class="mb-0">Não tem conta?</p>
                    <a href="register.php" class="btn btn-outline-primary w-100">Criar Conta</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
