<?php
/**
 * /encarts/dashboard.php
 * Painel do usuário - Lista de encarts criados
 * Autenticação via Clerk
 */

require_once __DIR__ . '/config/clerk.php';
require_once __DIR__ . '/classes/ClerkAuth.php';
require_once __DIR__ . '/classes/User.php';

// Verificar autenticação via Clerk
$user = ClerkAuth::requireAuth();
$userModel = new User();
$limits = $userModel->getPlanLimits((int)$user['id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Encarts</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/encarts/assets/css/style.css">
    
    <!-- Clerk JS -->
    <script src="https://cdn.jsdelivr.net/npm/@clerk/clerk-js@latest/dist/clerk.browser.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="/encarts/" class="navbar-brand">
                <i class="bi bi-layers-fill"></i>
                Encarts
            </a>
            <ul class="navbar-nav">
                <li><span class="nav-link" id="userNameDisplay">Olá, <?php echo htmlspecialchars($user['name']); ?></span></li>
                <li><span class="badge bg-<?php echo $user['plan'] === 'pro' ? 'warning' : 'secondary'; ?>" id="userPlanBadge"><?php echo ucfirst($user['plan']); ?></span></li>
                <li><a href="editor.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo Encart</a></li>
                <li><button class="btn btn-secondary" id="logoutBtn"><i class="bi bi-box-arrow-right"></i> Sair</button></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container py-4">
        <!-- Stats Bar -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="feature-icon mb-0"><i class="bi bi-images"></i></div>
                        <div>
                            <div class="text-muted small">Meus Encarts</div>
                            <div class="h4 mb-0"><?php echo $limits['used']; ?> / <?php echo $limits['limit'] === PHP_INT_MAX ? '∞' : $limits['limit']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="feature-icon mb-0"><i class="bi bi-palette"></i></div>
                        <div>
                            <div class="text-muted small">Templates</div>
                            <div class="h4 mb-0"><?php echo $user['plan'] === 'pro' ? 'Todos' : 'Básicos'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="feature-icon mb-0"><i class="bi bi-crown"></i></div>
                        <div>
                            <div class="text-muted small">Plano</div>
                            <div class="h4 mb-0"><?php echo ucfirst($user['plan']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerta de Limite -->
        <?php if ($limits['remaining'] <= 2 && $limits['limit'] !== PHP_INT_MAX): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Você tem apenas <strong><?php echo $limits['remaining']; ?></strong> encart(s) restante(s) no seu plano.
            <a href="#" class="alert-link">Faça upgrade para Pro</a> para criar ilimitados!
        </div>
        <?php endif; ?>

        <!-- Header com Busca -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Meus Encarts</h2>
            <div class="d-flex gap-2">
                <input type="text" class="form-control" id="searchInput" placeholder="Buscar encarts..." style="max-width: 250px;">
                <select class="form-select" id="sortSelect" style="max-width: 150px;">
                    <option value="created_at_DESC">Mais recentes</option>
                    <option value="created_at_ASC">Mais antigos</option>
                    <option value="title_ASC">Nome (A-Z)</option>
                    <option value="title_DESC">Nome (Z-A)</option>
                </select>
            </div>
        </div>

        <!-- Grid de Encarts -->
        <div id="encartsGrid" class="encarts-grid">
            <!-- Preenchido via JavaScript -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        </div>

        <!-- Estado Vazio -->
        <div id="emptyState" class="text-center py-5 d-none">
            <i class="bi bi-images display-1 text-muted"></i>
            <h4 class="mt-3">Nenhum encart ainda</h4>
            <p class="text-muted">Crie seu primeiro design clicando em "Novo Encart"</p>
            <a href="editor.php" class="btn btn-primary mt-2"><i class="bi bi-plus-lg"></i> Criar Primeiro Encart</a>
        </div>
    </main>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Excluir Encart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este encart? Esta ação não pode ser desfeita.</p>
                    <input type="hidden" id="deleteEncartId">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/encarts/assets/js/app.js"></script>
    <script>
        let currentEncarts = [];
        
        // Inicializar Clerk e verificar auth
        const clerkPublishableKey = '<?php echo CLERK_PUBLISHABLE_KEY; ?>';
        
        async function initClerkAndCheck() {
            if (!clerkPublishableKey) {
                console.error('CLERK_PUBLISHABLE_KEY não configurado');
                return;
            }
            
            try {
                await clerk.load();
                
                // Se não estiver logado, redirecionar para index
                if (!clerk.user) {
                    window.location.href = '/encarts/index.php';
                    return;
                }
                
                // Atualizar informações do usuário na UI
                updateUserInfo(clerk.user);
            } catch (error) {
                console.error('Erro ao inicializar Clerk:', error);
            }
        }
        
        function updateUserInfo(user) {
            const nameDisplay = document.getElementById('userNameDisplay');
            if (user.firstName || user.primaryEmailAddress) {
                const firstName = user.firstName || user.primaryEmailAddress?.emailAddress?.split('@')[0] || 'Usuário';
                nameDisplay.textContent = 'Olá, ' + firstName;
            }
        }

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            await clerk.signOut();
            window.location.href = '/encarts/index.php';
        });

        // Carregar encarts
        async function loadEncarts() {
            const grid = document.getElementById('encartsGrid');
            const emptyState = document.getElementById('emptyState');
            
            try {
                const result = await getEncarts({ limit: 50 });
                currentEncarts = result.data || [];
                
                if (currentEncarts.length === 0) {
                    grid.classList.add('d-none');
                    emptyState.classList.remove('d-none');
                    return;
                }
                
                grid.classList.remove('d-none');
                emptyState.classList.add('d-none');
                
                renderEncarts(currentEncarts);
            } catch (error) {
                console.error('Error loading encarts:', error);
                grid.innerHTML = '<div class="alert alert-danger">Erro ao carregar encarts.</div>';
            }
        }

        // Renderizar encarts
        function renderEncarts(encarts) {
            const grid = document.getElementById('encartsGrid');
            
            grid.innerHTML = encarts.map(encart => `
                <div class="encart-card" data-id="${encart.id}">
                    <div class="encart-thumbnail">
                        ${encart.thumbnail_url 
                            ? `<img src="${encart.thumbnail_url}" alt="${encart.title}">`
                            : `<div class="text-muted"><i class="bi bi-image display-4"></i></div>`
                        }
                    </div>
                    <div class="encart-info">
                        <div class="encart-title">${escapeHtml(encart.title)}</div>
                        <div class="encart-meta">
                            <span><i class="bi bi-arrows-fullscreen"></i> ${encart.width}x${encart.height}</span>
                            <span><i class="bi bi-clock"></i> ${formatDate(encart.updated_at)}</span>
                        </div>
                    </div>
                    <div class="encart-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="editEncart(${encart.id})">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="togglePublic(${encart.id}, ${encart.is_public})">
                            <i class="bi bi-${encart.is_public ? 'eye' : 'eye-slash'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="showDeleteModal(${encart.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Editar encart
        function editEncart(id) {
            window.location.href = `/encarts/editor.php?id=${id}`;
        }

        // Toggle público/privado
        async function togglePublic(id, isPublic) {
            try {
                await updateEncart(id, { is_public: !isPublic });
                showToast(isPublic ? 'Encart tornado privado' : 'Encart tornado público', 'success');
                loadEncarts();
            } catch (error) {
                showToast('Erro ao atualizar', 'danger');
            }
        }

        // Mostrar modal de exclusão
        function showDeleteModal(id) {
            document.getElementById('deleteEncartId').value = id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Confirmar exclusão
        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            const id = document.getElementById('deleteEncartId').value;
            
            try {
                await deleteEncart(id);
                showToast('Encart excluído com sucesso!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                loadEncarts();
            } catch (error) {
                showToast('Erro ao excluir encart', 'danger');
            }
        });

        // Busca
        document.getElementById('searchInput').addEventListener('input', debounce(function(e) {
            const term = e.target.value.trim();
            if (term) {
                searchEncarts(term).then(result => {
                    renderEncarts(result.data || []);
                });
            } else {
                loadEncarts();
            }
        }, 300));

        // Ordenação
        document.getElementById('sortSelect').addEventListener('change', function() {
            const [orderBy, order] = this.value.split('_');
            getEncarts({ orderBy, order, limit: 50 }).then(result => {
                renderEncarts(result.data || []);
            });
        });

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Inicializar
        initClerkAndCheck();
        loadEncarts();
    </script>
</body>
</html>
