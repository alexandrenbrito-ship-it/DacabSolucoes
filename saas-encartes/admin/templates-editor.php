<?php
/**
 * EncartePro - Editor de Templates (Admin)
 * Criar/Editar templates com preview ao vivo
 */

require_once '../includes/config.php';
require_once '../includes/render-template.php';

if (!isAdmin()) {
    redirect('/auth/login.php');
}

$pdo = getDB();
$templateId = $_GET['id'] ?? null;
$action = $templateId ? 'edit' : 'create';

// Carrega template se estiver editando
$template = null;
if ($templateId) {
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    
    if (!$template) {
        setFlashMessage('error', 'Template não encontrado.');
        redirect('templates.php');
    }
}

include '../includes/header.php';
?>

<style>
/* Layout do editor */
.editor-page {
    display: grid;
    grid-template-columns: 1fr 450px;
    gap: 20px;
    height: calc(100vh - 180px);
    overflow: hidden;
}

@media (max-width: 1024px) {
    .editor-page {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .editor-preview-panel {
        display: none !important;
    }
    
    .mobile-preview-btn {
        display: block !important;
    }
}

.mobile-preview-btn {
    display: none;
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    padding: 15px 25px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 50px;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

/* Painel de formulário */
.editor-form-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.editor-form-content {
    flex: 1;
    overflow-y: auto;
    padding: 0;
}

.editor-form-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
}

.editor-form-header h2 {
    margin: 0;
    font-size: 18px;
}

/* Seções colapsáveis */
.form-section {
    border-bottom: 1px solid #eee;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #fafafa;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
}

.section-header:hover {
    background: #f5f5f5;
}

.section-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
}

.toggle-icon {
    transition: transform 0.3s;
    font-size: 12px;
}

.form-section[data-collapsed="true"] .section-content {
    display: none;
}

.form-section[data-collapsed="true"] .toggle-icon {
    transform: rotate(-90deg);
}

.section-content {
    padding: 20px;
}

/* Campos do formulário */
.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    font-size: 13px;
    color: #555;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
    font-family: monospace;
    font-size: 12px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

/* Color picker */
.color-picker-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-picker-group input[type="color"] {
    width: 50px;
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    padding: 2px;
}

.color-picker-group .hex-input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: monospace;
    text-transform: uppercase;
}

/* Toggle switch */
.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: var(--primary-color);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

/* Radio group */
.radio-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.radio-option input[type="radio"] {
    cursor: pointer;
}

/* Range slider */
.range-slider {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 15px;
}

.range-slider input[type="range"] {
    flex: 1;
    height: 6px;
    border-radius: 3px;
    background: #e0e0e0;
    outline: none;
    -webkit-appearance: none;
}

.range-slider input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--primary-color);
    cursor: pointer;
}

.range-value {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    color: var(--primary-color);
}

/* Checkbox group */
.checkbox-group {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.checkbox-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

/* Preview panel */
.editor-preview-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 180px);
}

.preview-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
}

.preview-header h3 {
    margin: 0;
    font-size: 15px;
}

.preview-controls {
    display: flex;
    gap: 10px;
}

.preview-controls button {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.preview-controls button:hover {
    background: #f5f5f5;
}

.preview-controls button.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.preview-content {
    flex: 1;
    overflow: auto;
    padding: 20px;
    background: #f0f0f0;
    display: flex;
    justify-content: center;
}

.preview-frame {
    transform-origin: top center;
    transition: transform 0.3s;
}

.preview-frame.desktop {
    transform: scale(0.6);
}

.preview-frame.mobile {
    transform: scale(0.35);
}

/* Barra de ações */
.editor-actions {
    padding: 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    background: #fafafa;
}

.editor-actions .btn {
    flex: 1;
}

/* Modal de preview fullscreen */
.preview-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    z-index: 10000;
    overflow: auto;
}

.preview-modal.open {
    display: flex;
    flex-direction: column;
}

.preview-modal-header {
    padding: 15px 20px;
    background: #333;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-modal-body {
    flex: 1;
    padding: 40px;
    display: flex;
    justify-content: center;
}

.preview-modal-body .encarte {
    box-shadow: 0 0 40px rgba(0,0,0,0.5);
}
</style>

<div class="editor-page">
    <!-- Painel de Formulário -->
    <div class="editor-form-panel">
        <div class="editor-form-header">
            <h2><?= $action === 'create' ? '➕ Novo Template' : '✏️ Editar Template' ?></h2>
            <a href="templates.php" class="btn btn-outline btn-sm">← Voltar</a>
        </div>
        
        <div class="editor-form-content">
            <form id="templateForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="<?= $action === 'create' ? 'create' : 'update' ?>">
                <?php if ($template): ?>
                    <input type="hidden" name="id" value="<?= $template['id'] ?>">
                <?php endif; ?>
                
                <!-- Seção 1: Informações Gerais -->
                <div class="form-section" data-collapsed="false">
                    <div class="section-header" onclick="toggleSection(this)">
                        <h3>📝 Informações Gerais</h3>
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
                        <div class="form-row">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="active" <?= ($template['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inactive" <?= ($template['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Restrição de Plano</label>
                                <select name="plan_restriction">
                                    <option value="all" <?= ($template['plan_restriction'] ?? 'all') === 'all' ? 'selected' : '' ?>>Todos os Planos</option>
                                    <option value="pro" <?= ($template['plan_restriction'] ?? 'all') === 'pro' ? 'selected' : '' ?>>Pro+</option>
                                    <option value="enterprise" <?= ($template['plan_restriction'] ?? 'all') === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Destaque</label>
                                <label class="toggle-label">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="is_featured" <?= !empty($template['is_featured']) ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </div>
                                    <span>Aparecer em primeiro</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>Ordem de Exibição</label>
                                <input type="number" name="sort_order" value="<?= $template['sort_order'] ?? 0 ?>" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção 2: Cabeçalho -->
                <div class="form-section" data-collapsed="false">
                    <div class="section-header" onclick="toggleSection(this)">
                        <h3>🎨 Cabeçalho</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cor de Fundo</label>
                                <div class="color-picker-group">
                                    <input type="color" name="header_bg_color" value="<?= htmlspecialchars($template['header_bg_color'] ?? '#e8401c') ?>" oninput="syncColorInput(this, 'header_bg_color_text')">
                                    <input type="text" id="header_bg_color_text" class="hex-input" value="<?= htmlspecialchars($template['header_bg_color'] ?? '#e8401c') ?>" oninput="syncTextInput(this, 'header_bg_color')">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cor do Texto</label>
                                <div class="color-picker-group">
                                    <input type="color" name="header_text_color" value="<?= htmlspecialchars($template['header_text_color'] ?? '#ffffff') ?>" oninput="syncColorInput(this, 'header_text_color_text')">
                                    <input type="text" id="header_text_color_text" class="hex-input" value="<?= htmlspecialchars($template['header_text_color'] ?? '#ffffff') ?>" oninput="syncTextInput(this, 'header_text_color')">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Altura do Cabeçalho</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="header_height" value="small" <?= ($template['header_height'] ?? 'medium') === 'small' ? 'checked' : '' ?>>
                                    Pequeno
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="header_height" value="medium" <?= ($template['header_height'] ?? 'medium') === 'medium' ? 'checked' : '' ?>>
                                    Médio
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="header_height" value="large" <?= ($template['header_height'] ?? 'medium') === 'large' ? 'checked' : '' ?>>
                                    Grande
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Layout da Logo</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="header_layout" value="logo-left" <?= ($template['header_layout'] ?? 'logo-left') === 'logo-left' ? 'checked' : '' ?>>
                                    Esquerda
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="header_layout" value="logo-center" <?= ($template['header_layout'] ?? 'logo-left') === 'logo-center' ? 'checked' : '' ?>>
                                    Centro
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="header_layout" value="logo-right" <?= ($template['header_layout'] ?? 'logo-left') === 'logo-right' ? 'checked' : '' ?>>
                                    Direita
                                </label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="toggle-label">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="header_show_logo" <?= !empty($template['header_show_logo']) ? 'checked' : '' ?>>
                                    </div>
                                    <span>Mostrar Logo da Loja</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="toggle-label">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="header_show_phone" <?= !isset($template['header_show_phone']) || !empty($template['header_show_phone']) ? 'checked' : '' ?>>
                                    </div>
                                    <span>Mostrar Telefone</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção 3: Tipografia -->
                <div class="form-section" data-collapsed="true">
                    <div class="section-header" onclick="toggleSection(this)">
                        <h3>🔤 Tipografia</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Fonte dos Títulos</label>
                                <select name="primary_font">
                                    <?php 
                                    $fonts = ['Syne', 'Oswald', 'Bebas Neue', 'Anton', 'Montserrat', 'Playfair Display', 'Raleway', 'Poppins', 'Inter', 'Roboto'];
                                    foreach ($fonts as $font): 
                                    ?>
                                        <option value="<?= $font ?>" <?= ($template['primary_font'] ?? 'Syne') === $font ? 'selected' : '' ?>><?= $font ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Fonte do Corpo</label>
                                <select name="secondary_font">
                                    <?php 
                                    $fonts = ['DM Sans', 'Syne', 'Oswald', 'Bebas Neue', 'Anton', 'Montserrat', 'Playfair Display', 'Raleway', 'Poppins', 'Inter', 'Roboto'];
                                    foreach ($fonts as $font): 
                                    ?>
                                        <option value="<?= $font ?>" <?= ($template['secondary_font'] ?? 'DM Sans') === $font ? 'selected' : '' ?>><?= $font ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tamanho do Título</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="title_size" value="small" <?= ($template['title_size'] ?? 'large') === 'small' ? 'checked' : '' ?>>
                                    Pequeno
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="title_size" value="medium" <?= ($template['title_size'] ?? 'large') === 'medium' ? 'checked' : '' ?>>
                                    Médio
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="title_size" value="large" <?= ($template['title_size'] ?? 'large') === 'large' ? 'checked' : '' ?>>
                                    Grande
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="title_size" value="xlarge" <?= ($template['title_size'] ?? 'large') === 'xlarge' ? 'checked' : '' ?>>
                                    Extra Grande
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção 4: Produtos -->
                <div class="form-section" data-collapsed="true">
                    <div class="section-header" onclick="toggleSection(this)">
                        <h3>🛒 Área de Produtos</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cor de Fundo</label>
                                <div class="color-picker-group">
                                    <input type="color" name="body_bg_color" value="<?= htmlspecialchars($template['body_bg_color'] ?? '#ffffff') ?>" oninput="syncColorInput(this, 'body_bg_color_text')">
                                    <input type="text" id="body_bg_color_text" class="hex-input" value="<?= htmlspecialchars($template['body_bg_color'] ?? '#ffffff') ?>" oninput="syncTextInput(this, 'body_bg_color')">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cor do Texto</label>
                                <div class="color-picker-group">
                                    <input type="color" name="body_text_color" value="<?= htmlspecialchars($template['body_text_color'] ?? '#222222') ?>" oninput="syncColorInput(this, 'body_text_color_text')">
                                    <input type="text" id="body_text_color_text" class="hex-input" value="<?= htmlspecialchars($template['body_text_color'] ?? '#222222') ?>" oninput="syncTextInput(this, 'body_text_color')">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Layout dos Produtos</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="layout_style" value="grid" <?= ($template['layout_style'] ?? 'grid') === 'grid' ? 'checked' : '' ?>>
                                    Grid
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="layout_style" value="list" <?= ($template['layout_style'] ?? 'grid') === 'list' ? 'checked' : '' ?>>
                                    Lista
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="layout_style" value="magazine" <?= ($template['layout_style'] ?? 'grid') === 'magazine' ? 'checked' : '' ?>>
                                    Magazine
                                </label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Colunas Desktop</label>
                                <select name="product_cols_desktop">
                                    <option value="2" <?= ($template['product_cols_desktop'] ?? 3) == 2 ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= ($template['product_cols_desktop'] ?? 3) == 3 ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= ($template['product_cols_desktop'] ?? 3) == 4 ? 'selected' : '' ?>>4</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Colunas Mobile</label>
                                <select name="product_cols_mobile">
                                    <option value="1" <?= ($template['product_cols_mobile'] ?? 2) == 1 ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= ($template['product_cols_mobile'] ?? 2) == 2 ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                        </div>
                        
                        <h4 style="margin: 20px 0 15px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Card do Produto</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cor de Fundo</label>
                                <div class="color-picker-group">
                                    <input type="color" name="product_card_bg" value="<?= htmlspecialchars($template['product_card_bg'] ?? '#f9f9f9') ?>" oninput="syncColorInput(this, 'product_card_bg_text')">
                                    <input type="text" id="product_card_bg_text" class="hex-input" value="<?= htmlspecialchars($template['product_card_bg'] ?? '#f9f9f9') ?>" oninput="syncTextInput(this, 'product_card_bg')">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cor da Borda</label>
                                <div class="color-picker-group">
                                    <input type="color" name="product_card_border" value="<?= htmlspecialchars($template['product_card_border'] ?? '#eeeeee') ?>" oninput="syncColorInput(this, 'product_card_border_text')">
                                    <input type="text" id="product_card_border_text" class="hex-input" value="<?= htmlspecialchars($template['product_card_border'] ?? '#eeeeee') ?>" oninput="syncTextInput(this, 'product_card_border')">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Arredondamento da Borda</label>
                            <div class="range-slider">
                                <input type="range" name="product_card_radius" min="0" max="24" value="<?= $template['product_card_radius'] ?? 8 ?>" oninput="document.getElementById('radius_value').textContent = this.value + 'px'">
                                <span id="radius_value" class="range-value"><?= $template['product_card_radius'] ?? 8 ?>px</span>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cor do Nome</label>
                                <div class="color-picker-group">
                                    <input type="color" name="product_name_color" value="<?= htmlspecialchars($template['product_name_color'] ?? '#222222') ?>" oninput="syncColorInput(this, 'product_name_color_text')">
                                    <input type="text" id="product_name_color_text" class="hex-input" value="<?= htmlspecialchars($template['product_name_color'] ?? '#222222') ?>" oninput="syncTextInput(this, 'product_name_color')">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cor do Preço</label>
                                <div class="color-picker-group">
                                    <input type="color" name="product_price_color" value="<?= htmlspecialchars($template['product_price_color'] ?? '#e8401c') ?>" oninput="syncColorInput(this, 'product_price_color_text')">
                                    <input type="text" id="product_price_color_text" class="hex-input" value="<?= htmlspecialchars($template['product_price_color'] ?? '#e8401c') ?>" oninput="syncTextInput(this, 'product_price_color')">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Cor do Preço Antigo</label>
                            <div class="color-picker-group">
                                <input type="color" name="product_old_price_color" value="<?= htmlspecialchars($template['product_old_price_color'] ?? '#999999') ?>" oninput="syncColorInput(this, 'product_old_price_color_text')">
                                <input type="text" id="product_old_price_color_text" class="hex-input" value="<?= htmlspecialchars($template['product_old_price_color'] ?? '#999999') ?>" oninput="syncTextInput(this, 'product_old_price_color')">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="toggle-label">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="show_product_image" <?= !isset($template['show_product_image']) || !empty($template['show_product_image']) ? 'checked' : '' ?>>
                                    </div>
                                    <span>Mostrar Imagem</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="toggle-label">
                                    <div class="toggle-switch">
                                        <input type="checkbox" name="show_old_price" <?= !isset($template['show_old_price']) || !empty($template['show_old_price']) ? 'checked' : '' ?>>
                                    </div>
                                    <span>Mostrar Preço Antigo</span>
                                </label>
                            </div>
                        </div>
                        
                        <h4 style="margin: 20px 0 15px; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Badge de Preço</h4>
                        
                        <div class="form-group">
                            <label>Estilo do Badge</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="badge_style" value="circle" <?= ($template['badge_style'] ?? 'circle') === 'circle' ? 'checked' : '' ?>>
                                    Círculo
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="badge_style" value="square" <?= ($template['badge_style'] ?? 'circle') === 'square' ? 'checked' : '' ?>>
                                    Quadrado
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="badge_style" value="ribbon" <?= ($template['badge_style'] ?? 'circle') === 'ribbon' ? 'checked' : '' ?>>
                                    Faixa
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="badge_style" value="none" <?= ($template['badge_style'] ?? 'circle') === 'none' ? 'checked' : '' ?>>
                                    Sem Badge
                                </label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cor de Fundo</label>
                                <div class="color-picker-group">
                                    <input type="color" name="badge_bg_color" value="<?= htmlspecialchars($template['badge_bg_color'] ?? '#e8401c') ?>" oninput="syncColorInput(this, 'badge_bg_color_text')">
                                    <input type="text" id="badge_bg_color_text" class="hex-input" value="<?= htmlspecialchars($template['badge_bg_color'] ?? '#e8401c') ?>" oninput="syncTextInput(this, 'badge_bg_color')">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cor do Texto</label>
                                <div class="color-picker-group">
                                    <input type="color" name="badge_text_color" value="<?= htmlspecialchars($template['badge_text_color'] ?? '#ffffff') ?>" oninput="syncColorInput(this, 'badge_text_color_text')">
                                    <input type="text" id="badge_text_color_text" class="hex-input" value="<?= htmlspecialchars($template['badge_text_color'] ?? '#ffffff') ?>" oninput="syncTextInput(this, 'badge_text_color')">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção 5: Rodapé -->
                <div class="form-section" data-collapsed="true">
                    <div class="section-header" onclick="toggleSection(this)">
                        <h3>📍 Rodapé</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cor de Fundo</label>
                                <div class="color-picker-group">
                                    <input type="color" name="footer_bg_color" value="<?= htmlspecialchars($template['footer_bg_color'] ?? '#f5a623') ?>" oninput="syncColorInput(this, 'footer_bg_color_text')">
                                    <input type="text" id="footer_bg_color_text" class="hex-input" value="<?= htmlspecialchars($template['footer_bg_color'] ?? '#f5a623') ?>" oninput="syncTextInput(this, 'footer_bg_color')">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Cor do Texto</label>
                                <div class="color-picker-group">
                                    <input type="color" name="footer_text_color" value="<?= htmlspecialchars($template['footer_text_color'] ?? '#ffffff') ?>" oninput="syncColorInput(this, 'footer_text_color_text')">
                                    <input type="text" id="footer_text_color_text" class="hex-input" value="<?= htmlspecialchars($template['footer_text_color'] ?? '#ffffff') ?>" oninput="syncTextInput(this, 'footer_text_color')">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Mostrar no Rodapé</label>
                            <div class="checkbox-group">
                                <label class="checkbox-option">
                                    <input type="checkbox" name="footer_show_address" <?= !isset($template['footer_show_address']) || !empty($template['footer_show_address']) ? 'checked' : '' ?>>
                                    Endereço
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="footer_show_phone" <?= !isset($template['footer_show_phone']) || !empty($template['footer_show_phone']) ? 'checked' : '' ?>>
                                    Telefone
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="footer_show_whatsapp" <?= !isset($template['footer_show_whatsapp']) || !empty($template['footer_show_whatsapp']) ? 'checked' : '' ?>>
                                    WhatsApp
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="footer_show_website" <?= !empty($template['footer_show_website']) ? 'checked' : '' ?>>
                                    Site
                                </label>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="footer_show_social" <?= !empty($template['footer_show_social']) ? 'checked' : '' ?>>
                                    Redes Sociais
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Seção 6: Avançado -->
                <div class="form-section" data-collapsed="true">
                    <div class="section-header" onclick="toggleSection(this)">
                        <h3>⚙️ Personalização Avançada</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="section-content">
                        <div class="form-group">
                            <label>CSS Personalizado</label>
                            <textarea name="custom_css" rows="6" placeholder="/* Estilos aplicados sobre o template */"><?= htmlspecialchars($template['custom_css'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>HTML Extra no Cabeçalho</label>
                            <textarea name="custom_html_header" rows="3" placeholder="<!-- HTML adicional dentro do cabeçalho -->"><?= htmlspecialchars($template['custom_html_header'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>HTML Extra no Rodapé</label>
                            <textarea name="custom_html_footer" rows="3" placeholder="<!-- HTML adicional dentro do rodapé -->"><?= htmlspecialchars($template['custom_html_footer'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="editor-actions">
            <button type="button" class="btn btn-outline" onclick="window.location.href='templates.php'">← Voltar</button>
            <button type="button" class="btn btn-secondary" onclick="openPreviewModal()">👁 Ver Preview Completo</button>
            <button type="button" class="btn btn-primary" onclick="saveTemplate()">💾 Salvar Template</button>
        </div>
    </div>
    
    <!-- Painel de Preview -->
    <div class="editor-preview-panel">
        <div class="preview-header">
            <h3>👁 Preview Ao Vivo</h3>
            <div class="preview-controls">
                <button onclick="setPreviewSize('desktop')" id="btn-desktop" class="active">🖥️</button>
                <button onclick="setPreviewSize('mobile')" id="btn-mobile">📱</button>
            </div>
        </div>
        <div class="preview-content">
            <div id="previewFrame" class="preview-frame desktop">
                <!-- Preview será renderizado aqui -->
            </div>
        </div>
    </div>
</div>

<!-- Botão mobile -->
<button class="mobile-preview-btn" onclick="openPreviewModal()">👁 Ver Preview</button>

<!-- Modal de Preview Fullscreen -->
<div class="preview-modal" id="previewModal">
    <div class="preview-modal-header">
        <h3>Preview Completo</h3>
        <button onclick="closePreviewModal()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
    </div>
    <div class="preview-modal-body">
        <div id="previewModalContent">
            <!-- Preview completo será renderizado aqui -->
        </div>
    </div>
</div>

<script>
// Dados atuais do template
let templateData = <?= json_encode($template ?: []) ?>;

// Toggle sections
function toggleSection(header) {
    const section = header.parentElement;
    const collapsed = section.dataset.collapsed === 'true';
    section.dataset.collapsed = !collapsed;
}

// Sync color inputs
function syncColorInput(colorInput, textInputId) {
    document.getElementById(textInputId).value = colorInput.value;
    updatePreview();
}

function syncTextInput(textInput, colorInputName) {
    const colorInput = document.querySelector(`input[name="${colorInputName}"][type="color"]`);
    if (colorInput && /^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
        colorInput.value = textInput.value;
    }
    updatePreview();
}

// Set preview size
function setPreviewSize(size) {
    const frame = document.getElementById('previewFrame');
    frame.className = 'preview-frame ' + size;
    document.getElementById('btn-desktop').classList.toggle('active', size === 'desktop');
    document.getElementById('btn-mobile').classList.toggle('active', size === 'mobile');
}

// Update preview with debounce
let previewTimeout;
function updatePreview() {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(() => {
        const formData = new FormData(document.getElementById('templateForm'));
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Handle checkboxes
        data.header_show_logo = document.querySelector('input[name="header_show_logo"]').checked ? 1 : 0;
        data.header_show_phone = document.querySelector('input[name="header_show_phone"]').checked ? 1 : 0;
        data.show_product_image = document.querySelector('input[name="show_product_image"]').checked ? 1 : 0;
        data.show_old_price = document.querySelector('input[name="show_old_price"]').checked ? 1 : 0;
        data.footer_show_address = document.querySelector('input[name="footer_show_address"]').checked ? 1 : 0;
        data.footer_show_phone = document.querySelector('input[name="footer_show_phone"]').checked ? 1 : 0;
        data.footer_show_whatsapp = document.querySelector('input[name="footer_show_whatsapp"]').checked ? 1 : 0;
        data.footer_show_website = document.querySelector('input[name="footer_show_website"]').checked ? 1 : 0;
        data.footer_show_social = document.querySelector('input[name="footer_show_social"]').checked ? 1 : 0;
        data.is_featured = document.querySelector('input[name="is_featured"]').checked ? 1 : 0;
        
        templateData = data;
        renderPreview();
    }, 200);
}

// Render preview
function renderPreview() {
    const iframe = document.getElementById('previewFrame');
    
    // Build CSS variables
    const cssVars = `
        --header-bg: ${templateData.header_bg_color || '#e8401c'};
        --header-text: ${templateData.header_text_color || '#ffffff'};
        --body-bg: ${templateData.body_bg_color || '#ffffff'};
        --body-text: ${templateData.body_text_color || '#222222'};
        --product-card-bg: ${templateData.product_card_bg || '#f9f9f9'};
        --product-card-border: ${templateData.product_card_border || '#eeeeee'};
        --product-name-color: ${templateData.product_name_color || '#222222'};
        --product-price-color: ${templateData.product_price_color || '#e8401c'};
        --product-old-price-color: ${templateData.product_old_price_color || '#999999'};
        --badge-bg: ${templateData.badge_bg_color || '#e8401c'};
        --badge-text: ${templateData.badge_text_color || '#ffffff'};
        --footer-bg: ${templateData.footer_bg_color || '#f5a623'};
        --footer-text: ${templateData.footer_text_color || '#ffffff'};
        --product-card-radius: ${templateData.product_card_radius || 8}px;
        --primary-font: '${templateData.primary_font || 'Syne'}';
        --secondary-font: '${templateData.secondary_font || 'DM Sans'}';
    `;
    
    // Simple preview HTML
    iframe.innerHTML = `
        <div class="encarte" style="${cssVars}">
            <header class="encarte-header header-${templateData.header_layout || 'logo-left'} header-${templateData.header_height || 'medium'}">
                ${templateData.header_show_logo ? '<div class="store-logo" style="width:60px;height:60px;background:#fff;border-radius:8px;"></div>' : ''}
                <div class="header-texts">
                    <h1 class="store-name">🏪 Mercadinho Exemplo</h1>
                    <h2 class="promo-title">OFERTAS DA SEMANA</h2>
                    <p class="promo-subtitle">Preços imperdíveis!</p>
                </div>
                ${templateData.header_show_phone ? '<div class="header-phone">📞 (11) 99999-9999</div>' : ''}
            </header>
            
            <main class="encarte-body layout-${templateData.layout_style || 'grid'} cols-desktop-${templateData.product_cols_desktop || 3}">
                ${renderProductCards()}
            </main>
            
            <footer class="encarte-footer">
                ${templateData.footer_show_address ? '<p>📍 Rua Exemplo, 123 — São Paulo</p>' : ''}
                ${templateData.footer_show_phone ? '<p>📞 (11) 99999-9999</p>' : ''}
                ${templateData.footer_show_whatsapp ? '<p>💬 (11) 99999-9999</p>' : ''}
                <p>Válido de 01/07 a 07/07</p>
            </footer>
        </div>
    `;
}

function renderProductCards() {
    const showImage = templateData.show_product_image !== '0';
    const showOldPrice = templateData.show_old_price !== '0';
    const badgeStyle = templateData.badge_style || 'circle';
    
    let html = '';
    const products = [
        {name: 'Arroz Tipo 1 5kg', price: '22,90', oldPrice: '28,90'},
        {name: 'Feijão Carioca 1kg', price: '7,50', oldPrice: '9,90'},
        {name: 'Óleo de Soja 900ml', price: '8,90', oldPrice: ''},
        {name: 'Açúcar Cristal 1kg', price: '4,90', oldPrice: ''},
        {name: 'Café Torrado 500g', price: '12,90', oldPrice: '15,90'},
        {name: 'Macarrão 500g', price: '3,50', oldPrice: ''}
    ];
    
    products.forEach((p, i) => {
        html += `
            <div class="product-card ${i === 0 ? 'featured' : ''}">
                ${showImage ? '<div class="product-image-placeholder">📷</div>' : ''}
                <p class="product-name">${p.name}</p>
                ${showOldPrice && p.oldPrice ? `<p class="product-old-price">de R$ ${p.oldPrice}</p>` : ''}
                <div class="badge badge-${badgeStyle}">R$ ${p.price}</div>
            </div>
        `;
    });
    
    return html;
}

// Save template
async function saveTemplate() {
    const formData = new FormData(document.getElementById('templateForm'));
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            showToast('success', data.message);
            if (data.id && !templateData.id) {
                setTimeout(() => window.location.href = '?id=' + data.id, 1500);
            }
        } else {
            showToast('error', data.message);
        }
    } catch (error) {
        showToast('error', 'Erro: ' + error.message);
    }
}

// Preview modal
function openPreviewModal() {
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewModalContent');
    const frame = document.getElementById('previewFrame');
    content.innerHTML = frame.innerHTML;
    modal.classList.add('open');
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.remove('open');
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
