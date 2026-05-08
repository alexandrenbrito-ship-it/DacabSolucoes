<?php
/**
 * EncartePro - Dashboard Principal do Usuário
 */

require_once __DIR__ . '/../includes/config.php';
$requireLogin = true;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Obtém estatísticas do usuário
$encartesCount = getEncarteCount($userId);
$userPlanData = getUserPlan($userId);
$canCreate = canCreateEncarte($userId);

// Verifica status do pagamento
$paymentStatus = $_GET['payment'] ?? '';
?>

<style>
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
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
    }
    
    .stat-card .label {
        color: var(--gray-color);
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }
    
    .stat-card .value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
    }
    
    .plan-card {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
    }
    
    .plan-card h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .action-btn {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
        display: block;
        color: var(--dark-color);
    }
    
    .action-btn:hover {
        transform: translateY(-5px);
        color: var(--primary-color);
    }
    
    .action-btn .icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    
    .encartes-list {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .encarte-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #eee;
    }
    
    .encarte-item:last-child {
        border-bottom: none;
    }
    
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
</style>

<div class="dashboard-header">
    <h1 style="font-family: var(--font-heading);">Dashboard</h1>
    <?php if ($canCreate): ?>
        <a href="editor.php" class="btn btn-primary">+ Criar Novo Encarte</a>
    <?php else: ?>
        <span class="btn btn-warning" style="cursor: not-allowed;">Limite Atingido</span>
    <?php endif; ?>
</div>

<?php if ($paymentStatus === 'success'): ?>
    <div class="alert alert-success">
        ✅ Pagamento confirmado! Sua assinatura está ativa.
    </div>
<?php elseif ($paymentStatus === 'failure'): ?>
    <div class="alert alert-error">
        ❌ Ocorreu um erro no pagamento. Tente novamente.
    </div>
<?php elseif ($paymentStatus === 'pending'): ?>
    <div class="alert alert-warning">
        ⏳ Pagamento pendente. Aguarde a confirmação.
    </div>
<?php endif; ?>

<!-- Card do Plano -->
<div class="plan-card">
    <h3><?php echo $userPlanData ? htmlspecialchars($userPlanData['plan_name']) : 'Nenhum plano ativo'; ?></h3>
    <p>Status: <?php echo $userPlanData ? ucfirst($userPlanData['status']) : 'Inativo'; ?></p>
    <?php if ($userPlanData && $userPlanData['expires_at']): ?>
        <p>Vencimento: <?php echo formatDate($userPlanData['expires_at']); ?></p>
    <?php endif; ?>
    <div style="margin-top: 1rem;">
        <strong>Encartes: <?php echo $encartesCount; ?> / <?php echo $userPlanData && $userPlanData['max_encartes'] > 0 ? $userPlanData['max_encartes'] : '∞'; ?></strong>
    </div>
    <?php if ($userPlanData && $userPlanData['status'] !== 'active'): ?>
        <a href="#" class="btn btn-white" style="margin-top: 1rem; display: inline-block; color: var(--primary-color);">Fazer Upgrade</a>
    <?php endif; ?>
</div>

<!-- Estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="label">Total de Encartes</div>
        <div class="value"><?php echo $encartesCount; ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Rascunhos</div>
        <div class="value">
            <?php
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM encartes WHERE user_id = :user_id AND status = 'draft'");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            echo $stmt->fetch()['count'];
            ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="label">Publicados</div>
        <div class="value">
            <?php
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM encartes WHERE user_id = :user_id AND status = 'published'");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            echo $stmt->fetch()['count'];
            ?>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="quick-actions">
    <a href="editor.php" class="action-btn">
        <div class="icon">🎨</div>
        <strong>Criar Encarte</strong>
    </a>
    <a href="my-encartes.php" class="action-btn">
        <div class="icon">📁</div>
        <strong>Meus Encartes</strong>
    </a>
    <a href="#" class="action-btn">
        <div class="icon">⚙️</div>
        <strong>Configurações</strong>
    </a>
</div>

<!-- Últimos Encartes -->
<div class="encartes-list">
    <div style="padding: 1.5rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0;">Últimos Encartes</h3>
        <a href="my-encartes.php" style="color: var(--primary-color);">Ver todos →</a>
    </div>
    
    <?php
    $stmt = $db->prepare("SELECT * FROM encartes WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $encartes = $stmt->fetchAll();
    
    if (empty($encartes)):
    ?>
        <div style="padding: 3rem; text-align: center; color: var(--gray-color);">
            <p>Nenhum encarte criado ainda.</p>
            <a href="editor.php" class="btn btn-primary" style="margin-top: 1rem;">Criar Primeiro Encarte</a>
        </div>
    <?php else: ?>
        <?php foreach ($encartes as $encarte): ?>
            <div class="encarte-item">
                <div>
                    <strong><?php echo htmlspecialchars($encarte['title']); ?></strong>
                    <br>
                    <small style="color: var(--gray-color);">
                        <?php echo formatDate($encarte['created_at']); ?> • 
                        <span style="color: <?php echo $encarte['status'] === 'published' ? 'var(--success-color)' : 'var(--warning-color)'; ?>">
                            <?php echo $encarte['status'] === 'published' ? 'Publicado' : 'Rascunho'; ?>
                        </span>
                    </small>
                </div>
                <div>
                    <a href="editor.php?id=<?php echo $encarte['id']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Editar</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
