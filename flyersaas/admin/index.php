<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) redirect(BASE_URL . '/login.php');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
$auth = new Auth();
$db = Database::getInstance()->getConnection();

// Estatísticas
$stats = [];
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['users'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM flyers");
$stats['flyers'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM gallery_images");
$stats['images'] = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as total FROM user_subscriptions WHERE status = 'active'");
$stats['active_subs'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.sidebar{min-height:100vh;background:#1a252f}.sidebar a{color:#ecf0f1;text-decoration:none;padding:12px 20px;display:block}.sidebar a:hover,.sidebar a.active{background:#34495e}.sidebar a i{width:25px}</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 sidebar p-0">
    <div class="p-3 bg-dark text-white"><h5 class="mb-0">⚙️ Admin Panel</h5></div>
    <nav class="mt-3">
        <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="plans.php"><i class="fas fa-tags"></i> Planos</a>
        <a href="users.php"><i class="fas fa-users"></i> Usuários</a>
        <a href="config.php"><i class="fas fa-cog"></i> Configurações</a>
        <a href="db-update.php"><i class="fas fa-database"></i> Atualizar Banco</a>
        <a href="../user/dashboard.php"><i class="fas fa-arrow-left"></i> Voltar ao Site</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</div>
<div class="col-md-10 p-4">
    <h2 class="mb-4">Dashboard Administrativo</h2>
    <div class="row mb-4">
        <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h5>Usuários</h5><h3><?php echo $stats['users'];?></h3></div></div></div>
        <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h5>Encartes</h5><h3><?php echo $stats['flyers'];?></h3></div></div></div>
        <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h5>Imagens</h5><h3><?php echo $stats['images'];?></h3></div></div></div>
        <div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body"><h5>Assinaturas Ativas</h5><h3><?php echo $stats['active_subs'];?></h3></div></div></div>
    </div>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Bem-vindo ao painel administrativo. Use o menu lateral para gerenciar o sistema.</div>
</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
