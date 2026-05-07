<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) redirect(BASE_URL . '/login.php');
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
$auth = new Auth();
$user = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = $auth->updateProfile($user['id'], $_POST);
        if ($result['success']) { $success = 'Perfil atualizado!'; $user = $auth->getCurrentUser(); }
        else $error = $result['error'];
    } elseif (isset($_POST['change_password'])) {
        $result = $auth->changePassword($user['id'], $_POST['current_password'], $_POST['new_password']);
        if ($result['success']) $success = 'Senha alterada!'; else $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Perfil - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.sidebar{min-height:100vh;background:#2c3e50}.sidebar a{color:#ecf0f1;text-decoration:none;padding:12px 20px;display:block}.sidebar a:hover,.sidebar a.active{background:#34495e}.sidebar a i{width:25px}</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<div class="col-md-2 sidebar p-0">
    <div class="p-3 bg-dark text-white"><h5 class="mb-0">🛒 FlyerSaaS</h5></div>
    <nav class="mt-3">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="flyers.php"><i class="fas fa-newspaper"></i> Encartes</a>
        <a href="gallery.php"><i class="fas fa-images"></i> Galeria</a>
        <a href="upload-image.php"><i class="fas fa-upload"></i> Upload</a>
        <a href="profile.php" class="active"><i class="fas fa-user"></i> Perfil</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
    </nav>
</div>
<div class="col-md-10 p-4">
    <h2 class="mb-4"><i class="fas fa-user-cog"></i> Meu Perfil</h2>
    <?php if($error):?><div class="alert alert-danger"><?php echo htmlspecialchars($error);?></div><?php endif;?>
    <?php if($success):?><div class="alert alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4"><div class="card-header">Dados Pessoais</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']);?>" required></div>
                        <div class="mb-3"><label class="form-label">E-mail</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']);?>" disabled></div>
                        <div class="mb-3"><label class="form-label">Nome da Loja</label><input type="text" name="store_name" class="form-control" value="<?php echo htmlspecialchars($user['store_name']??'');?>"></div>
                        <div class="mb-3"><label class="form-label">Telefone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']??'');?>"></div>
                        <div class="mb-3"><label class="form-label">CNPJ/CPF</label><input type="text" name="cnpj" class="form-control" value="<?php echo htmlspecialchars($user['cnpj']??'');?>"></div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4"><div class="card-header">Alterar Senha</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3"><label class="form-label">Senha Atual</label><input type="password" name="current_password" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label">Nova Senha</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
                        <button type="submit" name="change_password" class="btn btn-warning">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
