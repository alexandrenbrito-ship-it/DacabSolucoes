<?php
/**
 * EncartePro - Admin: Gerenciar Assinaturas
 */

require_once __DIR__ . '/../includes/config.php';
$requireLogin = true;
$requireAdmin = true;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $stmt = $db->prepare("UPDATE subscriptions SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $_POST['status'], ':id' => $_POST['sub_id']]);
                setFlashMessage('success', 'Status atualizado!');
                break;
            case 'assign_plan':
                $userId = (int)$_POST['user_id'];
                $planId = (int)$_POST['plan_id'];
                $expiresAt = $_POST['expires_at'] ?? date('Y-m-d', strtotime('+1 month'));
                
                // Cancela assinatura anterior
                $stmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status IN ('active', 'trial')");
                $stmt->execute([$userId]);
                
                // Cria nova assinatura
                $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, expires_at) VALUES (?, ?, 'active', CURDATE(), ?)");
                $stmt->execute([$userId, $planId, $expiresAt]);
                setFlashMessage('success', 'Plano atribuído!');
                break;
        }
    }
}

// Filtros
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT s.*, u.name as user_name, u.email as user_email, p.name as plan_name, p.price 
        FROM subscriptions s 
        JOIN users u ON s.user_id = u.id 
        JOIN plans p ON s.plan_id = p.id";

$countSql = str_replace("SELECT s.*", "SELECT COUNT(*) as total", $sql);

if ($statusFilter) {
    $sql .= " WHERE s.status = :status";
    $countSql .= " WHERE s.status = :status";
}

$sql .= " ORDER BY s.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($countSql);
if ($statusFilter) $stmt->bindValue(':status', $statusFilter);
$stmt->execute();
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare($sql);
if ($statusFilter) $stmt->bindValue(':status', $statusFilter);
$stmt->execute();
$subscriptions = $stmt->fetchAll();

// Para o formulário de atribuir plano
$stmt = $db->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY name");
$users = $stmt->fetchAll();
$stmt = $db->query("SELECT id, name, price FROM plans WHERE status = 'active' ORDER BY price");
$plans = $stmt->fetchAll();
?>

<style>
.table-container { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; margin-bottom: 2rem; }
th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
th { background: var(--light-color); font-weight: 600; }
.badge { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.85rem; }
.badge-active { background: #d4edda; color: #155724; }
.badge-expired { background: #f8d7da; color: #721c24; }
.badge-cancelled { background: #e2e3e5; color: #383d41; }
.badge-trial { background: #fff3cd; color: #856404; }
.filters { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.filter-btn { padding: 0.5rem 1rem; border: 2px solid #eee; background: white; border-radius: 5px; cursor: pointer; }
.filter-btn.active { border-color: var(--primary-color); color: var(--primary-color); }
.pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
.pagination a, .pagination span { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; }
.pagination .current { background: var(--primary-color); color: white; }
.card { background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
.form-group input, .form-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; }
</style>

<h1 style="font-family: var(--font-heading); margin-bottom: 1.5rem;">💳 Gerenciar Assinaturas</h1>

<div class="card">
    <h3>Atribuir Plano a Usuário</h3>
    <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
        <input type="hidden" name="action" value="assign_plan">
        <div class="form-group">
            <label>Usuário</label>
            <select name="user_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo $u['email']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Plano</label>
            <select name="plan_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($plans as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> - <?php echo formatCurrency($p['price']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Vencimento</label>
            <input type="date" name="expires_at" value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Atribuir Plano</button>
    </form>
</div>

<div class="filters">
    <a href="?status=" class="filter-btn <?php echo !$statusFilter ? 'active' : ''; ?>">Todos</a>
    <a href="?status=active" class="filter-btn <?php echo $statusFilter==='active'?'active':''; ?>">Ativos</a>
    <a href="?status=trial" class="filter-btn <?php echo $statusFilter==='trial'?'active':''; ?>">Trial</a>
    <a href="?status=expired" class="filter-btn <?php echo $statusFilter==='expired'?'active':''; ?>">Expirados</a>
    <a href="?status=cancelled" class="filter-btn <?php echo $statusFilter==='cancelled'?'active':''; ?>">Cancelados</a>
</div>

<div class="table-container">
    <table>
        <thead><tr><th>ID</th><th>Usuário</th><th>Plano</th><th>Status</th><th>Início</th><th>Vencimento</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($subscriptions as $sub): ?>
            <tr>
                <td><?php echo $sub['id']; ?></td>
                <td><?php echo htmlspecialchars($sub['user_name']); ?><br><small style="color:#999;"><?php echo htmlspecialchars($sub['user_email']); ?></small></td>
                <td><?php echo htmlspecialchars($sub['plan_name']); ?></td>
                <td><span class="badge badge-<?php echo $sub['status']; ?>"><?php echo ucfirst($sub['status']); ?></span></td>
                <td><?php echo formatDate($sub['starts_at']); ?></td>
                <td><?php echo $sub['expires_at'] ? formatDate($sub['expires_at']) : '-'; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding:0.25rem;">
                            <option value="active" <?php echo $sub['status']==='active'?'selected':''; ?>>Ativo</option>
                            <option value="trial" <?php echo $sub['status']==='trial'?'selected':''; ?>>Trial</option>
                            <option value="expired" <?php echo $sub['status']==='expired'?'selected':''; ?>>Expirado</option>
                            <option value="cancelled" <?php echo $sub['status']==='cancelled'?'selected':''; ?>>Cancelado</option>
                        </select>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?><span class="current"><?php echo $i; ?></span>
        <?php else: ?><a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a><?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<p><a href="index.php" class="btn btn-outline">← Voltar ao Admin</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
