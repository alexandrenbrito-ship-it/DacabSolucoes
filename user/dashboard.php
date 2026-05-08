<?php
require_once __DIR__ . '/../config.php';
Auth::requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();
$user = Auth::user();

// Busca assinatura ativa
$subscription = null;
$stmt = $conn->prepare("
    SELECT us.*, p.name as plan_name, p.price, p.images_limit, p.flyers_limit 
    FROM user_subscriptions us
    INNER JOIN plans p ON us.plan_id = p.id
    WHERE us.user_id = ? AND us.status = 'active' AND us.end_date > NOW()
    LIMIT 1
");
$stmt->execute([$user['id']]);
$subscription = $stmt->fetch();

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🛒 FlyerSaaS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="gallery.php">Galeria</a></li>
                    <li class="nav-item"><a class="nav-link" href="flyers.php">Encartes</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php displayFlashMessage(); ?>
        
        <div class="row mb-4">
            <div class="col-12">
                <h2>👋 Olá, <?= sanitize($user['name']) ?>!</h2>
                <p class="text-muted">Bem-vindo ao seu painel de controle.</p>
            </div>
        </div>
        
        <?php if ($subscription): ?>
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-stat bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Plano Atual</h5>
                        <h2 class="display-6"><?= sanitize($subscription['plan_name']) ?></h2>
                        <p class="mb-0"><?= formatMoney($subscription['price']) ?>/mês</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-stat bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Imagens</h5>
                        <h2 class="display-6"><?= $subscription['images_used'] ?> / <?= $subscription['images_limit'] ?></h2>
                        <p class="mb-0"><?= round(($subscription['images_used'] / $subscription['images_limit']) * 100) ?>% utilizado</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card card-stat bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Encartes</h5>
                        <h2 class="display-6"><?= $subscription['flyers_used'] ?> / <?= $subscription['flyers_limit'] ?></h2>
                        <p class="mb-0"><?= round(($subscription['flyers_used'] / $subscription['flyers_limit']) * 100) ?>% utilizado</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-warning">
            <strong>📅 Período da assinatura:</strong> 
            <?= date('d/m/Y', strtotime($subscription['start_date'])) ?> até <?= date('d/m/Y', strtotime($subscription['end_date'])) ?>
        </div>
        <?php else: ?>
        <div class="alert alert-danger">
            <strong>⚠️ Você não possui uma assinatura ativa!</strong><br>
            Entre em contato com o administrador para ativar seu plano.
        </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">⚡ Acesso Rápido</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="upload-image.php" class="btn btn-outline-primary">
                                <i class="fas fa-upload"></i> Upload de Imagem
                            </a>
                            <a href="gallery.php" class="btn btn-outline-primary">
                                <i class="fas fa-images"></i> Ver Galeria Completa
                            </a>
                            <a href="flyers.php" class="btn btn-outline-primary">
                                <i class="fas fa-file-alt"></i> Meus Encartes
                            </a>
                            <a href="flyers.php?action=create" class="btn btn-outline-success">
                                <i class="fas fa-plus"></i> Criar Novo Encarte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">📊 Resumo da Conta</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Nome:</strong></td>
                                <td><?= sanitize($user['name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>E-mail:</strong></td>
                                <td><?= sanitize($user['email']) ?></td>
                            </tr>
                            <?php if ($user['store_name']): ?>
                            <tr>
                                <td><strong>Loja:</strong></td>
                                <td><?= sanitize($user['store_name']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
