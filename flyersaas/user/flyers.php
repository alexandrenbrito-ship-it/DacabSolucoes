<?php
require_once '../config.php';
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) redirect(BASE_URL . '/login.php');

require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/SubscriptionManager.php';

$auth = new Auth();
$subManager = new SubscriptionManager();
$user = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Criar encarte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $canCreate = $subManager->canCreateFlyer($user['id']);
    if (!$canCreate['allowed']) {
        $error = $canCreate['reason'];
    } else {
        try {
            $db->beginTransaction();
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description'] ?? '');
            $template = sanitize($_POST['template'] ?? 'default');
            
            $stmt = $db->prepare("INSERT INTO flyers (user_id, title, description, template, status, created_at) VALUES (?, ?, ?, ?, 'draft', NOW())");
            $stmt->execute([$user['id'], $title, $description, $template]);
            $flyerId = $db->lastInsertId();
            
            // Itens do encarte
            if (!empty($_POST['items'])) {
                $stmt = $db->prepare("INSERT INTO flyer_items (flyer_id, product_name, price, description, image_id, position_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($_POST['items'] as $i => $item) {
                    $stmt->execute([$flyerId, sanitize($item['name']), (float)$item['price'], sanitize($item['description'] ?? ''), (int)($item['image_id'] ?? null), $i]);
                }
            }
            
            $subManager->incrementFlyersUsed($user['id']);
            $db->commit();
            $success = 'Encarte criado com sucesso!';
            $action = 'list';
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Listar encartes
if ($action === 'list') {
    $stmt = $db->prepare("SELECT * FROM flyers WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $flyers = $stmt->fetchAll();
}

// Obter imagens para seleção
$stmt = $db->query("SELECT * FROM gallery_images ORDER BY created_at DESC LIMIT 100");
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encartes - FlyerSaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: 100vh; background: #2c3e50; }
        .sidebar a { color: #ecf0f1; text-decoration: none; padding: 12px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; }
        .sidebar a i { width: 25px; }
        .product-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 bg-dark text-white"><h5 class="mb-0">🛒 FlyerSaaS</h5></div>
                <nav class="mt-3">
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="flyers.php" class="active"><i class="fas fa-newspaper"></i> Encartes</a>
                    <a href="gallery.php"><i class="fas fa-images"></i> Galeria de Imagens</a>
                    <a href="upload-image.php"><i class="fas fa-upload"></i> Upload de Imagem</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Meu Perfil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </nav>
            </div>
            <div class="col-md-10 p-4">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

                <?php if ($action === 'create'): ?>
                <h2><i class="fas fa-plus"></i> Novo Encarte</h2>
                <form method="POST" id="flyerForm">
                    <input type="hidden" name="action" value="create">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Título *</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Template</label>
                                    <select name="template" class="form-select">
                                        <option value="default">Padrão</option>
                                        <option value="modern">Moderno</option>
                                        <option value="classic">Clássico</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <h5>Produtos</h5>
                    <div id="productsContainer"></div>
                    <button type="button" class="btn btn-outline-primary mb-3" onclick="addProduct()"><i class="fas fa-plus"></i> Adicionar Produto</button>
                    
                    <hr>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Encarte</button>
                    <a href="flyers.php" class="btn btn-secondary">Cancelar</a>
                </form>

                <script>
                const images = <?php echo json_encode($images); ?>;
                let productCount = 0;
                
                function addProduct() {
                    const id = productCount++;
                    let options = '<option value="">Selecione uma imagem</option>';
                    images.forEach(img => {
                        options += `<option value="${img.id}">${img.original_name}</option>`;
                    });
                    
                    const html = `
                    <div class="product-item row" id="product_${id}">
                        <div class="col-md-3">
                            <input type="text" name="items[${id}][name]" class="form-control" placeholder="Nome do Produto" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" step="0.01" name="items[${id}][price]" class="form-control" placeholder="Preço" required>
                        </div>
                        <div class="col-md-4">
                            <select name="items[${id}][image_id]" class="form-select">${options}</select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="items[${id}][description]" class="form-control" placeholder="Descrição (opcional)">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger" onclick="document.getElementById('product_${id}').remove()"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>`;
                    document.getElementById('productsContainer').insertAdjacentHTML('beforeend', html);
                }
                // Adicionar 3 produtos iniciais
                for(let i=0; i<3; i++) addProduct();
                </script>
                <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-newspaper"></i> Meus Encartes</h2>
                    <a href="?action=create" class="btn btn-primary"><i class="fas fa-plus"></i> Novo Encarte</a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($flyers)): ?>
                        <p class="text-muted text-center">Nenhum encarte criado.</p>
                        <?php else: ?>
                        <table class="table table-hover">
                            <thead><tr><th>Título</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead>
                            <tbody>
                            <?php foreach ($flyers as $f): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($f['title']); ?></td>
                                <td><span class="badge bg-<?php echo $f['status'] === 'published' ? 'success' : 'warning'; ?>"><?php echo ucfirst($f['status']); ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($f['created_at'])); ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-pdf"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
