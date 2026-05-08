<?php
/**
 * EncartePro - Login de Usuário
 */

require_once __DIR__ . '/../includes/config.php';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/');
    } else {
        redirect(SITE_URL . '/dashboard/');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validações
    if (empty($email)) {
        $errors[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($password)) {
        $errors[] = 'Senha é obrigatória';
    }
    
    if (empty($errors)) {
        $db = getDB();
        
        // Busca usuário por email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Verifica se a conta está ativa
            if ($user['status'] !== 'active') {
                $errors[] = 'Sua conta está inativa ou suspensa. Contate o suporte.';
            } else {
                // Cria sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                
                // Remember me (opcional)
                if ($remember) {
                    setcookie('remember_token', bin2hex(random_bytes(32)), time() + (86400 * 30), '/');
                }
                
                // Redireciona baseado na role
                if ($user['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/');
                } else {
                    redirect(SITE_URL . '/dashboard/');
                }
            }
        } else {
            $errors[] = 'Email ou senha incorretos';
        }
    }
}

$pageTitle = 'Entrar';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .auth-container {
        max-width: 450px;
        margin: 2rem auto;
    }
    
    .auth-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        padding: 2.5rem;
    }
    
    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .auth-header h1 {
        font-family: var(--font-heading);
        color: var(--primary-color);
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    .auth-header p {
        color: #666;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
    
    .form-options label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        color: #666;
    }
    
    .btn-block {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 1.5rem;
        color: #666;
    }
    
    .auth-footer a {
        color: var(--primary-color);
        font-weight: 600;
    }
    
    .divider {
        text-align: center;
        margin: 1.5rem 0;
        position: relative;
    }
    
    .divider::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        width: 100%;
        height: 1px;
        background: #eee;
    }
    
    .divider span {
        background: white;
        padding: 0 1rem;
        color: #999;
        font-size: 0.9rem;
        position: relative;
    }
    
    .errors-list {
        background: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid #f5c6cb;
    }
    
    .errors-list ul {
        margin: 0;
        padding-left: 1.5rem;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Bem-vindo de volta!</h1>
            <p>Faça login para acessar sua conta</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="errors-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    Lembrar de mim
                </label>
                <a href="<?php echo SITE_URL; ?>/auth/forgot-password.php" style="color: var(--primary-color);">
                    Esqueceu a senha?
                </a>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                Entrar
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Não tem uma conta? <a href="<?php echo SITE_URL; ?>/auth/register.php">Cadastre-se grátis</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
