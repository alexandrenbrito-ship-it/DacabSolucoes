<?php
/**
 * EncartePro - Admin Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
$requireLogin = true;
$requireAdmin = true;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Estatísticas gerais
$stats = [];

// Total de usuários
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetch()['total'];

// Assinaturas ativas
$stmt = $db->query("SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'");
$stats['active_subscriptions'] = $stmt->fetch()['total'];

// Receita mensal estimada
$stmt = $db->query("
    SELECT SUM(p.price) as revenue 
    FROM subscriptions s 
    JOIN plans p ON s.plan_id = p.id 
    WHERE s.status = 'active' AND p.billing_cycle = 'monthly'
");
$stats['monthly_revenue'] = $stmt->fetch()['revenue'] ?? 0;

// Novos cadastros hoje
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
$stats['new_today'] = $stmt->fetch()['total'];
?>

<style>
    .admin-header {
        margin-bottom: 2rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid var(--primary-color);
    }
    
    .stat-card .label {
        color: var(--gray-color);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-card .value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-color);
    }
    
    .admin-nav {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .admin-nav-item {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        display: block;
        color: var(--dark-color);
    }
    
    .admin-nav-item:hover {
        transform: translateY(-5px);
        color: var(--primary-color);
    }
    
    .admin-nav-item .icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
</style>

<div class="admin-header">
    <h1 style="font-family: var(--font-heading);">🔧 Painel Administrativo</h1>
    <p style="color: var(--gray-color);">Gerencie usuários, planos e assinaturas</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total de Usuários</div>
        <div class="value"><?php echo $stats['total_users']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="label">Assinaturas Ativas</div>
        <div class="value"><?php echo $stats['active_subscriptions']; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="label">Receita Mensal (Est.)</div>
        <div class="value"><?php echo formatCurrency($stats['monthly_revenue']); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="label">Novos Hoje</div>
        <div class="value"><?php echo $stats['new_today']; ?></div>
    </div>
</div>

<h2 style="margin: 2rem 0 1rem 0;">Gerenciar</h2>

<div class="admin-nav">
    <a href="users.php" class="admin-nav-item">
        <div class="icon">👥</div>
        <strong>Usuários</strong>
        <p style="color: var(--gray-color); font-size: 0.9rem; margin-top: 0.5rem;">Gerenciar usuários e permissões</p>
    </a>
    
    <a href="plans.php" class="admin-nav-item">
        <div class="icon">📦</div>
        <strong>Planos</strong>
        <p style="color: var(--gray-color); font-size: 0.9rem; margin-top: 0.5rem;">Configurar planos e preços</p>
    </a>
    
    <a href="subscriptions.php" class="admin-nav-item">
        <div class="icon">💳</div>
        <strong>Assinaturas</strong>
        <p style="color: var(--gray-color); font-size: 0.9rem; margin-top: 0.5rem;">Gerenciar assinaturas</p>
    </a>
    
    <a href="<?php echo SITE_URL; ?>/dashboard/" class="admin-nav-item">
        <div class="icon">🏠</div>
        <strong>Ver Site</strong>
        <p style="color: var(--gray-color); font-size: 0.9rem; margin-top: 0.5rem;">Ir para o dashboard</p>
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
