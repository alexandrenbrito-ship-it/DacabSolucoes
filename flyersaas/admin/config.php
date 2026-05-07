<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) redirect(BASE_URL . '/login.php');

require_once __DIR__ . '/../includes/Database.php';
$db = Database::getInstance()->getConnection();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['site_name', 'site_email', 'maintenance_mode', 'max_upload_size'] as $key) {
        if (isset($_POST[$key])) {
            $stmt = $db->prepare("INSERT INTO system_config (config_key, config_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
            $stmt->execute([$key, sanitize($_POST[$key]), '']);
        }
    }
    $success = 'Configurações salvas!';
}

$stmt = $db->query("SELECT * FROM system_config");
$configs = [];
while ($row = $stmt->fetch()) { $configs[$row['config_key']] = $row['config_value']; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurações - FlyerSaaS</title>
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
        <a href="users.php"><i class="fas fa-users"></i> Usuários</a>
        <a href="config.php" class="active"><i class="fas fa-cog"></i> Configurações</a>
        <a href="db-update.php"><i class="fas fa-database"></i> Atualizar Banco</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-cog"></i> Configurações do Sistema</h2>
    <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>
    
    <form method="POST" class="card">
        <div class="card-body">
            <div class="mb-3"><label class="form-label">Nome do Site</label><input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($configs['site_name'] ?? 'FlyerSaaS');?>"></div>
            <div class="mb-3"><label class="form-label">E-mail de Contato</label><input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($configs['site_email'] ?? '');?>"></div>
            <div class="mb-3"><label class="form-label">Modo de Manutenção</label>
                <select name="maintenance_mode" class="form-select">
                    <option value="0" <?php echo ($configs['maintenance_mode'] ?? 0) == 0 ? 'selected' : ''; ?>>Desligado</option>
                    <option value="1" <?php echo ($configs['maintenance_mode'] ?? 0) == 1 ? 'selected' : ''; ?>>Ligado</option>
                </select>
            </div>
            <div class="mb-3"><label class="form-label">Tamanho Máximo de Upload (bytes)</label><input type="number" name="max_upload_size" class="form-control" value="<?php echo htmlspecialchars($configs['max_upload_size'] ?? 5242880);?>"></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Configurações</button>
        </div>
    </form>
</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
