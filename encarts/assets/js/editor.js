/**
 * /assets/js/editor.js
 * Lógica do Canvas HTML5 para o editor visual de encarts
 */

// ============================================================
// ESTADO GLOBAL DO EDITOR
// ============================================================
const Editor = {
    canvas: null,
    ctx: null,
    width: 1080,
    height: 1080,
    zoom: 1,
    elements: [],
    selectedElement: null,
    history: [],
    historyIndex: -1,
    isDragging: false,
    dragOffset: { x: 0, y: 0 },
    background: { type: 'color', value: '#ffffff' }
};

// ============================================================
// INICIALIZAÇÃO
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initCanvas();
    initEventListeners();
    loadTemplates();
    
    // Carregar encart existente se houver ID
    if (window.editorConfig && window.editorConfig.encartId) {
        loadEncart(window.editorConfig.encartId);
    } else {
        saveState(); // Estado inicial
    }
});

function initCanvas() {
    Editor.canvas = document.getElementById('mainCanvas');
    Editor.ctx = Editor.canvas.getContext('2d');
    resizeCanvas(Editor.width, Editor.height);
    render();
}

function resizeCanvas(width, height) {
    Editor.width = width;
    Editor.height = height;
    Editor.canvas.width = width * Editor.zoom;
    Editor.canvas.height = height * Editor.zoom;
    Editor.canvas.style.width = (width * Editor.zoom) + 'px';
    Editor.canvas.style.height = (height * Editor.zoom) + 'px';
    Editor.ctx.scale(Editor.zoom, Editor.zoom);
}

// ============================================================
// RENDERIZAÇÃO
// ============================================================
function render() {
    const ctx = Editor.ctx;
    const w = Editor.width;
    const h = Editor.height;
    
    // Limpar canvas
    ctx.clearRect(0, 0, w, h);
    
    // Desenhar fundo
    drawBackground(ctx, w, h);
    
    // Desenhar elementos ordenados por zIndex
    const sortedElements = [...Editor.elements].sort((a, b) => a.zIndex - b.zIndex);
    sortedElements.forEach(el => drawElement(ctx, el));
    
    // Desenhar seleção
    if (Editor.selectedElement) {
        drawSelection(ctx, Editor.selectedElement);
    }
}

function drawBackground(ctx, w, h) {
    const bg = Editor.background;
    if (bg.type === 'color') {
        ctx.fillStyle = bg.value;
        ctx.fillRect(0, 0, w, h);
    } else if (bg.type === 'gradient') {
        const gradient = ctx.createLinearGradient(0, 0, bg.angle === 90 ? w : 0, bg.angle === 90 ? 0 : h);
        gradient.addColorStop(0, bg.startColor);
        gradient.addColorStop(1, bg.endColor);
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, w, h);
    }
}

function drawElement(ctx, el) {
    ctx.save();
    ctx.translate(el.x, el.y);
    ctx.rotate((el.rotation || 0) * Math.PI / 180);
    
    const x = -el.width / 2;
    const y = -el.height / 2;
    
    if (el.type === 'text') {
        drawText(ctx, el, x, y);
    } else if (el.type === 'image') {
        drawImage(ctx, el, x, y);
    } else if (el.type === 'rectangle') {
        drawRectangle(ctx, el, x, y);
    } else if (el.type === 'circle') {
        drawCircle(ctx, el, x, y);
    } else if (el.type === 'triangle') {
        drawTriangle(ctx, el, x, y);
    }
    
    ctx.restore();
}

function drawText(ctx, el, x, y) {
    const p = el.properties || {};
    ctx.font = `${p.fontStyle || 'normal'} ${p.fontWeight || 'normal'} ${p.fontSize || 24}px ${p.fontFamily || 'Arial'}`;
    ctx.fillStyle = p.color || '#000000';
    ctx.textAlign = p.textAlign || 'left';
    ctx.textBaseline = 'middle';
    
    const lines = (p.text || '').split('\n');
    const lineHeight = (p.fontSize || 24) * (p.lineHeight || 1.2);
    const totalHeight = lines.length * lineHeight;
    let startY = y;
    
    if (p.textAlign === 'center') {
        ctx.textAlign = 'center';
        lines.forEach((line, i) => {
            ctx.fillText(line, 0, startY + (i * lineHeight) - totalHeight/2 + lineHeight/2);
        });
    } else if (p.textAlign === 'right') {
        ctx.textAlign = 'right';
        lines.forEach((line, i) => {
            ctx.fillText(line, el.width/2, startY + (i * lineHeight) - totalHeight/2 + lineHeight/2);
        });
    } else {
        ctx.textAlign = 'left';
        lines.forEach((line, i) => {
            ctx.fillText(line, -el.width/2, startY + (i * lineHeight) - totalHeight/2 + lineHeight/2);
        });
    }
}

function drawImage(ctx, el, x, y) {
    if (el.imageObj) {
        ctx.drawImage(el.imageObj, x, y, el.width, el.height);
    } else {
        ctx.fillStyle = '#cccccc';
        ctx.fillRect(x, y, el.width, el.height);
        ctx.fillStyle = '#666666';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Imagem', 0, 0);
    }
}

function drawRectangle(ctx, el, x, y) {
    const p = el.properties || {};
    ctx.fillStyle = p.fillColor || '#cccccc';
    ctx.strokeStyle = p.borderColor || 'transparent';
    ctx.lineWidth = p.borderWidth || 0;
    
    const r = p.borderRadius || 0;
    if (r > 0) {
        roundRect(ctx, x, y, el.width, el.height, r);
        ctx.fill();
        if (p.borderWidth > 0) ctx.stroke();
    } else {
        ctx.fillRect(x, y, el.width, el.height);
        if (p.borderWidth > 0) ctx.strokeRect(x, y, el.width, el.height);
    }
}

function drawCircle(ctx, el, x, y) {
    const p = el.properties || {};
    ctx.beginPath();
    ctx.ellipse(0, 0, el.width/2, el.height/2, 0, 0, Math.PI * 2);
    ctx.fillStyle = p.fillColor || '#cccccc';
    ctx.fill();
    if (p.borderWidth > 0) {
        ctx.strokeStyle = p.borderColor || 'transparent';
        ctx.lineWidth = p.borderWidth;
        ctx.stroke();
    }
}

function drawTriangle(ctx, el, x, y) {
    const p = el.properties || {};
    ctx.beginPath();
    ctx.moveTo(0, -el.height/2);
    ctx.lineTo(el.width/2, el.height/2);
    ctx.lineTo(-el.width/2, el.height/2);
    ctx.closePath();
    ctx.fillStyle = p.fillColor || '#cccccc';
    ctx.fill();
    if (p.borderWidth > 0) {
        ctx.strokeStyle = p.borderColor || 'transparent';
        ctx.lineWidth = p.borderWidth;
        ctx.stroke();
    }
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

function drawSelection(ctx, el) {
    ctx.save();
    ctx.translate(el.x, el.y);
    ctx.rotate((el.rotation || 0) * Math.PI / 180);
    
    ctx.strokeStyle = '#4f46e5';
    ctx.lineWidth = 2 / Editor.zoom;
    ctx.setLineDash([5 / Editor.zoom, 5 / Editor.zoom]);
    ctx.strokeRect(-el.width/2 - 5, -el.height/2 - 5, el.width + 10, el.height + 10);
    
    // Handles de redimensionamento
    ctx.fillStyle = '#4f46e5';
    ctx.setLineDash([]);
    const handles = [
        [-el.width/2 - 5, -el.height/2 - 5],
        [el.width/2 + 5, -el.height/2 - 5],
        [-el.width/2 - 5, el.height/2 + 5],
        [el.width/2 + 5, el.height/2 + 5]
    ];
    handles.forEach(([hx, hy]) => {
        ctx.fillRect(hx - 4, hy - 4, 8, 8);
    });
    
    ctx.restore();
}

// ============================================================
// EVENTOS DO MOUSE
// ============================================================
function initEventListeners() {
    Editor.canvas.addEventListener('mousedown', handleMouseDown);
    Editor.canvas.addEventListener('mousemove', handleMouseMove);
    Editor.canvas.addEventListener('mouseup', handleMouseUp);
    Editor.canvas.addEventListener('dblclick', handleDoubleClick);
    
    // Zoom
    document.getElementById('zoomIn').addEventListener('click', () => setZoom(Editor.zoom + 0.1));
    document.getElementById('zoomOut').addEventListener('click', () => setZoom(Editor.zoom - 0.1));
    
    // Toolbar
    document.getElementById('saveBtn').addEventListener('click', saveEncart);
    document.getElementById('exportBtn').addEventListener('click', showExportModal);
    document.getElementById('confirmExport').addEventListener('click', exportImage);
    document.getElementById('undoBtn').addEventListener('click', undo);
    document.getElementById('redoBtn').addEventListener('click', redo);
    
    // Format select
    document.getElementById('formatSelect').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const w = parseInt(option.dataset.w);
        const h = parseInt(option.dataset.h);
        resizeCanvas(w, h);
        render();
    });
    
    // Tabs
    document.querySelectorAll('.sidebar-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('d-none'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.remove('d-none');
        });
    });
    
    // Elementos
    document.querySelectorAll('.element-item').forEach(item => {
        item.addEventListener('click', function() {
            addElement(this.dataset.type);
        });
    });
    
    // Upload de imagem
    document.getElementById('imageUpload').addEventListener('change', handleImageUpload);
    
    // Teclado
    document.addEventListener('keydown', handleKeyDown);
    
    // Export modal options
    document.querySelectorAll('.export-option').forEach(opt => {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.export-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
}

function getMousePos(e) {
    const rect = Editor.canvas.getBoundingClientRect();
    return {
        x: (e.clientX - rect.left) / Editor.zoom,
        y: (e.clientY - rect.top) / Editor.zoom
    };
}

function handleMouseDown(e) {
    const pos = getMousePos(e);
    
    // Verificar clique em elemento (de cima para baixo)
    const sortedElements = [...Editor.elements].sort((a, b) => b.zIndex - a.zIndex);
    const clicked = sortedElements.find(el => isPointInElement(pos, el));
    
    if (clicked) {
        Editor.selectedElement = clicked;
        Editor.isDragging = true;
        Editor.dragOffset = {
            x: pos.x - clicked.x,
            y: pos.y - clicked.y
        };
        updatePropertiesPanel();
        renderLayersList();
        render();
    } else {
        Editor.selectedElement = null;
        updatePropertiesPanel();
        renderLayersList();
        render();
    }
}

function handleMouseMove(e) {
    if (!Editor.isDragging || !Editor.selectedElement) return;
    
    const pos = getMousePos(e);
    Editor.selectedElement.x = pos.x - Editor.dragOffset.x;
    Editor.selectedElement.y = pos.y - Editor.dragOffset.y;
    render();
}

function handleMouseUp() {
    if (Editor.isDragging) {
        saveState();
    }
    Editor.isDragging = false;
}

function handleDoubleClick(e) {
    if (Editor.selectedElement && Editor.selectedElement.type === 'text') {
        const newText = prompt('Editar texto:', Editor.selectedElement.properties.text);
        if (newText !== null) {
            Editor.selectedElement.properties.text = newText;
            saveState();
            render();
        }
    }
}

function isPointInElement(pos, el) {
    return pos.x >= el.x - el.width/2 && 
           pos.x <= el.x + el.width/2 && 
           pos.y >= el.y - el.height/2 && 
           pos.y <= el.y + el.height/2;
}

// ============================================================
// ADICIONAR ELEMENTOS
// ============================================================
function addElement(type) {
    const element = {
        id: generateUUID(),
        type: type,
        x: Editor.width / 2,
        y: Editor.height / 2,
        width: type === 'text' ? 300 : 200,
        height: type === 'text' ? 50 : 200,
        rotation: 0,
        zIndex: Editor.elements.length + 1,
        properties: getDefaultProperties(type)
    };
    
    Editor.elements.push(element);
    Editor.selectedElement = element;
    saveState();
    updatePropertiesPanel();
    renderLayersList();
    render();
}

function getDefaultProperties(type) {
    switch(type) {
        case 'text':
            return {
                text: 'Seu Texto',
                fontSize: 32,
                fontFamily: 'Poppins',
                fontWeight: 'normal',
                fontStyle: 'normal',
                textAlign: 'center',
                color: '#333333',
                lineHeight: 1.2
            };
        case 'image':
            return { src: '' };
        case 'rectangle':
            return { fillColor: '#4f46e5', borderColor: 'transparent', borderWidth: 0, borderRadius: 0 };
        case 'circle':
            return { fillColor: '#4f46e5', borderColor: 'transparent', borderWidth: 0 };
        case 'triangle':
            return { fillColor: '#4f46e5', borderColor: 'transparent', borderWidth: 0 };
        default:
            return {};
    }
}

function handleImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    uploadImage(file).then(result => {
        const img = new Image();
        img.onload = () => {
            const aspectRatio = img.width / img.height;
            const width = 300;
            const height = 300 / aspectRatio;
            
            const element = {
                id: generateUUID(),
                type: 'image',
                x: Editor.width / 2,
                y: Editor.height / 2,
                width: width,
                height: height,
                rotation: 0,
                zIndex: Editor.elements.length + 1,
                properties: { src: result.data.url },
                imageObj: img
            };
            
            Editor.elements.push(element);
            Editor.selectedElement = element;
            saveState();
            renderLayersList();
            render();
        };
        img.src = result.data.url;
    }).catch(err => {
        alert('Erro no upload: ' + err.message);
    });
    
    e.target.value = '';
}

// ============================================================
// HISTÓRICO (UNDO/REDO)
// ============================================================
function saveState() {
    const state = JSON.stringify({
        elements: Editor.elements,
        background: Editor.background
    });
    
    // Remover estados futuros se estivermos no meio do histórico
    Editor.history = Editor.history.slice(0, Editor.historyIndex + 1);
    Editor.history.push(state);
    Editor.historyIndex++;
    
    // Limitar histórico a 50 estados
    if (Editor.history.length > 50) {
        Editor.history.shift();
        Editor.historyIndex--;
    }
}

function undo() {
    if (Editor.historyIndex > 0) {
        Editor.historyIndex--;
        loadState(Editor.history[Editor.historyIndex]);
    }
}

function redo() {
    if (Editor.historyIndex < Editor.history.length - 1) {
        Editor.historyIndex++;
        loadState(Editor.history[Editor.historyIndex]);
    }
}

function loadState(stateJson) {
    const state = JSON.parse(stateJson);
    Editor.elements = state.elements || [];
    Editor.background = state.background || { type: 'color', value: '#ffffff' };
    Editor.selectedElement = null;
    renderLayersList();
    updatePropertiesPanel();
    render();
}

// ============================================================
// PROPRIEDADES E CAMADAS
// ============================================================
function updatePropertiesPanel() {
    const container = document.getElementById('propertiesContent');
    
    if (!Editor.selectedElement) {
        container.innerHTML = '<p class="text-muted small">Selecione um elemento para editar</p>';
        return;
    }
    
    const el = Editor.selectedElement;
    const p = el.properties || {};
    
    let html = `
        <div class="property-row">
            <span class="property-label">Tipo</span>
            <span class="property-input">${el.type}</span>
        </div>
        <div class="property-row">
            <span class="property-label">X</span>
            <div class="property-input"><input type="number" value="${Math.round(el.x)}" onchange="updateElement('x', this.value)"></div>
        </div>
        <div class="property-row">
            <span class="property-label">Y</span>
            <div class="property-input"><input type="number" value="${Math.round(el.y)}" onchange="updateElement('y', this.value)"></div>
        </div>
        <div class="property-row">
            <span class="property-label">Largura</span>
            <div class="property-input"><input type="number" value="${Math.round(el.width)}" onchange="updateElement('width', this.value)"></div>
        </div>
        <div class="property-row">
            <span class="property-label">Altura</span>
            <div class="property-input"><input type="number" value="${Math.round(el.height)}" onchange="updateElement('height', this.value)"></div>
        </div>
        <div class="property-row">
            <span class="property-label">Rotação</span>
            <div class="property-input"><input type="range" min="0" max="360" value="${el.rotation || 0}" oninput="updateElement('rotation', this.value)"></div>
        </div>
    `;
    
    if (el.type === 'text') {
        html += `
            <div class="property-row">
                <span class="property-label">Texto</span>
                <div class="property-input"><textarea class="form-control" rows="3" onchange="updateProperty('text', this.value)">${p.text || ''}</textarea></div>
            </div>
            <div class="property-row">
                <span class="property-label">Cor</span>
                <div class="property-input"><input type="color" value="${p.color || '#333333'}" onchange="updateProperty('color', this.value)"></div>
            </div>
            <div class="property-row">
                <span class="property-label">Tamanho</span>
                <div class="property-input"><input type="number" value="${p.fontSize || 24}" onchange="updateProperty('fontSize', this.value)"></div>
            </div>
            <div class="property-row">
                <span class="property-label">Fonte</span>
                <div class="property-input">
                    <select onchange="updateProperty('fontFamily', this.value)">
                        <option value="Poppins" ${p.fontFamily === 'Poppins' ? 'selected' : ''}>Poppins</option>
                        <option value="Open Sans" ${p.fontFamily === 'Open Sans' ? 'selected' : ''}>Open Sans</option>
                        <option value="Roboto" ${p.fontFamily === 'Roboto' ? 'selected' : ''}>Roboto</option>
                        <option value="Montserrat" ${p.fontFamily === 'Montserrat' ? 'selected' : ''}>Montserrat</option>
                        <option value="Anton" ${p.fontFamily === 'Anton' ? 'selected' : ''}>Anton</option>
                    </select>
                </div>
            </div>
            <div class="property-row">
                <span class="property-label">Alinh.</span>
                <div class="property-input">
                    <select onchange="updateProperty('textAlign', this.value)">
                        <option value="left" ${p.textAlign === 'left' ? 'selected' : ''}>Esquerda</option>
                        <option value="center" ${p.textAlign === 'center' ? 'selected' : ''}>Centro</option>
                        <option value="right" ${p.textAlign === 'right' ? 'selected' : ''}>Direita</option>
                    </select>
                </div>
            </div>
        `;
    } else if (el.type !== 'image') {
        html += `
            <div class="property-row">
                <span class="property-label">Cor</span>
                <div class="property-input"><input type="color" value="${p.fillColor || '#cccccc'}" onchange="updateProperty('fillColor', this.value)"></div>
            </div>
        `;
    }
    
    html += `
        <div class="mt-3">
            <button class="btn btn-danger btn-sm w-100" onclick="deleteSelectedElement()"><i class="bi bi-trash"></i> Excluir Elemento</button>
        </div>
    `;
    
    container.innerHTML = html;
}

function updateElement(prop, value) {
    if (!Editor.selectedElement) return;
    Editor.selectedElement[prop] = parseFloat(value);
    saveState();
    render();
}

function updateProperty(prop, value) {
    if (!Editor.selectedElement) return;
    if (!Editor.selectedElement.properties) Editor.selectedElement.properties = {};
    Editor.selectedElement.properties[prop] = prop === 'fontSize' ? parseInt(value) : value;
    saveState();
    render();
}

function deleteSelectedElement() {
    if (!Editor.selectedElement) return;
    Editor.elements = Editor.elements.filter(el => el.id !== Editor.selectedElement.id);
    Editor.selectedElement = null;
    saveState();
    renderLayersList();
    updatePropertiesPanel();
    render();
}

function renderLayersList() {
    const container = document.getElementById('layersList');
    const sortedElements = [...Editor.elements].sort((a, b) => b.zIndex - a.zIndex);
    
    container.innerHTML = sortedElements.map((el, index) => `
        <div class="layer-item ${Editor.selectedElement && Editor.selectedElement.id === el.id ? 'active' : ''}" onclick="selectElementById('${el.id}')">
            <span class="layer-drag-handle"><i class="bi bi-grip-vertical"></i></span>
            <span class="layer-icon">${getElementIcon(el.type)}</span>
            <span class="layer-name">${getLayerName(el)}</span>
            <div class="layer-actions">
                <button class="layer-action-btn" onclick="event.stopPropagation(); moveLayer('${el.id}', 'up')"><i class="bi bi-arrow-up"></i></button>
                <button class="layer-action-btn" onclick="event.stopPropagation(); moveLayer('${el.id}', 'down')"><i class="bi bi-arrow-down"></i></button>
            </div>
        </div>
    `).join('');
}

function getElementIcon(type) {
    const icons = { text: '📝', image: '🖼️', rectangle: '⬜', circle: '⭕', triangle: '🔺' };
    return icons[type] || '❓';
}

function getLayerName(el) {
    if (el.type === 'text') return (el.properties.text || 'Texto').substring(0, 20);
    return el.type.charAt(0).toUpperCase() + el.type.slice(1);
}

function selectElementById(id) {
    Editor.selectedElement = Editor.elements.find(el => el.id === id);
    updatePropertiesPanel();
    renderLayersList();
    render();
}

function moveLayer(id, direction) {
    const index = Editor.elements.findIndex(el => el.id === id);
    if (index === -1) return;
    
    if (direction === 'up' && index < Editor.elements.length - 1) {
        [Editor.elements[index], Editor.elements[index + 1]] = [Editor.elements[index + 1], Editor.elements[index]];
    } else if (direction === 'down' && index > 0) {
        [Editor.elements[index], Editor.elements[index - 1]] = [Editor.elements[index - 1], Editor.elements[index]];
    }
    
    // Atualizar zIndex
    Editor.elements.forEach((el, i) => el.zIndex = i + 1);
    saveState();
    renderLayersList();
    render();
}

// ============================================================
// ZOOM
// ============================================================
function setZoom(level) {
    Editor.zoom = Math.max(0.25, Math.min(2, level));
    document.getElementById('zoomLevel').textContent = Math.round(Editor.zoom * 100) + '%';
    resizeCanvas(Editor.width, Editor.height);
    render();
}

// ============================================================
// TEMPLATES
// ============================================================
async function loadTemplates() {
    try {
        const result = await getTemplates();
        const grid = document.getElementById('templatesGrid');
        
        if (result.success && result.data.length > 0) {
            grid.innerHTML = result.data.map(t => `
                <div class="template-item ${t.is_premium ? 'premium' : ''}" onclick="loadTemplate(${t.id})">
                    <div class="template-item-name">${escapeHtml(t.name)}</div>
                </div>
            `).join('');
        } else {
            grid.innerHTML = '<p class="text-muted small">Nenhum template disponível</p>';
        }
    } catch (error) {
        console.error('Error loading templates:', error);
    }
}

async function loadTemplate(id) {
    try {
        const result = await getTemplate(id);
        if (result.success) {
            const data = result.data.canvas_data;
            Editor.elements = data.elements || [];
            Editor.background = data.background || { type: 'color', value: '#ffffff' };
            resizeCanvas(data.width, data.height);
            saveState();
            renderLayersList();
            render();
            showToast('Template carregado!', 'success');
        }
    } catch (error) {
        showToast('Erro ao carregar template', 'danger');
    }
}

// ============================================================
// SALVAR E EXPORTAR
// ============================================================
async function saveEncart() {
    const title = prompt('Título do encart:', 'Meu Encart');
    if (!title) return;
    
    const canvasData = {
        version: '1.0',
        width: Editor.width,
        height: Editor.height,
        background: Editor.background,
        elements: Editor.elements.map(el => ({
            ...el,
            imageObj: undefined // Remover objeto Image não serializável
        }))
    };
    
    try {
        if (window.editorConfig.encartId) {
            await updateEncart(window.editorConfig.encartId, {
                title: title,
                canvas_data: canvasData
            });
            showToast('Encart atualizado com sucesso!', 'success');
        } else {
            const result = await createEncart(title, canvasData, Editor.width, Editor.height, 
                document.getElementById('formatSelect').value);
            if (result.success) {
                window.editorConfig.encartId = result.encart_id;
                document.getElementById('encartTitle').textContent = 'Editando Encart';
                showToast('Encart criado com sucesso!', 'success');
            }
        }
    } catch (error) {
        showToast('Erro ao salvar: ' + error.message, 'danger');
    }
}

function showExportModal() {
    document.getElementById('exportModal').classList.remove('hidden');
}

function exportImage() {
    const format = document.querySelector('.export-option.selected').dataset.format;
    const link = document.createElement('a');
    link.download = `encart-${Date.now()}.${format}`;
    link.href = Editor.canvas.toDataURL(`image/${format}`, 0.9);
    link.click();
    document.getElementById('exportModal').classList.add('hidden');
    showToast('Download iniciado!', 'success');
}

// ============================================================
// CARREGAR ENCART EXISTENTE
// ============================================================
async function loadEncart(id) {
    try {
        const result = await getEncart(id);
        if (result.success) {
            const data = result.data.canvas_data;
            Editor.elements = data.elements || [];
            Editor.background = data.background || { type: 'color', value: '#ffffff' };
            resizeCanvas(data.width, data.height);
            
            // Carregar imagens
            for (const el of Editor.elements) {
                if (el.type === 'image' && el.properties.src) {
                    const img = new Image();
                    img.src = el.properties.src;
                    el.imageObj = img;
                }
            }
            
            saveState();
            renderLayersList();
            render();
        }
    } catch (error) {
        console.error('Error loading encart:', error);
    }
}

// ============================================================
// TECLADO
// ============================================================
function handleKeyDown(e) {
    if (e.key === 'Delete' || e.key === 'Backspace') {
        if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            deleteSelectedElement();
        }
    }
    if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
        e.preventDefault();
        if (e.shiftKey) redo(); else undo();
    }
}

// ============================================================
// UTILITÁRIOS
// ============================================================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
