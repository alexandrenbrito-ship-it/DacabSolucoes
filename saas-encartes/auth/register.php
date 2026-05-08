<?php
/**
 * EncartePro - Registro de Usuário
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mail.php';

// Se já estiver logado, redireciona
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    // Validações
    if (empty($name)) {
        $errors[] = 'Nome é obrigatório';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Nome deve ter pelo menos 3 caracteres';
    }
    
    if (empty($email)) {
        $errors[] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($password)) {
        $errors[] = 'Senha é obrigatória';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres';
    }
    
    if ($password !== $passwordConfirm) {
        $errors[] = 'Senhas não conferem';
    }
    
    if (empty($errors)) {
        $db = getDB();
        
        // Verifica se email já existe
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $errors[] = 'Este email já está cadastrado';
        } else {
            try {
                $db->beginTransaction();
                
                // Hash da senha
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $verificationToken = bin2hex(random_bytes(32));
                
                // Cria usuário
                $stmt = $db->prepare("
                    INSERT INTO users (name, email, password, role, status, verification_token, created_at)
                    VALUES (:name, :email, :password, 'user', 'active', :token, NOW())
                ");
                
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':token' => $verificationToken
                ]);
                
                $userId = $db->lastInsertId();
                
                // Atribui plano trial (7 dias)
                $stmt = $db->prepare("SELECT id FROM plans WHERE name = 'Starter' LIMIT 1");
                $stmt->execute();
                $plan = $stmt->fetch();
                
                if ($plan) {
                    $stmt = $db->prepare("
                        INSERT INTO subscriptions (user_id, plan_id, status, starts_at, expires_at, created_at)
                        VALUES (:user_id, :plan_id, 'trial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NOW())
                    ");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':plan_id' => $plan['id']
                    ]);
                }
                
                $db->commit();
                
                // Envia email de boas-vindas
                sendWelcomeEmail($email, $name, $verificationToken);
                
                setFlashMessage('success', 'Conta criada com sucesso! Faça login para continuar.');
                redirect(SITE_URL . '/auth/login.php');
                
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Erro ao criar conta: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Criar Conta';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .auth-container {
        max-width: 500px;
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
        margin-bottom: 1.25rem;
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
    
    .trial-badge {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        display: inline-block;
        margin-bottom: 1.5rem;
        font-weight: 600;
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
</style>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Comece Grátis!</h1>
            <p>Crie sua conta em segundos</p>
        </div>
        
        <div class="text-center">
            <span class="trial-badge">🎁 7 dias grátis no plano Starter</span>
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
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                       required autofocus>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirmar Senha</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">
                Criar Conta Grátis
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Já tem uma conta? <a href="<?php echo SITE_URL; ?>/auth/login.php">Faça login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
