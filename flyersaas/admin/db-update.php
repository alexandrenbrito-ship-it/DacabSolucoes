<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) redirect(BASE_URL . '/login.php');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Migration.php';
$migration = new Migration();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $migration->runPendingMigrations();
    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = $result['message'] . ': ' . ($result['error'] ?? '');
        $messageType = 'danger';
    }
}

$executed = $migration->getExecutedMigrations();
$pending = $migration->getPendingMigrations();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Banco - FlyerSaaS</title>
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
        <a href="config.php"><i class="fas fa-cog"></i> Configurações</a>
        <a href="db-update.php" class="active"><i class="fas fa-database"></i> Atualizar Banco</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-database"></i> Atualização do Banco de Dados</h2>
    
    <?php if($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">Migrações Executadas (<?php echo count($executed); ?>)</div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach($executed as $m): ?>
                        <li class="list-group-item"><i class="fas fa-check text-success"></i> <?php echo htmlspecialchars($m); ?></li>
                        <?php endforeach; ?>
                        <?php if(empty($executed)): ?><li class="list-group-item text-muted">Nenhuma migração executada</li><?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning">Migrações Pendentes (<?php echo count($pending); ?>)</div>
                <div class="card-body">
                    <ul class="list-group mb-3">
                        <?php foreach($pending as $m): ?>
                        <li class="list-group-item"><i class="fas fa-clock text-warning"></i> <?php echo htmlspecialchars($m); ?></li>
                        <?php endforeach; ?>
                        <?php if(empty($pending)): ?><li class="list-group-item text-muted">Nenhuma migração pendente</li><?php endif; ?>
                    </ul>
                    
                    <?php if(!empty($pending)): ?>
                    <form method="POST" onsubmit="return confirm('Deseja executar todas as migrações pendentes?')">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-play"></i> Executar Atualizações</button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Banco de dados atualizado!</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
