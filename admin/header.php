<?php
/**
 * Admin Header - Cabeçalho comum para todas as páginas admin
 */
if (!isset($pageTitle)) $pageTitle = 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - FlyerSaaS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; border-left: 4px solid #3498db; }
        .sidebar i { width: 25px; }
        .content { padding: 30px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar p-0">
                <div class="py-4 px-3 bg-dark">
                    <h4 class="text-white mb-0">🛒 FlyerSaaS</h4>
                    <small class="text-muted">Painel Admin</small>
                </div>
                
                <div class="position-sticky pt-3">
                    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i> Usuários
                    </a>
                    <a href="plans.php" class="<?= basename($_SERVER['PHP_SELF']) == 'plans.php' ? 'active' : '' ?>">
                        <i class="fas fa-box"></i> Planos
                    </a>
                    <a href="config.php" class="<?= basename($_SERVER['PHP_SELF']) == 'config.php' ? 'active' : '' ?>">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                    <a href="db-update.php" class="<?= basename($_SERVER['PHP_SELF']) == 'db-update.php' ? 'active' : '' ?>">
                        <i class="fas fa-database"></i> Atualizar Banco
                    </a>
                    
                    <hr class="my-3 mx-3">
                    
                    <a href="../user/dashboard.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Ver Site
                    </a>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </nav>
            
            <!-- Conteúdo Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 content">
                <!-- Navbar Superior -->
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                    <h4 class="mb-0"><?= sanitize($pageTitle) ?></h4>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= sanitize(Auth::user()['name']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                        </ul>
                    </div>
                </div>
                
                <?php displayFlashMessage(); ?>
