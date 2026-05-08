<?php
/**
 * EncartePro - Admin: Gerenciar Usuários
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
                $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $_POST['status'], ':id' => $_POST['user_id']]);
                setFlashMessage('success', 'Status atualizado!');
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'user'");
                $stmt->execute([':id' => $_POST['user_id']]);
                setFlashMessage('success', 'Usuário excluído!');
                break;
        }
    }
}

// Lista usuários com paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("SELECT u.*, s.plan_id, p.name as plan_name FROM users u 
                      LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
                      LEFT JOIN plans p ON s.plan_id = p.id 
                      WHERE u.role = 'user' 
                      ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute();
$users = $stmt->fetchAll();

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);
?>

<style>
.table-container { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; }
th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
th { background: var(--light-color); font-weight: 600; }
.badge { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.85rem; font-weight: 600; }
.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #f8d7da; color: #721c24; }
.badge-suspended { background: #fff3cd; color: #856404; }
.actions { display: flex; gap: 0.5rem; }
.pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
.pagination a, .pagination span { padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 5px; text-decoration: none; }
.pagination .current { background: var(--primary-color); color: white; border-color: var(--primary-color); }
</style>

<h1 style="font-family: var(--font-heading); margin-bottom: 1.5rem;">👥 Gerenciar Usuários</h1>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nome</th><th>Email</th><th>Plano</th><th>Status</th><th>Criado</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['plan_name'] ?? '-'); ?></td>
                <td><span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                <td><?php echo formatDate($user['created_at']); ?></td>
                <td class="actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding:0.25rem;">
                            <option value="active" <?php echo $user['status']==='active'?'selected':''; ?>>Ativo</option>
                            <option value="inactive" <?php echo $user['status']==='inactive'?'selected':''; ?>>Inativo</option>
                            <option value="suspended" <?php echo $user['status']==='suspended'?'selected':''; ?>>Suspenso</option>
                        </select>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir usuário?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
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
        <?php else: ?><a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a><?php endif; ?>
    <?php endfor; ?>
</div>
<?php endif; ?>

<p style="margin-top:1rem;"><a href="index.php" class="btn btn-outline">← Voltar ao Admin</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
