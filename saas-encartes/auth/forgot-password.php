<?php
/**
 * EncartePro - Recuperação de Senha (Forgot Password)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mail.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($errors)) {
        $db = getDB();
        
        // Busca usuário por email
        $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = :email AND status = 'active'");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            // Gera token de reset
            $resetToken = bin2hex(random_bytes(32));
            $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Salva token no banco
            $stmt = $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
            $stmt->execute([
                ':token' => $resetToken,
                ':expires' => $resetExpires,
                ':id' => $user['id']
            ]);
            
            // Envia email de recuperação
            sendResetPasswordEmail($user['email'], $user['name'], $resetToken);
            
            $success = 'Enviamos um email com instruções para redefinir sua senha. O link expira em 1 hora.';
        } else {
            // Mensagem genérica por segurança
            $success = 'Se este email estiver cadastrado, você receberá instruções para redefinir sua senha.';
        }
    }
}

$pageTitle = 'Recuperar Senha';
requireLogin = false;
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
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .form-group input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 1rem;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .btn-block {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 1.5rem;
    }
    
    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid #c3e6cb;
    }
    
    .errors-list {
        background: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .errors-list ul {
        margin: 0;
        padding-left: 1.5rem;
    }
    
    .info-box {
        background: #e7f3ff;
        border: 1px solid #b3d9ff;
        color: #004085;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>🔐 Recuperar Senha</h1>
            <p>Digite seu email para receber instruções</p>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">
                ✅ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="errors-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            💡 Você receberá um email com um link para redefinir sua senha. 
            O link expira em 1 hora.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       required autofocus>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                Enviar Instruções
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Lembrou sua senha? <a href="<?php echo SITE_URL; ?>/auth/login.php">Voltar ao login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
