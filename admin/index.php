<?php
/**
 * Painel Admin - Dashboard
 */
require_once __DIR__ . '/../config.php';
Auth::requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Estatísticas
$stats = [];

// Total de usuários
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['total_users'] = $stmt->fetch()['total'];

// Usuários ativos
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active' AND is_admin = 0");
$stats['active_users'] = $stmt->fetch()['total'];

// Total de encartes
$stmt = $conn->query("SELECT COUNT(*) as total FROM flyers");
$stats['total_flyers'] = $stmt->fetch()['total'];

// Imagens na galeria
$stmt = $conn->query("SELECT COUNT(*) as total FROM gallery_images");
$stats['total_images'] = $stmt->fetch()['total'];

// Assinaturas ativas
$stmt = $conn->query("SELECT COUNT(*) as total FROM user_subscriptions WHERE status = 'active' AND end_date > NOW()");
$stats['active_subscriptions'] = $stmt->fetch()['total'];

// Receita mensal estimada
$stmt = $conn->query("
    SELECT SUM(p.price) as revenue 
    FROM user_subscriptions us
    INNER JOIN plans p ON us.plan_id = p.id
    WHERE us.status = 'active' AND us.end_date > NOW()
");
$stats['revenue'] = $stmt->fetch()['revenue'] ?? 0;

$pageTitle = 'Dashboard';
include __DIR__ . '/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">📊 Dashboard Administrativo</h2>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Usuários</h5>
                <h2 class="display-4"><?= $stats['total_users'] ?></h2>
                <p class="card-text"><?= $stats['active_users'] ?> ativos</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Assinaturas Ativas</h5>
                <h2 class="display-4"><?= $stats['active_subscriptions'] ?></h2>
                <p class="card-text"><?= formatMoney($stats['revenue']) ?>/mês</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Encartes Criados</h5>
                <h2 class="display-4"><?= $stats['total_flyers'] ?></h2>
                <p class="card-text">Total geral</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <h5 class="card-title">Imagens na Galeria</h5>
                <h2 class="display-4"><?= $stats['total_images'] ?></h2>
                <p class="card-text">Compartilhadas</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">⚡ Acesso Rápido</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="users.php" class="btn btn-outline-primary">👥 Gerenciar Usuários</a>
                    <a href="plans.php" class="btn btn-outline-primary">📦 Gerenciar Planos</a>
                    <a href="db-update.php" class="btn btn-outline-primary">🔄 Atualizar Banco de Dados</a>
                    <a href="config.php" class="btn btn-outline-primary">⚙️ Configurações do Sistema</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">📈 Últimas Atividades</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $conn->query("
                    SELECT u.name, u.email, 'Novo usuário' as action, u.created_at as date
                    FROM users u
                    WHERE u.is_admin = 0
                    UNION ALL
                    SELECT u.name, u.email, CONCAT('Criou encarte: ', f.title) as action, f.created_at as date
                    FROM flyers f
                    INNER JOIN users u ON f.user_id = u.id
                    ORDER BY date DESC
                    LIMIT 5
                ");
                $activities = $stmt->fetchAll();
                
                if (count($activities) > 0):
                    foreach ($activities as $activity):
                ?>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <div>
                            <strong><?= sanitize($activity['name']) ?></strong><br>
                            <small class="text-muted"><?= sanitize($activity['action']) ?></small>
                        </div>
                        <small class="text-muted"><?= formatDate($activity['date']) ?></small>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <p class="text-muted text-center">Nenhuma atividade recente.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
