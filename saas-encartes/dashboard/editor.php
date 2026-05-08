<?php
/**
 * EncartePro - Editor de Encartes
 */

require_once __DIR__ . '/../includes/config.php';
$requireLogin = true;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'];
$canCreate = canCreateEncarte($userId);
$encarteId = $_GET['id'] ?? null;
$encarteData = null;

// Carrega encarte existente se estiver editando
if ($encarteId) {
    $stmt = $db->prepare("SELECT * FROM encartes WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $encarteId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $encarteData = $stmt->fetch();
    
    if (!$encarteData) {
        setFlashMessage('error', 'Encarte não encontrado.');
        redirect(SITE_URL . '/dashboard/');
    }
}

// Processa salvamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save') {
        $title = trim($_POST['title'] ?? 'Sem título');
        $templateId = $_POST['template_id'] ?? 'mercado_semanal';
        $data = json_encode($_POST['encarte_data'] ?? []);
        $status = $_POST['status'] ?? 'draft';
        
        if ($encarteId) {
            // Atualiza encarte existente
            $stmt = $db->prepare("UPDATE encartes SET title = :title, template_id = :template_id, data = :data, status = :status, updated_at = NOW() WHERE id = :id AND user_id = :user_id");
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':template_id', $templateId, PDO::PARAM_STR);
            $stmt->bindValue(':data', $data, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->bindValue(':id', $encarteId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            setFlashMessage('success', 'Encarte atualizado com sucesso!');
        } else {
            // Cria novo encarte
            if (!$canCreate) {
                setFlashMessage('error', 'Você atingiu o limite de encartes do seu plano.');
            } else {
                $stmt = $db->prepare("INSERT INTO encartes (user_id, title, template_id, data, status, created_at, updated_at) VALUES (:user_id, :title, :template_id, :data, :status, NOW(), NOW())");
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':title', $title, PDO::PARAM_STR);
                $stmt->bindValue(':template_id', $templateId, PDO::PARAM_STR);
                $stmt->bindValue(':data', $data, PDO::PARAM_STR);
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
                $stmt->execute();
                
                $encarteId = $db->lastInsertId();
                setFlashMessage('success', 'Encarte criado com sucesso!');
            }
        }
        
        if ($encarteId) {
            redirect(SITE_URL . '/dashboard/editor.php?id=' . $encarteId);
        }
    }
}
?>

<style>
    .editor-container {
        display: grid;
        grid-template-columns: 250px 1fr 300px;
        gap: 1rem;
        height: calc(100vh - 200px);
        min-height: 600px;
    }
    
    .sidebar {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow-y: auto;
        padding: 1rem;
    }
    
    .preview-area {
        background: #f0f0f0;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        overflow: auto;
    }
    
    .preview-frame {
        background: white;
        width: 100%;
        max-width: 500px;
        aspect-ratio: 1/1.414;
        box-shadow: 0 5px 30px rgba(0,0,0,0.2);
    }
    
    .template-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
    }
    
    .template-item {
        border: 2px solid #eee;
        border-radius: 8px;
        padding: 0.75rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .template-item:hover,
    .template-item.active {
        border-color: var(--primary-color);
        background: #fff5f2;
    }
    
    .template-item .icon {
        font-size: 2rem;
        margin-bottom: 0.25rem;
    }
    
    .template-item .name {
        font-size: 0.75rem;
        color: var(--dark-color);
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
    }
    
    .products-list {
        margin-top: 1rem;
    }
    
    .product-item {
        background: #f8f9fa;
        padding: 0.75rem;
        border-radius: 5px;
        margin-bottom: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.75rem;
        font-size: 0.85rem;
    }
    
    .toolbar {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
</style>

<div class="toolbar">
    <a href="my-encartes.php" class="btn btn-outline">← Voltar</a>
    <form method="POST" style="display: inline;">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="title" id="form-title" value="<?php echo htmlspecialchars($encarteData['title'] ?? 'Novo Encarte'); ?>">
        <input type="hidden" name="template_id" id="form-template" value="<?php echo htmlspecialchars($encarteData['template_id'] ?? 'mercado_semanal'); ?>">
        <input type="hidden" name="encarte_data" id="form-data" value="">
        <input type="hidden" name="status" value="draft">
        <button type="submit" class="btn btn-primary">💾 Salvar Rascunho</button>
    </form>
    <?php if ($encarteId): ?>
        <a href="download.php?id=<?php echo $encarteId; ?>" class="btn btn-success">📄 Baixar PDF</a>
    <?php endif; ?>
</div>

<div class="editor-container">
    <!-- Sidebar Esquerda: Templates -->
    <div class="sidebar">
        <h3 style="margin-bottom: 1rem;">Templates</h3>
        <div class="template-grid">
            <div class="template-item active" data-template="mercado_semanal">
                <div class="icon">🛒</div>
                <div class="name">Mercado Semanal</div>
            </div>
            <div class="template-item" data-template="promocao_relampago">
                <div class="icon">⚡</div>
                <div class="name">Promoção Relâmpago</div>
            </div>
            <div class="template-item" data-template="cardapio_simples">
                <div class="icon">🍽️</div>
                <div class="name">Cardápio Simples</div>
            </div>
            <div class="template-item" data-template="oferta_supermercado">
                <div class="icon">🏪</div>
                <div class="name">Oferta Supermercado</div>
            </div>
            <div class="template-item" data-template="aniversario_loja">
                <div class="icon">🎉</div>
                <div class="name">Aniversário</div>
            </div>
            <div class="template-item" data-template="black_friday">
                <div class="icon">🖤</div>
                <div class="name">Black Friday</div>
            </div>
        </div>
    </div>
    
    <!-- Área Central: Preview -->
    <div class="preview-area">
        <div class="preview-frame" id="preview">
            <!-- Preview será renderizado aqui via JS -->
        </div>
    </div>
    
    <!-- Sidebar Direita: Edição -->
    <div class="sidebar">
        <h3 style="margin-bottom: 1rem;">Editar</h3>
        
        <div class="form-group">
            <label>Título</label>
            <input type="text" id="edit-title" value="<?php echo htmlspecialchars($encarteData['title'] ?? ''); ?>" placeholder="Nome do encarte">
        </div>
        
        <div class="form-group">
            <label>Nome da Loja</label>
            <input type="text" id="edit-store" value="" placeholder="Sua Loja">
        </div>
        
        <div class="form-group">
            <label>Título Principal</label>
            <input type="text" id="edit-main-title" value="" placeholder="Ofertas da Semana">
        </div>
        
        <div class="form-group">
            <label>Cor Primária</label>
            <input type="color" id="edit-primary-color" value="#e8401c" style="height: 40px;">
        </div>
        
        <div class="form-group">
            <label>Cor Secundária</label>
            <input type="color" id="edit-secondary-color" value="#f5a623" style="height: 40px;">
        </div>
        
        <div class="form-group">
            <label>Rodapé / Validade</label>
            <input type="text" id="edit-footer" value="Válido até XXXX/XX/XX">
        </div>
        
        <hr style="margin: 1.5rem 0;">
        
        <h4>Produtos</h4>
        <div class="products-list" id="products-list">
            <!-- Produtos serão adicionados aqui -->
        </div>
        
        <button class="btn btn-outline btn-sm" onclick="addProduct()" style="margin-top: 0.5rem; width: 100%;">+ Adicionar Produto</button>
    </div>
</div>

<script>
let currentTemplate = '<?php echo $encarteData['template_id'] ?? 'mercado_semanal'; ?>';
let products = [];

// Inicializa dados se estiver editando
<?php if ($encarteData && $encarteData['data']): ?>
    const savedData = <?php echo $encarteData['data']; ?>;
    products = savedData.products || [];
    document.getElementById('edit-store').value = savedData.store_name || '';
    document.getElementById('edit-main-title').value = savedData.main_title || '';
    document.getElementById('edit-primary-color').value = savedData.primary_color || '#e8401c';
    document.getElementById('edit-secondary-color').value = savedData.secondary_color || '#f5a623';
    document.getElementById('edit-footer').value = savedData.footer_text || '';
<?php endif; ?>

// Seleção de templates
document.querySelectorAll('.template-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.template-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        currentTemplate = this.dataset.template;
        document.getElementById('form-template').value = currentTemplate;
        renderPreview();
    });
});

// Adicionar produto
function addProduct() {
    products.push({ name: '', price: '0,00', image: '' });
    renderProducts();
}

// Remover produto
function removeProduct(index) {
    products.splice(index, 1);
    renderProducts();
    renderPreview();
}

// Renderizar lista de produtos
function renderProducts() {
    const container = document.getElementById('products-list');
    container.innerHTML = products.map((p, i) => `
        <div class="product-item">
            <input type="text" placeholder="Nome do produto" value="${p.name}" 
                   onchange="updateProduct(${i}, 'name', this.value)" 
                   style="width: 100%; margin-bottom: 0.5rem; padding: 0.25rem; border: 1px solid #ddd; border-radius: 3px;">
            <input type="text" placeholder="Preço" value="${p.price}" 
                   onchange="updateProduct(${i}, 'price', this.value)"
                   style="width: 100%; margin-bottom: 0.5rem; padding: 0.25rem; border: 1px solid #ddd; border-radius: 3px;">
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-danger btn-sm" onclick="removeProduct(${i})">Remover</button>
            </div>
        </div>
    `).join('');
}

// Atualizar produto
function updateProduct(index, field, value) {
    products[index][field] = value;
    renderPreview();
}

// Renderizar preview
function renderPreview() {
    const data = {
        store_name: document.getElementById('edit-store').value,
        main_title: document.getElementById('edit-main-title').value,
        primary_color: document.getElementById('edit-primary-color').value,
        secondary_color: document.getElementById('edit-secondary-color').value,
        footer_text: document.getElementById('edit-footer').value,
        products: products
    };
    
    document.getElementById('form-data').value = JSON.stringify(data);
    document.getElementById('form-title').value = document.getElementById('edit-title').value;
    
    // Preview simplificado
    let productsHTML = products.slice(0, 6).map(p => `
        <div style="border: 1px solid #eee; padding: 10px; margin: 5px; text-align: center;">
            <div style="height: 60px; background: #f0f0f0; margin-bottom: 5px;"></div>
            <div style="font-size: 12px;">${p.name || 'Produto'}</div>
            <div style="color: ${data.primary_color}; font-weight: bold;">R$ ${p.price || '0,00'}</div>
        </div>
    `).join('');
    
    document.getElementById('preview').innerHTML = `
        <div style="padding: 20px; height: 100%; box-sizing: border-box;">
            <div style="background: ${data.primary_color}; color: white; padding: 20px; text-align: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">${data.store_name || 'Sua Loja'}</h2>
                <p style="margin: 5px 0 0 0;">${data.main_title || 'Ofertas'}</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                ${productsHTML}
            </div>
            <div style="background: ${data.secondary_color}; color: white; padding: 10px; text-align: center; margin-top: 20px;">
                ${data.footer_text || 'Validade'}
            </div>
        </div>
    `;
}

// Event listeners para atualização em tempo real
['edit-store', 'edit-main-title', 'edit-primary-color', 'edit-secondary-color', 'edit-footer', 'edit-title'].forEach(id => {
    document.getElementById(id).addEventListener('input', renderPreview);
});

// Inicializa
renderProducts();
renderPreview();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
