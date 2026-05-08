<?php
require_once __DIR__ . '/../config.php';
Auth::requireLogin();
$user = Auth::user();
$pageTitle = 'Meu Perfil';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🛒 FlyerSaaS</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="gallery.php">Galeria</a></li>
                    <li class="nav-item"><a class="nav-link" href="flyers.php">Encartes</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile.php">Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php displayFlashMessage(); ?>
        
        <h2 class="mb-4">👤 Meu Perfil</h2>
        
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <tr><td><strong>Nome:</strong></td><td><?= sanitize($user['name']) ?></td></tr>
                    <tr><td><strong>E-mail:</strong></td><td><?= sanitize($user['email']) ?></td></tr>
                </table>
            </div>
        </div>
        
        <p class="text-muted mt-4">Em desenvolvimento...</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
