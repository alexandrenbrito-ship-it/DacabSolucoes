<?php
/**
 * EncartePro - Redefinição de Senha (Reset Password)
 */

require_once __DIR__ . '/../includes/config.php';

$errors = [];
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

// Valida o token
if (!empty($token)) {
    $db = getDB();
    
    // Busca usuário com token válido
    $stmt = $db->prepare("
        SELECT id, name, email FROM users 
        WHERE reset_token = :token 
        AND reset_expires > NOW() 
        AND status = 'active'
    ");
    $stmt->bindValue(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
    } else {
        $errors[] = 'Link inválido ou expirado. Solicite uma nova recuperação de senha.';
    }
} else {
    $errors[] = 'Token não fornecido.';
}

// Processa a redefinição de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password)) {
        $errors[] = 'Senha é obrigatória';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres';
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = 'Senhas não conferem';
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Atualiza senha e limpa token
            $stmt = $db->prepare("
                UPDATE users 
                SET password = :password, reset_token = NULL, reset_expires = NULL 
                WHERE id = :id
            ");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':id' => $user['id']
            ]);
            
            $success = 'Senha redefinida com sucesso! Você já pode fazer login.';
            
        } catch (Exception $e) {
            $errors[] = 'Erro ao redefinir senha: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Redefinir Senha';
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
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid #c3e6cb;
        text-align: center;
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
    
    .expired-box {
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #856404;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
    }
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>🔑 Redefinir Senha</h1>
            <p>Crie uma nova senha para sua conta</p>
        </div>
        
        <?php if ($success): ?>
            <div class="success-message">
                ✅ <?php echo htmlspecialchars($success); ?>
                <br><br>
                <a href="<?php echo SITE_URL; ?>/auth/login.php" class="btn btn-primary">
                    Ir para Login
                </a>
            </div>
        <?php elseif (!empty($errors) && !$validToken): ?>
            <div class="errors-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="expired-box">
                <p>O link de redefinição de senha expirou ou é inválido.</p>
                <br>
                <a href="<?php echo SITE_URL; ?>/auth/forgot-password.php" class="btn btn-primary">
                    Solicitar Novo Link
                </a>
            </div>
        <?php else: ?>
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
                    <label for="password">Nova Senha</label>
                    <input type="password" id="password" name="password" required minlength="6" autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Confirmar Nova Senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Redefinir Senha
                </button>
            </form>
        <?php endif; ?>
        
        <div class="auth-footer">
            <p>Lembrou sua senha? <a href="<?php echo SITE_URL; ?>/auth/login.php">Voltar ao login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
