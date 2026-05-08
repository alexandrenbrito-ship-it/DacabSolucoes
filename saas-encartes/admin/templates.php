<?php
/**
 * EncartePro - Gerenciador de Templates (Admin)
 * CRUD completo de templates com preview ao vivo
 */

require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('/auth/login.php');
}

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$templateId = $_GET['id'] ?? null;

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    $postAction = $_POST['action'] ?? '';
    
    try {
        if ($postAction === 'create' || $postAction === 'update') {
            $data = [
                'name' => sanitize($_POST['name']),
                'description' => sanitize($_POST['description']),
                'header_bg_color' => sanitize($_POST['header_bg_color']),
                'header_text_color' => sanitize($_POST['header_text_color']),
                'body_bg_color' => sanitize($_POST['body_bg_color']),
                'footer_bg_color' => sanitize($_POST['footer_bg_color']),
                'footer_text_color' => sanitize($_POST['footer_text_color']),
                'primary_font' => sanitize($_POST['primary_font']),
                'product_cols' => (int) $_POST['product_cols'],
                'show_old_price' => isset($_POST['show_old_price']) ? 1 : 0,
                'show_product_image' => isset($_POST['show_product_image']) ? 1 : 0,
                'badge_style' => sanitize($_POST['badge_style']),
                'layout_style' => sanitize($_POST['layout_style']),
                'custom_css' => $_POST['custom_css'],
                'status' => sanitize($_POST['status']),
            ];
            
            // Upload de thumbnail
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['thumbnail'], ROOT_PATH . '/uploads/templates', ['image/jpeg', 'image/png', 'image/gif'], 2097152);
                if ($uploadResult['success']) {
                    $data['thumbnail'] = $uploadResult['filename'];
                }
            }
            
            if ($postAction === 'create') {
                $sql = "INSERT INTO templates (name, description, thumbnail, header_bg_color, header_text_color, 
                        body_bg_color, footer_bg_color, footer_text_color, primary_font, product_cols, 
                        show_old_price, show_product_image, badge_style, layout_style, custom_css, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($data));
                $templateId = $pdo->lastInsertId();
                echo json_encode(['success' => true, 'message' => 'Template criado!', 'id' => $templateId]);
            } else {
                $sql = "UPDATE templates SET name=?, description=?, header_bg_color=?, header_text_color=?, 
                        body_bg_color=?, footer_bg_color=?, footer_text_color=?, primary_font=?, product_cols=?, 
                        show_old_price=?, show_product_image=?, badge_style=?, layout_style=?, custom_css=?, status=?";
                
                $params = array_values($data);
                
                if (!empty($_FILES['thumbnail']['name'])) {
                    $sql .= ", thumbnail=?";
                    $params[] = $data['thumbnail'];
                }
                
                $sql .= " WHERE id=?";
                $params[] = $_POST['id'];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                echo json_encode(['success' => true, 'message' => 'Template atualizado!']);
            }
        } elseif ($postAction === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['success' => true, 'message' => 'Template excluído!']);
        } elseif ($postAction === 'duplicate') {
            $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $template = $stmt->fetch();
            
            if ($template) {
                $sql = "INSERT INTO templates (name, description, thumbnail, header_bg_color, header_text_color, 
                        body_bg_color, footer_bg_color, footer_text_color, primary_font, product_cols, 
                        show_old_price, show_product_image, badge_style, layout_style, custom_css, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $template['name'] . ' (Cópia)',
                    $template['description'],
                    $template['thumbnail'],
                    $template['header_bg_color'],
                    $template['header_text_color'],
                    $template['body_bg_color'],
                    $template['footer_bg_color'],
                    $template['footer_text_color'],
                    $template['primary_font'],
                    $template['product_cols'],
                    $template['show_old_price'],
                    $template['show_product_image'],
                    $template['badge_style'],
                    $template['layout_style'],
                    $template['custom_css'],
                    $template['status']
                ]);
                echo json_encode(['success' => true, 'message' => 'Template duplicado!']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Carrega template para edição
$template = null;
if ($action === 'edit' && $templateId) {
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
}

// Lista todos os templates
$templates = [];
if ($action === 'list') {
    $stmt = $pdo->query("SELECT t.*, 
                        (SELECT COUNT(*) FROM encartes WHERE template_id = t.id) as encartes_count 
                        FROM templates t ORDER BY t.created_at DESC");
    $templates = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<div class="admin-container">
    <?php if ($action === 'list'): ?>
        <div class="admin-header">
            <h1>📐 Gerenciar Templates</h1>
            <p>Crie e edite os templates disponíveis para os usuários</p>
            <a href="?action=new" class="btn btn-primary">➕ Novo Template</a>
        </div>
        
        <div class="templates-grid">
            <?php foreach ($templates as $t): ?>
                <div class="template-card" data-id="<?= $t['id'] ?>">
                    <div class="template-preview" style="
                        background: <?= htmlspecialchars($t['body_bg_color']) ?>;
                        border-top: 20px solid <?= htmlspecialchars($t['header_bg_color']) ?>;
                        border-bottom: 15px solid <?= htmlspecialchars($t['footer_bg_color']) ?>;
                    ">
                        <div style="padding: 20px; text-align: center; color: <?= htmlspecialchars($t['header_text_color']) ?>;">
                            <strong><?= htmlspecialchars($t['name']) ?></strong>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3><?= htmlspecialchars($t['name']) ?></h3>
                        <p><?= htmlspecialchars(substr($t['description'], 0, 80)) ?>...</p>
                        <div class="template-meta">
                            <span class="badge badge-<?= $t['status'] ?>"><?= $t['status'] ?></span>
                            <span class="count"><?= $t['encartes_count'] ?> encartes</span>
                        </div>
                        <div class="template-actions">
                            <a href="?action=edit&id=<?= $t['id'] ?>" class="btn btn-sm">✏️ Editar</a>
                            <button onclick="duplicateTemplate(<?= $t['id'] ?>)" class="btn btn-sm btn-secondary">📋 Duplicar</button>
                            <button onclick="deleteTemplate(<?= $t['id'] ?>)" class="btn btn-sm btn-danger">🗑️ Excluir</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <div class="admin-header">
            <h1><?= $action === 'new' ? '➕ Novo' : '✏️ Editar' ?> Template</h1>
            <a href="templates.php" class="btn btn-secondary">← Voltar</a>
        </div>
        
        <div class="editor-layout">
            <div class="editor-form">
                <form id="templateForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="<?= $action === 'new' ? 'create' : 'update' ?>">
                    <?php if ($template): ?>
                        <input type="hidden" name="id" value="<?= $template['id'] ?>">
                    <?php endif; ?>
                    
                    <!-- Seção 1: Informações Básicas -->
                    <div class="form-section collapsible" data-collapsed="false">
                        <div class="section-header" onclick="toggleSection(this)">
                            <h3>📝 Informações Básicas</h3>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div class="section-content">
                            <div class="form-group">
                                <label>Nome do Template *</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($template['name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Descrição</label>
                                <textarea name="description" rows="3"><?= htmlspecialchars($template['description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="active" <?= ($template['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inactive" <?= ($template['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Thumbnail</label>
                                <input type="file" name="thumbnail" accept="image/*">
                                <?php if (!empty($template['thumbnail'])): ?>
                                    <img src="/uploads/templates/<?= htmlspecialchars($template['thumbnail']) ?>" alt="Thumbnail" style="max-width: 200px; margin-top: 10px;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção 2: Estrutura Visual -->
                    <div class="form-section collapsible" data-collapsed="false">
                        <div class="section-header" onclick="toggleSection(this)">
                            <h3>🎨 Estrutura Visual</h3>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div class="section-content">
                            <h4>Cabeçalho</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Cor de Fundo</label>
                                    <div class="color-picker-group">
                                        <input type="color" name="header_bg_color" value="<?= htmlspecialchars($template['header_bg_color'] ?? '#e8401c') ?>" oninput="updatePreview()">
                                        <input type="text" class="hex-input" value="<?= htmlspecialchars($template['header_bg_color'] ?? '#e8401c') ?>" oninput="updatePreview()">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Cor do Texto</label>
                                    <div class="color-picker-group">
                                        <input type="color" name="header_text_color" value="<?= htmlspecialchars($template['header_text_color'] ?? '#ffffff') ?>" oninput="updatePreview()">
                                        <input type="text" class="hex-input" value="<?= htmlspecialchars($template['header_text_color'] ?? '#ffffff') ?>" oninput="updatePreview()">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Fonte Principal</label>
                                <select name="primary_font" onchange="updatePreview()">
                                    <option value="Syne" <?= ($template['primary_font'] ?? 'Syne') === 'Syne' ? 'selected' : '' ?>>Syne</option>
                                    <option value="Oswald" <?= ($template['primary_font'] ?? 'Syne') === 'Oswald' ? 'selected' : '' ?>>Oswald</option>
                                    <option value="Bebas Neue" <?= ($template['primary_font'] ?? 'Syne') === 'Bebas Neue' ? 'selected' : '' ?>>Bebas Neue</option>
                                    <option value="Anton" <?= ($template['primary_font'] ?? 'Syne') === 'Anton' ? 'selected' : '' ?>>Anton</option>
                                    <option value="Montserrat" <?= ($template['primary_font'] ?? 'Syne') === 'Montserrat' ? 'selected' : '' ?>>Montserrat</option>
                                    <option value="Playfair Display" <?= ($template['primary_font'] ?? 'Syne') === 'Playfair Display' ? 'selected' : '' ?>>Playfair Display</option>
                                </select>
                            </div>
                            
                            <h4>Área de Produtos</h4>
                            <div class="form-group">
                                <label>Cor de Fundo</label>
                                <div class="color-picker-group">
                                    <input type="color" name="body_bg_color" value="<?= htmlspecialchars($template['body_bg_color'] ?? '#ffffff') ?>" oninput="updatePreview()">
                                    <input type="text" class="hex-input" value="<?= htmlspecialchars($template['body_bg_color'] ?? '#ffffff') ?>" oninput="updatePreview()">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Colunas de Produtos</label>
                                    <select name="product_cols" onchange="updatePreview()">
                                        <option value="2" <?= ($template['product_cols'] ?? 3) == 2 ? 'selected' : '' ?>>2 colunas</option>
                                        <option value="3" <?= ($template['product_cols'] ?? 3) == 3 ? 'selected' : '' ?>>3 colunas</option>
                                        <option value="4" <?= ($template['product_cols'] ?? 3) == 4 ? 'selected' : '' ?>>4 colunas</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Estilo do Badge</label>
                                    <select name="badge_style" onchange="updatePreview()">
                                        <option value="circle" <?= ($template['badge_style'] ?? 'circle') === 'circle' ? 'selected' : '' ?>>Círculo</option>
                                        <option value="square" <?= ($template['badge_style'] ?? 'circle') === 'square' ? 'selected' : '' ?>>Quadrado</option>
                                        <option value="ribbon" <?= ($template['badge_style'] ?? 'circle') === 'ribbon' ? 'selected' : '' ?>>Faixa</option>
                                        <option value="none" <?= ($template['badge_style'] ?? 'circle') === 'none' ? 'selected' : '' ?>>Nenhum</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Layout</label>
                                    <select name="layout_style" onchange="updatePreview()">
                                        <option value="grid" <?= ($template['layout_style'] ?? 'grid') === 'grid' ? 'selected' : '' ?>>Grade (Grid)</option>
                                        <option value="list" <?= ($template['layout_style'] ?? 'grid') === 'list' ? 'selected' : '' ?>>Lista</option>
                                        <option value="magazine" <?= ($template['layout_style'] ?? 'grid') === 'magazine' ? 'selected' : '' ?>>Revista</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="show_product_image" <?= ($template['show_product_image'] ?? 1) ? 'checked' : '' ?> onchange="updatePreview()">
                                    <span>Mostrar imagem do produto</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="show_old_price" <?= ($template['show_old_price'] ?? 1) ? 'checked' : '' ?> onchange="updatePreview()">
                                    <span>Mostrar preço antigo (de)</span>
                                </label>
                            </div>
                            
                            <h4>Rodapé</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Cor de Fundo</label>
                                    <div class="color-picker-group">
                                        <input type="color" name="footer_bg_color" value="<?= htmlspecialchars($template['footer_bg_color'] ?? '#f5a623') ?>" oninput="updatePreview()">
                                        <input type="text" class="hex-input" value="<?= htmlspecialchars($template['footer_bg_color'] ?? '#f5a623') ?>" oninput="updatePreview()">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Cor do Texto</label>
                                    <div class="color-picker-group">
                                        <input type="color" name="footer_text_color" value="<?= htmlspecialchars($template['footer_text_color'] ?? '#ffffff') ?>" oninput="updatePreview()">
                                        <input type="text" class="hex-input" value="<?= htmlspecialchars($template['footer_text_color'] ?? '#ffffff') ?>" oninput="updatePreview()">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção 3: CSS Personalizado -->
                    <div class="form-section collapsible" data-collapsed="true">
                        <div class="section-header" onclick="toggleSection(this)">
                            <h3>💻 CSS Personalizado</h3>
                            <span class="toggle-icon">▼</span>
                        </div>
                        <div class="section-content">
                            <div class="form-group">
                                <label>CSS Extra (avançado)</label>
                                <textarea name="custom_css" rows="8" placeholder="/* CSS personalizado aqui */"><?= htmlspecialchars($template['custom_css'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-large">💾 Salvar Template</button>
                </form>
            </div>
            
            <!-- Preview ao Vivo -->
            <div class="preview-panel">
                <h3>👁️ Preview ao Vivo</h3>
                <div id="livePreview" class="preview-frame">
                    <!-- Renderizado via JS -->
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.template-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.template-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow);
}

.template-preview {
    height: 180px;
    background: #f5f5f5;
}

.template-info {
    padding: 20px;
}

.template-info h3 {
    margin: 0 0 8px 0;
    color: var(--text);
}

.template-info p {
    color: var(--text-muted);
    font-size: 14px;
    margin: 0 0 12px 0;
}

.template-meta {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 16px;
}

.count {
    font-size: 13px;
    color: var(--text-muted);
}

.template-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.editor-layout {
    display: grid;
    grid-template-columns: 1fr 500px;
    gap: 24px;
}

.editor-form {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
}

.preview-panel {
    position: sticky;
    top: 20px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    height: fit-content;
}

.preview-frame {
    background: white;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px;
    min-height: 500px;
    transform-origin: top center;
}

.form-section {
    border: 1px solid var(--border);
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: var(--bg);
    cursor: pointer;
    user-select: none;
}

.section-header h3 {
    margin: 0;
    font-size: 16px;
}

.toggle-icon {
    transition: transform 0.3s;
}

.collapsible[data-collapsed="true"] .section-content {
    display: none;
}

.collapsible[data-collapsed="true"] .toggle-icon {
    transform: rotate(-90deg);
}

.section-content {
    padding: 20px;
}

.badge-active {
    background: #f0fdf4;
    color: #2d6a4f;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-inactive {
    background: #fef2f2;
    color: #dc2626;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.btn-large {
    padding: 16px 32px;
    font-size: 16px;
    width: 100%;
    margin-top: 20px;
}
</style>

<script>
// Toggle sections
function toggleSection(header) {
    const section = header.parentElement;
    const collapsed = section.dataset.collapsed === 'true';
    section.dataset.collapsed = !collapsed;
}

// Update live preview
function updatePreview() {
    const headerBg = document.querySelector('input[name="header_bg_color"]').value;
    const headerText = document.querySelector('input[name="header_text_color"]').value;
    const bodyBg = document.querySelector('input[name="body_bg_color"]').value;
    const footerBg = document.querySelector('input[name="footer_bg_color"]').value;
    const footerText = document.querySelector('input[name="footer_text_color"]').value;
    const font = document.querySelector('select[name="primary_font"]').value;
    const cols = document.querySelector('select[name="product_cols"]').value;
    const badgeStyle = document.querySelector('select[name="badge_style"]').value;
    const showImage = document.querySelector('input[name="show_product_image"]').checked;
    const showOldPrice = document.querySelector('input[name="show_old_price"]').checked;
    
    const preview = document.getElementById('livePreview');
    preview.innerHTML = `
        <div style="font-family: '${font}', sans-serif; background: ${bodyBg};">
            <!-- Header -->
            <div style="background: ${headerBg}; color: ${headerText}; padding: 20px; text-align: center;">
                <h2 style="margin: 0; font-size: 24px;">LOJA EXEMPLO</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Ofertas Especiais</p>
            </div>
            
            <!-- Products -->
            <div style="padding: 20px; display: grid; grid-template-columns: repeat(${cols}, 1fr); gap: 15px;">
                ${[1, 2, 3].map(i => `
                    <div style="border: 1px solid #ddd; padding: 15px; text-align: center;">
                        ${showImage ? '<div style="width: 100%; height: 80px; background: #f0f0f0; margin-bottom: 10px;"></div>' : ''}
                        <p style="margin: 0 0 10px 0; font-weight: 600;">Produto ${i}</p>
                        ${showOldPrice ? '<p style="margin: 0; text-decoration: line-through; color: #999; font-size: 12px;">R$ 99,90</p>' : ''}
                        <p style="margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: ${headerBg};">
                            ${badgeStyle === 'circle' ? '<span style="background: ' + headerBg + '; color: white; padding: 5px 15px; border-radius: 20px;">R$ 49,90</span>' : 'R$ 49,90'}
                        </p>
                    </div>
                `).join('')}
            </div>
            
            <!-- Footer -->
            <div style="background: ${footerBg}; color: ${footerText}; padding: 15px; text-align: center;">
                <p style="margin: 0;">Válido até 31/12/2025</p>
            </div>
        </div>
    `;
}

// Form submission
document.getElementById('templateForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('success', data.message);
            if (data.id) {
                setTimeout(() => window.location.href = 'templates.php?action=edit&id=' + data.id, 1000);
            }
        } else {
            showToast('error', data.message);
        }
    } catch (error) {
        showToast('error', 'Erro: ' + error.message);
    }
});

// Duplicate template
async function duplicateTemplate(id) {
    if (!confirm('Deseja duplicar este template?')) return;
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');
    formData.append('action', 'duplicate');
    formData.append('id', id);
    
    try {
        const response = await fetch('templates.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        showToast(data.success ? 'success' : 'error', data.message);
        if (data.success) location.reload();
    } catch (error) {
        showToast('error', 'Erro: ' + error.message);
    }
}

// Delete template
async function deleteTemplate(id) {
    if (!confirm('Tem certeza? Esta ação não pode ser desfeita.')) return;
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');
    formData.append('action', 'delete');
    formData.append('id', id);
    
    try {
        const response = await fetch('templates.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        showToast(data.success ? 'success' : 'error', data.message);
        if (data.success) location.reload();
    } catch (error) {
        showToast('error', 'Erro: ' + error.message);
    }
}

// Toast
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 16px 24px;
        border-radius: 8px; background: ${type === 'success' ? '#f0fdf4' : '#fef2f2'};
        color: ${type === 'success' ? '#2d6a4f' : '#dc2626'};
        border: 1px solid ${type === 'success' ? '#40916c' : '#ef4444'};
        box-shadow: 0 4px 16px rgba(0,0,0,0.1); z-index: 10000;
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Init
updatePreview();
</script>

<?php include '../includes/footer.php'; ?>
