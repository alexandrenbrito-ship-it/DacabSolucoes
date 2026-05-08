<?php
require_once __DIR__ . '/../config.php';
Auth::requireLogin();
$pageTitle = 'Galeria de Imagens';
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
        .gallery-item { position: relative; overflow: hidden; border-radius: 10px; }
        .gallery-item img { width: 100%; height: 200px; object-fit: cover; transition: transform 0.3s; }
        .gallery-item:hover img { transform: scale(1.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🛒 FlyerSaaS</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="gallery.php">Galeria</a></li>
                    <li class="nav-item"><a class="nav-link" href="flyers.php">Encartes</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <?php displayFlashMessage(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>🖼️ Galeria de Imagens</h2>
            <a href="upload-image.php" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</a>
        </div>
        
        <div class="alert alert-info">
            Todas as imagens enviadas por usuários estão disponíveis aqui. Use-as nos seus encartes!
        </div>
        
        <p class="text-muted text-center">Em desenvolvimento...</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
