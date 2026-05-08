<?php
/**
 * FlyerSaaS - Página de Login
 */

require_once __DIR__ . '/config.php';

// Se já estiver logado, redireciona
if (Auth::check()) {
    $user = Auth::user();
    if ($user['is_admin']) {
        header('Location: admin/index.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Preencha e-mail e senha.';
    } else {
        $result = Auth::login($email, $password);
        
        if ($result['success']) {
            // Redireciona conforme tipo de usuário
            if ($result['user']['is_admin']) {
                header('Location: admin/index.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
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
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card { max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card shadow-lg">
                <div class="card-header bg-white text-center py-4">
                    <h2 class="mb-0 text-primary">🛒 FlyerSaaS</h2>
                    <p class="text-muted mb-0">Acesse sua conta</p>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= sanitize($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            Entrar
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            Ainda não tem conta?<br>
                            <a href="register.php">Cadastre-se aqui</a>
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="text-center text-white mt-3">
                <small>FlyerSaaS v1.0 - Sistema de Encartes Digitais</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
