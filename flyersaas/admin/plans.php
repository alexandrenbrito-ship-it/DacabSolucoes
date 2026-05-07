<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) redirect(BASE_URL . '/login.php');

require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance()->getConnection();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $db->prepare("INSERT INTO plans (name, description, price, duration_days, images_limit, flyers_limit, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([sanitize($_POST['name']), sanitize($_POST['description']), (float)$_POST['price'], (int)$_POST['duration_days'], (int)$_POST['images_limit'], (int)$_POST['flyers_limit']]);
        $success = 'Plano criado!';
    } elseif (isset($_POST['update'])) {
        $stmt = $db->prepare("UPDATE plans SET name=?, description=?, price=?, duration_days=?, images_limit=?, flyers_limit=? WHERE id=?");
        $stmt->execute([sanitize($_POST['name']), sanitize($_POST['description']), (float)$_POST['price'], (int)$_POST['duration_days'], (int)$_POST['images_limit'], (int)$_POST['flyers_limit'], (int)$_POST['id']]);
        $success = 'Plano atualizado!';
    } elseif (isset($_POST['delete'])) {
        $stmt = $db->prepare("DELETE FROM plans WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        $success = 'Plano excluído!';
    }
}

$stmt = $db->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Planos - FlyerSaaS</title>
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
        <a href="plans.php" class="active"><i class="fas fa-tags"></i> Planos</a>
        <a href="users.php"><i class="fas fa-users"></i> Usuários</a>
        <a href="config.php"><i class="fas fa-cog"></i> Configurações</a>
        <a href="db-update.php"><i class="fas fa-database"></i> Atualizar Banco</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-tags"></i> Gerenciar Planos</h2>
    <?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif;?>
    <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>
    
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#planModal" onclick="clearForm()"><i class="fas fa-plus"></i> Novo Plano</button>
    
    <table class="table table-bordered">
        <thead><tr><th>Nome</th><th>Preço</th><th>Duração</th><th>Imagens</th><th>Encartes</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach($plans as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['name']);?></td>
            <td>R$ <?php echo number_format($p['price'],2,',','.');?></td>
            <td><?php echo $p['duration_days'];?> dias</td>
            <td><?php echo $p['images_limit'];?></td>
            <td><?php echo $p['flyers_limit'];?></td>
            <td><span class="badge bg-<?php echo $p['status']==='active'?'success':'secondary';?>"><?php echo ucfirst($p['status']);?></span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick='editPlan(<?php echo json_encode($p);?>)'><i class="fas fa-edit"></i></button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Excluir?')"><input type="hidden" name="id" value="<?php echo $p['id'];?>"><button type="submit" name="delete" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div></div>

<!-- Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Plano</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="id" id="plan_id">
                <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="name" id="plan_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Descrição</label><textarea name="description" id="plan_description" class="form-control"></textarea></div>
                <div class="row"><div class="col-6 mb-3"><label class="form-label">Preço (R$)</label><input type="number" step="0.01" name="price" id="plan_price" class="form-control" required></div><div class="col-6 mb-3"><label class="form-label">Duração (dias)</label><input type="number" name="duration_days" id="plan_duration" class="form-control" value="30" required></div></div>
                <div class="row"><div class="col-6 mb-3"><label class="form-label">Limite Imagens</label><input type="number" name="images_limit" id="plan_images" class="form-control" required></div><div class="col-6 mb-3"><label class="form-label">Limite Encartes</label><input type="number" name="flyers_limit" id="plan_flyers" class="form-control" required></div></div>
            </div>
            <div class="modal-footer"><button type="submit" name="create" id="btnCreate" class="btn btn-primary">Salvar</button><button type="submit" name="update" id="btnUpdate" class="btn btn-warning d-none">Atualizar</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('planModal'));
function clearForm(){document.getElementById('plan_id').value='';document.getElementById('plan_name').value='';document.getElementById('plan_description').value='';document.getElementById('plan_price').value='';document.getElementById('plan_duration').value='30';document.getElementById('plan_images').value='';document.getElementById('plan_flyers').value='';document.getElementById('btnCreate').classList.remove('d-none');document.getElementById('btnUpdate').classList.add('d-none');}
function editPlan(p){document.getElementById('plan_id').value=p.id;document.getElementById('plan_name').value=p.name;document.getElementById('plan_description').value=p.description;document.getElementById('plan_price').value=p.price;document.getElementById('plan_duration').value=p.duration_days;document.getElementById('plan_images').value=p.images_limit;document.getElementById('plan_flyers').value=p.flyers_limit;document.getElementById('btnCreate').classList.add('d-none');document.getElementById('btnUpdate').classList.remove('d-none');modal.show();}
</script>
</body>
</html>
