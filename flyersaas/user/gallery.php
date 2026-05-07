<?php
/**
 * FlyerSaaS - Galeria Geral de Imagens
 */
require_once '../config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    redirect(BASE_URL . '/login.php');
}

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();

// Busca e paginação
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search) {
    $where[] = "(original_name LIKE ? OR category LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}

$whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Total
$stmt = $db->prepare("SELECT COUNT(*) as total FROM gallery_images {$whereClause}");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

// Imagens
$stmt = $db->prepare("
    SELECT gi.*, u.store_name, u.name as uploader_name
    FROM gallery_images gi
    JOIN users u ON gi.uploaded_by = u.id
    {$whereClause}
    ORDER BY gi.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$images = $stmt->fetchAll();

// Categorias
$stmt = $db->query("SELECT DISTINCT category FROM gallery_images WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; }
        .sidebar a i { width: 25px; }
        .gallery-img { height: 200px; object-fit: cover; cursor: pointer; transition: transform 0.2s; }
        .gallery-img:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 bg-dark text-white"><h5 class="mb-0">🛒 FlyerSaaS</h5></div>
                <nav class="mt-3">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="flyers.php"><i class="fas fa-newspaper"></i> Encartes</a>
                    <a href="gallery.php" class="active"><i class="fas fa-images"></i> Galeria de Imagens</a>
                    <a href="upload-image.php"><i class="fas fa-upload"></i> Upload de Imagem</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Meu Perfil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="fas fa-images"></i> Galeria Geral de Imagens</h2>
                
                <!-- Filtros -->
                <form method="GET" class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" placeholder="Buscar por nome..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="category" class="form-select">
                                    <option value="">Todas categorias</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Galeria -->
                <div class="row g-3">
                    <?php foreach ($images as $img): ?>
                    <div class="col-md-3 col-6">
                        <div class="card h-100">
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($img['file_path']); ?>" 
                                 class="card-img-top gallery-img" alt="<?php echo htmlspecialchars($img['original_name']); ?>">
                            <div class="card-body p-2">
                                <small class="text-muted d-block text-truncate"><?php echo htmlspecialchars($img['original_name']); ?></small>
                                <small class="text-muted">Por: <?php echo htmlspecialchars($img['store_name'] ?? $img['uploader_name']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
