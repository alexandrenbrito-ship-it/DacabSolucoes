<?php
/**
 * FlyerSaaS - Dashboard do Usuário
 */
require_once '../config.php';

// Verificar autenticação
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/login.php');
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SubscriptionManager.php';

$auth = new Auth();
$subManager = new SubscriptionManager();

$user = $auth->getCurrentUser();
$subscription = $subManager->getCurrentSubscription();
$usage = $subManager->getUsageSummary($user['id']);

// Estatísticas rápidas
$db = Database::getInstance()->getConnection();

// Total de encartes
$stmt = $db->prepare("SELECT COUNT(*) as total FROM flyers WHERE user_id = ?");
$stmt->execute([$user['id']]);
$totalFlyers = $stmt->fetch()['total'];

// Total de imagens enviadas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM gallery_images WHERE uploaded_by = ?");
$stmt->execute([$user['id']]);
$totalImages = $stmt->fetch()['total'];

// Encartes recentes
$stmt = $db->prepare("SELECT * FROM flyers WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recentFlyers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; }
        .sidebar a i { width: 25px; }
        .stat-card { border-left: 4px solid; }
        .progress { height: 10px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 bg-dark text-white">
                    <h5 class="mb-0">🛒 FlyerSaaS</h5>
                </div>
                <nav class="mt-3">
                    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="flyers.php"><i class="fas fa-newspaper"></i> Encartes</a>
                    <a href="gallery.php"><i class="fas fa-images"></i> Galeria de Imagens</a>
                    <a href="upload-image.php"><i class="fas fa-upload"></i> Upload de Imagem</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Meu Perfil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Olá, <?php echo htmlspecialchars($user['name']); ?>! 👋</h2>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($user['store_name'] ?? 'Minha Loja'); ?></span>
                </div>

                <!-- Alerta de assinatura -->
                <?php if (!$subscription): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Você não possui uma assinatura ativa. 
                    <a href="#" class="alert-link">Assine um plano para continuar</a>.
                </div>
                <?php endif; ?>

                <!-- Cards de Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card border-primary shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Encartes Criados</h6>
                                <h3><?php echo $totalFlyers; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-success shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Imagens Enviadas</h6>
                                <h3><?php echo $totalImages; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-info shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Plano Atual</h6>
                                <h5><?php echo htmlspecialchars($usage['plan_name']); ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card border-warning shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Validade</h6>
                                <h6><?php echo $usage['end_date'] ? date('d/m/Y', strtotime($usage['end_date'])) : '-'; ?></h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limites de Uso -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <i class="fas fa-images"></i> Limite de Imagens
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo $usage['images_used']; ?> de <?php echo $usage['images_limit']; ?> usadas</span>
                                    <span><?php echo round(($usage['images_used'] / $usage['images_limit']) * 100); ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar <?php echo ($usage['images_used'] / $usage['images_limit']) > 0.8 ? 'bg-danger' : 'bg-success'; ?>" 
                                         style="width: <?php echo ($usage['images_used'] / $usage['images_limit']) * 100; ?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $usage['images_limit'] - $usage['images_used']; ?> imagens restantes
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <i class="fas fa-newspaper"></i> Limite de Encartes
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo $usage['flyers_used']; ?> de <?php echo $usage['flyers_limit']; ?> usados</span>
                                    <span><?php echo round(($usage['flyers_used'] / $usage['flyers_limit']) * 100); ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar <?php echo ($usage['flyers_used'] / $usage['flyers_limit']) > 0.8 ? 'bg-danger' : 'bg-success'; ?>" 
                                         style="width: <?php echo ($usage['flyers_used'] / $usage['flyers_limit']) * 100; ?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $usage['flyers_limit'] - $usage['flyers_used']; ?> encartes restantes
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5>Ações Rápidas</h5>
                    </div>
                    <div class="col-md-3">
                        <a href="flyers.php?action=create" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-plus"></i> Novo Encarte
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="upload-image.php" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-upload"></i> Upload de Imagem
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="gallery.php" class="btn btn-info w-100 mb-2 text-white">
                            <i class="fas fa-images"></i> Ver Galeria
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="profile.php" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-cog"></i> Configurações
                        </a>
                    </div>
                </div>

                <!-- Encartes Recentes -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock"></i> Encartes Recentes</span>
                        <a href="flyers.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentFlyers)): ?>
                        <p class="text-muted text-center mb-0">Nenhum encarte criado ainda.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentFlyers as $flyer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($flyer['title']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $flyer['status'] === 'published' ? 'success' : ($flyer['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($flyer['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($flyer['created_at'])); ?></td>
                                        <td>
                                            <a href="flyers.php?action=view&id=<?php echo $flyer['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
