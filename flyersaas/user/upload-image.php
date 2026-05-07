<?php
/**
 * FlyerSaaS - Upload de Imagens
 */
require_once '../config.php';

// Verificar autenticação
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/login.php');
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SubscriptionManager.php';

$auth = new Auth();
$subManager = new SubscriptionManager();
$user = $auth->getCurrentUser();
$usage = $subManager->getUsageSummary($user['id']);

$error = '';
$success = '';

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    // Verificar limite
    $canUpload = $subManager->canUploadImage($user['id']);
    if (!$canUpload['allowed']) {
        $error = $canUpload['reason'];
    } else {
        $file = $_FILES['image'];
        $category = sanitize($_POST['category'] ?? '');
        
        // Validações
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Arquivo muito grande. Máximo 5MB.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Erro no upload. Tente novamente.';
        } else {
            // Gerar hash para verificar duplicatas
            $hash = md5_file($file['tmp_name']);
            
            $db = Database::getInstance()->getConnection();
            
            // Verificar se já existe imagem com mesmo hash
            $stmt = $db->prepare("SELECT id FROM gallery_images WHERE hash = ?");
            $stmt->execute([$hash]);
            
            if ($stmt->fetch()) {
                $error = 'Esta imagem já existe na galeria (duplicata detectada).';
            } else {
                // Gerar nome único
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $storedName = uniqid() . '_' . time() . '.' . $extension;
                $uploadPath = __DIR__ . '/../assets/uploads/images/' . $storedName;
                
                // Mover arquivo
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Salvar no banco
                    $stmt = $db->prepare("
                        INSERT INTO gallery_images 
                        (original_name, stored_name, file_path, mime_type, size, hash, category, uploaded_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        sanitize($file['name']),
                        $storedName,
                        'assets/uploads/images/' . $storedName,
                        $file['type'],
                        $file['size'],
                        $hash,
                        $category,
                        $user['id']
                    ]);
                    
                    // Incrementar contador
                    $subManager->incrementImagesUsed($user['id']);
                    
                    $success = 'Imagem enviada com sucesso!';
                    
                    // Atualizar uso
                    $usage = $subManager->getUsageSummary($user['id']);
                } else {
                    $error = 'Erro ao salvar arquivo. Verifique as permissões.';
                }
            }
        }
    }
}

// Carregar imagens do usuário
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM gallery_images WHERE uploaded_by = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$myImages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de Imagem - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; }
        .sidebar a i { width: 25px; }
        .image-preview { max-width: 200px; max-height: 200px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 bg-dark text-white">
                    <h5 class="mb-0">🛒 FlyerSaaS</h5>
                </div>
                <nav class="mt-3">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="flyers.php"><i class="fas fa-newspaper"></i> Encartes</a>
                    <a href="gallery.php"><i class="fas fa-images"></i> Galeria de Imagens</a>
                    <a href="upload-image.php" class="active"><i class="fas fa-upload"></i> Upload de Imagem</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Meu Perfil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="fas fa-upload"></i> Upload de Imagem</h2>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Status do Limite -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Limite de Imagens:</strong> 
                                <?php echo $usage['images_used']; ?> de <?php echo $usage['images_limit']; ?> usadas
                            </div>
                            <div>
                                <span class="badge bg-<?php echo $usage['images_used'] >= $usage['images_limit'] ? 'danger' : 'success'; ?>">
                                    <?php echo $usage['images_limit'] - $usage['images_used']; ?> restantes
                                </span>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 10px;">
                            <div class="progress-bar" style="width: <?php echo ($usage['images_used'] / $usage['images_limit']) * 100; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Upload -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-cloud-upload-alt"></i> Nova Imagem
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="image" class="form-label">Selecione a Imagem *</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <small class="text-muted">Formatos: JPG, PNG, GIF, WebP. Máx: 5MB</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Categoria (opcional)</label>
                                    <input type="text" class="form-control" id="category" name="category" placeholder="Ex: Bebidas, Laticínios...">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" <?php echo $usage['images_used'] >= $usage['images_limit'] ? 'disabled' : ''; ?>>
                                <i class="fas fa-upload"></i> Enviar Imagem
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Minhas Imagens -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-images"></i> Minhas Imagens
                    </div>
                    <div class="card-body">
                        <?php if (empty($myImages)): ?>
                        <p class="text-muted text-center">Nenhuma imagem enviada ainda.</p>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($myImages as $img): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card">
                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($img['file_path']); ?>" 
                                         class="card-img-top image-preview object-fit-cover" alt="<?php echo htmlspecialchars($img['original_name']); ?>">
                                    <div class="card-body p-2">
                                        <small class="text-muted d-block text-truncate">
                                            <?php echo htmlspecialchars($img['original_name']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($img['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
