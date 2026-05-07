<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) redirect(BASE_URL . '/login.php');

require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance()->getConnection();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], (int)$_POST['id']]);
        $success = 'Status atualizado!';
    } elseif (isset($_POST['reset_password'])) {
        $newPass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newPass, (int)$_POST['id']]);
        $success = 'Senha resetada!';
    }
}

$stmt = $db->query("SELECT u.*, p.name as plan_name, us.end_date FROM users u LEFT JOIN user_subscriptions us ON u.id = us.user_id AND us.status='active' LEFT JOIN plans p ON us.plan_id = p.id WHERE u.is_admin = 0 ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Usuários - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.sidebar{min-height:100vh;background:#1a252f}.sidebar a{color:#ecf0f1;text-decoration:none;padding:12px 20px;display:block}.sidebar a:hover,.sidebar a.active{background:#34495e}</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 sidebar p-0">
    <div class="p-3 bg-dark text-white"><h5 class="mb-0">⚙️ Admin Panel</h5></div>
    <nav class="mt-3">
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="plans.php"><i class="fas fa-tags"></i> Planos</a>
        <a href="users.php" class="active"><i class="fas fa-users"></i> Usuários</a>
        <a href="config.php"><i class="fas fa-cog"></i> Configurações</a>
        <a href="db-update.php"><i class="fas fa-database"></i> Atualizar Banco</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-users"></i> Gerenciar Usuários</h2>
    <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>
    
    <table class="table table-bordered">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Loja</th><th>Plano</th><th>Validade</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['name']);?></td>
            <td><?php echo htmlspecialchars($u['email']);?></td>
            <td><?php echo htmlspecialchars($u['store_name'] ?? '-');?></td>
            <td><?php echo htmlspecialchars($u['plan_name'] ?? 'Nenhum');?></td>
            <td><?php echo $u['end_date'] ? date('d/m/Y', strtotime($u['end_date'])) : '-';?></td>
            <td><span class="badge bg-<?php echo $u['status']==='active'?'success':'danger';?>"><?php echo ucfirst($u['status']);?></span></td>
            <td>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="id" value="<?php echo $u['id'];?>">
                    <input type="hidden" name="status" value="<?php echo $u['status']==='active'?'inactive':'active';?>">
                    <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-warning"><i class="fas fa-toggle-on"></i></button>
                </form>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#passModal" onclick="document.getElementById('reset_user_id').value=<?php echo $u['id'];?>"><i class="fas fa-key"></i></button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div></div>

<div class="modal fade" id="passModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Resetar Senha</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" id="reset_user_id">
                <label class="form-label">Nova Senha</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="modal-footer"><button type="submit" name="reset_password" class="btn btn-danger">Resetar Senha</button></div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
