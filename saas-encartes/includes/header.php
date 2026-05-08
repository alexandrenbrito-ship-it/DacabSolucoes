<?php
/**
 * EncartePro - Header Comum
 * Incluído em todas as páginas do sistema
 */

// Previne acesso direto
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/config.php';
}

// Verifica se o usuário deve estar logado
$requireLogin = isset($requireLogin) ? $requireLogin : false;
$requireAdmin = isset($requireAdmin) ? $requireAdmin : false;

if ($requireLogin && !isLoggedIn()) {
    setFlashMessage('error', 'Faça login para acessar esta página.');
    redirect(SITE_URL . '/auth/login.php');
}

if ($requireAdmin && !isAdmin()) {
    setFlashMessage('error', 'Acesso não permitido.');
    redirect(SITE_URL . '/dashboard/');
}

// Obtém mensagem flash se existir
$flashMessage = getFlashMessage();

// Obtém dados do usuário logado
$userData = null;
$userPlan = null;
if (isLoggedIn()) {
    $db = getDB();
    if ($db) {
        $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $userData = $stmt->fetch();
        
        if ($userData && $userData['role'] !== 'admin') {
            $userPlan = getUserPlan($_SESSION['user_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos -->
    <style>
        :root {
            --primary-color: #e8401c;
            --secondary-color: #f5a623;
            --dark-color: #1a1a1a;
            --light-color: #f8f9fa;
            --gray-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --font-heading: 'Syne', sans-serif;
            --font-body: 'DM Sans', sans-serif;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-body);
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: 700;
            line-height: 1.2;
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        a:hover {
            color: var(--secondary-color);
        }
        
        /* Navbar */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
        }
        
        .navbar-nav a {
            color: var(--dark-color);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .navbar-nav a:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .navbar-nav .btn {
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            font-weight: 600;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #c93516;
            color: white;
        }
        
        /* Container principal */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
            min-height: calc(100vh - 200px);
        }
        
        /* Mensagens Flash */
        .flash-message {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .flash-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .flash-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .flash-message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .flash-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Botões */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            color: var(--dark-color);
        }
        
        /* Grid responsivo */
        .grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        .grid-4 {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        /* Utilitários */
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .mt-1 { margin-top: 0.5rem; }
        .mt-2 { margin-top: 1rem; }
        .mt-3 { margin-top: 1.5rem; }
        .mb-1 { margin-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 1rem; }
        .mb-3 { margin-bottom: 1.5rem; }
        
        .hidden {
            display: none;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .navbar-nav {
                gap: 0.5rem;
            }
            
            .navbar-nav a {
                padding: 0.3rem 0.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
    
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="<?php echo SITE_URL; ?>" class="navbar-brand">
                <?php echo SITE_NAME; ?>
            </a>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo SITE_URL; ?>/admin/">Admin</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard/">Dashboard</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/dashboard/my-encartes.php">Meus Encartes</a></li>
                    <?php endif; ?>
                    
                    <li>
                        <span style="color: var(--gray-color); font-size: 0.9rem;">
                            Olá, <?php echo htmlspecialchars($userData['name']); ?>
                        </span>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="btn btn-outline">
                            Sair
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/auth/login.php">Entrar</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/auth/register.php" class="btn btn-primary">Começar Grátis</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- Mensagem Flash -->
    <?php if ($flashMessage): ?>
        <div class="main-container" style="padding-bottom: 0;">
            <div class="flash-message <?php echo $flashMessage['type']; ?>">
                <?php echo htmlspecialchars($flashMessage['message']); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Container Principal -->
    <div class="main-container">
