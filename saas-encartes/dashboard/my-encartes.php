<?php
/**
 * EncartePro - Meus Encartes
 */

require_once __DIR__ . '/../includes/config.php';
$requireLogin = true;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];

// Processa exclusão
if (isset($_GET['delete'])) {
    $encarteId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM encartes WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $encarteId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    setFlashMessage('success', 'Encarte excluído com sucesso!');
    redirect(SITE_URL . '/dashboard/my-encartes.php');
}

// Filtros
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Query base
$sql = "SELECT * FROM encartes WHERE user_id = :user_id";
$params = [':user_id' => $userId];

if ($statusFilter !== 'all') {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

// Conta total
$countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
$countSql = preg_replace('/LIMIT.*$/', '', $countSql);

$stmt = $db->prepare($countSql);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
}
$stmt->execute();
$total = $stmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

// Busca encartes
$stmt = $db->prepare(str_replace(":limit OFFSET :offset", "LIMIT $perPage OFFSET $offset", $sql));
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
if ($statusFilter !== 'all') {
    $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
}
$stmt->execute();
$encartes = $stmt->fetchAll();
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .filters {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .filter-btn {
        padding: 0.5rem 1rem;
        border: 2px solid #eee;
        background: white;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .filter-btn:hover,
    .filter-btn.active {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .encartes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    .encarte-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .encarte-card:hover {
        transform: translateY(-5px);
    }
    
    .encarte-preview {
        height: 200px;
        background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
    }
    
    .encarte-info {
        padding: 1rem;
    }
    
    .encarte-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    
    .pagination a,
    .pagination span {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        text-decoration: none;
        color: var(--dark-color);
    }
    
    .pagination a:hover {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .pagination .current {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
</style>

<div class="page-header">
    <h1 style="font-family: var(--font-heading);">Meus Encartes</h1>
    <a href="editor.php" class="btn btn-primary">+ Criar Novo</a>
</div>

<div class="filters">
    <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">Todos</a>
    <a href="?status=draft" class="filter-btn <?php echo $statusFilter === 'draft' ? 'active' : ''; ?>">Rascunhos</a>
    <a href="?status=published" class="filter-btn <?php echo $statusFilter === 'published' ? 'active' : ''; ?>">Publicados</a>
</div>

<?php if (empty($encartes)): ?>
    <div style="text-align: center; padding: 4rem; background: white; border-radius: 10px;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📄</div>
        <h3>Nenhum encarte encontrado</h3>
        <p style="color: var(--gray-color); margin-bottom: 1.5rem;">Crie seu primeiro encarte agora mesmo!</p>
        <a href="editor.php" class="btn btn-primary">Criar Encarte</a>
    </div>
<?php else: ?>
    <div class="encartes-grid">
        <?php foreach ($encartes as $encarte): ?>
            <div class="encarte-card">
                <div class="encarte-preview">
                    <?php
                    $icons = ['mercado_semanal' => '🛒', 'promocao_relampago' => '⚡', 'cardapio_simples' => '🍽️', 
                              'oferta_supermercado' => '🏪', 'aniversario_loja' => '🎉', 'black_friday' => '🖤'];
                    echo $icons[$encarte['template_id']] ?? '📄';
                    ?>
                </div>
                <div class="encarte-info">
                    <strong><?php echo htmlspecialchars($encarte['title']); ?></strong>
                    <br>
                    <small style="color: var(--gray-color);">
                        <?php echo formatDate($encarte['created_at']); ?> • 
                        <span style="color: <?php echo $encarte['status'] === 'published' ? 'var(--success-color)' : 'var(--warning-color)'; ?>">
                            <?php echo ucfirst($encarte['status']); ?>
                        </span>
                    </small>
                    <div class="encarte-actions">
                        <a href="editor.php?id=<?php echo $encarte['id']; ?>" class="btn btn-outline btn-sm" style="flex: 1;">Editar</a>
                        <a href="download.php?id=<?php echo $encarte['id']; ?>" class="btn btn-success btn-sm">PDF</a>
                        <a href="?delete=<?php echo $encarte['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza?')">🗑️</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>">← Anterior</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>">Próxima →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
