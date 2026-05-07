<?php
/**
 * /encarts/editor.php
 * Editor visual de encarts com Canvas HTML5
 */

require_once __DIR__ . '/config/clerk.php';
require_once __DIR__ . '/classes/ClerkAuth.php';

// Verificar autenticação via Clerk
$user = ClerkAuth::requireAuth();
$encartId = isset($_GET['id']) ? (int)$_GET['id'] : null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor - Encarts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Open+Sans&family=Roboto:wght@300;400;700&family=Montserrat:wght@400;700;900&family=Anton&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/encarts/assets/css/editor.css">
    
    <!-- Clerk JS -->
    <script src="https://cdn.jsdelivr.net/npm/@clerk/clerk-js@latest/dist/clerk.browser.js"></script>
</head>
<body>
    <div class="editor-layout">
        <!-- Toolbar -->
        <header class="editor-toolbar">
            <div class="toolbar-left">
                <a href="dashboard.php" class="toolbar-btn"><i class="bi bi-arrow-left"></i></a>
                <span class="toolbar-title" id="encartTitle"><?php echo $encartId ? 'Editando Encart' : 'Novo Encart'; ?></span>
                <div class="toolbar-divider"></div>
                <select class="form-select form-select-sm" id="formatSelect" style="width: auto;">
                    <option value="post" data-w="1080" data-h="1080">Post (1080x1080)</option>
                    <option value="story" data-w="1080" data-h="1920">Story (1080x1920)</option>
                    <option value="banner" data-w="1200" data-h="628">Banner (1200x628)</option>
                    <option value="cover" data-w="820" data-h="312">Cover FB (820x312)</option>
                </select>
            </div>
            <div class="toolbar-right">
                <button class="toolbar-btn icon-only" id="undoBtn" title="Desfazer"><i class="bi bi-arrow-counterclockwise"></i></button>
                <button class="toolbar-btn icon-only" id="redoBtn" title="Refazer"><i class="bi bi-arrow-clockwise"></i></button>
                <div class="toolbar-divider"></div>
                <button class="toolbar-btn" id="saveBtn"><i class="bi bi-save"></i> Salvar</button>
                <button class="toolbar-btn primary" id="exportBtn"><i class="bi bi-download"></i> Exportar</button>
            </div>
        </header>

        <!-- Sidebar Esquerda -->
        <aside class="editor-sidebar-left">
            <div class="sidebar-tabs">
                <button class="sidebar-tab active" data-tab="templates">Templates</button>
                <button class="sidebar-tab" data-tab="elements">Elementos</button>
            </div>
            <div class="sidebar-content">
                <div id="tab-templates" class="tab-content">
                    <div class="templates-grid" id="templatesGrid"></div>
                </div>
                <div id="tab-elements" class="tab-content d-none">
                    <div class="elements-list">
                        <div class="element-item" data-type="text"><div class="element-icon"><i class="bi bi-type"></i></div><div class="element-info"><div class="element-name">Texto</div><div class="element-desc">Adicionar texto</div></div></div>
                        <div class="element-item" data-type="image"><div class="element-icon"><i class="bi bi-image"></i></div><div class="element-info"><div class="element-name">Imagem</div><div class="element-desc">Upload ou URL</div></div></div>
                        <div class="element-item" data-type="rectangle"><div class="element-icon"><i class="bi bi-square"></i></div><div class="element-info"><div class="element-name">Retângulo</div><div class="element-desc">Forma quadrada</div></div></div>
                        <div class="element-item" data-type="circle"><div class="element-icon"><i class="bi bi-circle"></i></div><div class="element-info"><div class="element-name">Círculo</div><div class="element-desc">Forma redonda</div></div></div>
                        <div class="element-item" data-type="triangle"><div class="element-icon"><i class="bi bi-play-fill"></i></div><div class="element-info"><div class="element-name">Triângulo</div><div class="element-desc">Forma triangular</div></div></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Área do Canvas -->
        <main class="editor-canvas-area">
            <div class="canvas-wrapper" id="canvasWrapper">
                <canvas id="mainCanvas"></canvas>
            </div>
            <div class="canvas-controls">
                <button class="zoom-btn" id="zoomOut"><i class="bi bi-dash-lg"></i></button>
                <span class="zoom-control" id="zoomLevel">100%</span>
                <button class="zoom-btn" id="zoomIn"><i class="bi bi-plus-lg"></i></button>
            </div>
        </main>

        <!-- Sidebar Direita -->
        <aside class="editor-sidebar-right">
            <div class="property-section" id="propertiesPanel">
                <div class="property-section-header"><span>Propriedades</span><i class="bi bi-chevron-down toggle-icon"></i></div>
                <div class="property-section-content" id="propertiesContent">
                    <p class="text-muted small">Selecione um elemento para editar</p>
                </div>
            </div>
            <div class="property-section">
                <div class="property-section-header"><span>Camadas</span><i class="bi bi-chevron-down toggle-icon"></i></div>
                <div class="property-section-content">
                    <div class="layers-list" id="layersList"></div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Modal Exportar -->
    <div class="export-modal hidden" id="exportModal">
        <div class="export-content">
            <h5>Exportar Encart</h5>
            <div class="export-options">
                <div class="export-option selected" data-format="png"><div class="export-option-icon">🖼️</div><div class="export-option-name">PNG</div></div>
                <div class="export-option" data-format="jpg"><div class="export-option-icon">📷</div><div class="export-option-name">JPG</div></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary flex-fill" id="confirmExport">Baixar</button>
            </div>
        </div>
    </div>

    <input type="file" id="imageUpload" accept="image/*" class="d-none">
    <script src="/encarts/assets/js/app.js"></script>
    <script src="/encarts/assets/js/editor.js"></script>
    <script>
        window.editorConfig = {
            encartId: <?php echo $encartId ? $encartId : 'null'; ?>,
            userId: <?php echo (int)$user['id']; ?>,
            userName: <?php echo json_encode($user['name']); ?>
        };
    </script>
</body>
</html>
