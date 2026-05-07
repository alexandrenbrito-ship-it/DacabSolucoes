/**
 * /assets/js/app.js
 * Funções utilitárias e chamadas AJAX às APIs
 */

// ============================================================
// CONFIGURAÇÃO GLOBAL
// ============================================================
const API_BASE = '/encarts/api/';

// ============================================================
// FUNÇÕES DE API
// ============================================================

/**
 * Faz uma requisição fetch com tratamento de erros
 */
async function apiRequest(endpoint, options = {}) {
    const url = API_BASE + endpoint;
    
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    };
    
    const config = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, config);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Erro na requisição');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ============================================================
// AUTENTICAÇÃO
// ============================================================

async function login(email, password) {
    return await apiRequest('auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'login', email, password })
    });
}

async function logout() {
    return await apiRequest('auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' })
    });
}

async function register(name, email, password, plan = 'free') {
    return await apiRequest('auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'register', name, email, password, plan })
    });
}

async function checkAuth() {
    return await apiRequest('auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'check' })
    });
}

// ============================================================
// ENCARTS
// ============================================================

async function getEncarts(options = {}) {
    const params = new URLSearchParams();
    if (options.orderBy) params.append('orderBy', options.orderBy);
    if (options.order) params.append('order', options.order);
    if (options.limit) params.append('limit', options.limit);
    if (options.offset) params.append('offset', options.offset);
    
    const queryString = params.toString();
    return await apiRequest('encarts.php' + (queryString ? '?' + queryString : ''));
}

async function getEncart(id) {
    return await apiRequest('encarts.php?action=single&id=' + id);
}

async function createEncart(title, canvasData, width, height, format = 'post') {
    return await apiRequest('encarts.php', {
        method: 'POST',
        body: JSON.stringify({ title, canvas_data: canvasData, width, height, format })
    });
}

async function updateEncart(id, data) {
    return await apiRequest('encarts.php', {
        method: 'PUT',
        body: JSON.stringify({ id, ...data })
    });
}

async function deleteEncart(id) {
    return await apiRequest('encarts.php?id=' + id, {
        method: 'DELETE'
    });
}

async function duplicateEncart(id, newTitle = '') {
    return await apiRequest('encarts.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'duplicate', id, new_title: newTitle })
    });
}

async function searchEncarts(term) {
    return await apiRequest('encarts.php?action=search&q=' + encodeURIComponent(term));
}

async function getPublicEncarts(category = '', limit = 20) {
    const params = new URLSearchParams({ action: 'public' });
    if (category) params.append('category', category);
    if (limit) params.append('limit', limit);
    
    return await apiRequest('encarts.php?' + params.toString());
}

// ============================================================
// TEMPLATES
// ============================================================

async function getTemplates(category = null, includePremium = true) {
    const params = new URLSearchParams({ action: 'list' });
    if (category) params.append('category', category);
    
    return await apiRequest('templates.php?' + params.toString());
}

async function getTemplate(id) {
    return await apiRequest('templates.php?action=single&id=' + id);
}

async function getTemplateCategories() {
    return await apiRequest('templates.php?action=categories');
}

async function searchTemplates(term) {
    return await apiRequest('templates.php?action=search&search=' + encodeURIComponent(term));
}

// ============================================================
// UPLOAD
// ============================================================

async function uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    
    try {
        const response = await fetch(API_BASE + 'upload.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Erro no upload');
        }
        
        return data;
    } catch (error) {
        console.error('Upload Error:', error);
        throw error;
    }
}

// ============================================================
// UTILITÁRIOS
// ============================================================

/**
 * Gera um UUID único
 */
function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/**
 * Formata data para exibição
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    
    if (days > 7) {
        return date.toLocaleDateString('pt-BR');
    } else if (days > 0) {
        return `${days} dia${days > 1 ? 's' : ''} atrás`;
    } else if (hours > 0) {
        return `${hours} hora${hours > 1 ? 's' : ''} atrás`;
    } else if (minutes > 0) {
        return `${minutes} minuto${minutes > 1 ? 's' : ''} atrás`;
    } else {
        return 'Agora mesmo';
    }
}

/**
 * Mostra notificação toast
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Debounce para funções
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Deep clone de objeto
 */
function deepClone(obj) {
    return JSON.parse(JSON.stringify(obj));
}

// ============================================================
// INICIALIZAÇÃO
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    // Verificar autenticação ao carregar a página
    if (typeof window.currentUser === 'undefined') {
        checkAuth().then(data => {
            window.currentUser = data.authenticated ? data.user : null;
            
            // Disparar evento customizado
            window.dispatchEvent(new CustomEvent('auth-check-complete', { 
                detail: { authenticated: data.authenticated, user: data.user }
            }));
        }).catch(() => {
            window.currentUser = null;
        });
    }
});

// Adicionar animações CSS para toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
