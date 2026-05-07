<?php
/**
 * /encarts/admin.php
 * Painel Administrativo - Gerenciamento de usuários, planos, roles e assinaturas
 * Apenas administradores podem acessar
 */

require_once __DIR__ . '/config/clerk.php';
require_once __DIR__ . '/classes/ClerkAuth.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Plan.php';
require_once __DIR__ . '/classes/Database.php';

// Verificar autenticação via Clerk
$user = ClerkAuth::requireAuth();
$userModel = new User();

// Verificar se é admin
if (!$userModel->isAdmin((int)$user['id'])) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$planModel = new Plan();

// Buscar dados iniciais
$roles = $db->query("SELECT * FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$plans = $planModel->getAllActive();
$allPlans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Encarts</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/encarts/assets/css/style.css">
    
    <style>
        .admin-sidebar {
            min-height: calc(100vh - 56px);
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .admin-nav-link {
            color: #495057;
            padding: 12px 20px;
            display: block;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        .admin-nav-link:hover, .admin-nav-link.active {
            background: #e9ecef;
            color: #0d6efd;
        }
        .admin-nav-link i {
            margin-right: 10px;
        }
        .stat-card {
            border-left: 4px solid #0d6efd;
        }
        .tab-content {
            padding: 20px 0;
        }
        .plan-card {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .plan-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .plan-card.active {
            border-color: #198754;
            background: #f8fff9;
        }
        .badge-role {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        .user-avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
    
    <!-- Clerk JS -->
    <script src="https://cdn.jsdelivr.net/npm/@clerk/clerk-js@latest/dist/clerk.browser.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a href="/encarts/" class="navbar-brand">
                <i class="bi bi-layers-fill"></i> Encarts Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user['name']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a href="dashboard.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light btn-sm" id="logoutBtn">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 admin-sidebar p-3">
                <nav class="nav flex-column">
                    <a href="#dashboard-tab" class="admin-nav-link active" data-bs-toggle="tab">
                        <i class="bi bi-speedometer2"></i> Visão Geral
                    </a>
                    <a href="#users-tab" class="admin-nav-link" data-bs-toggle="tab">
                        <i class="bi bi-people"></i> Usuários
                    </a>
                    <a href="#plans-tab" class="admin-nav-link" data-bs-toggle="tab">
                        <i class="bi bi-credit-card"></i> Planos
                    </a>
                    <a href="#roles-tab" class="admin-nav-link" data-bs-toggle="tab">
                        <i class="bi bi-shield-lock"></i> Tipos de Usuário
                    </a>
                    <a href="#assignments-tab" class="admin-nav-link" data-bs-toggle="tab">
                        <i class="bi bi-person-check"></i> Atribuir Assinatura
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="tab-content">
                    
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard-tab">
                        <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Visão Geral</h2>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="text-muted small">Total de Usuários</div>
                                        <div class="h3 mb-0" id="totalUsers">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card" style="border-left-color: #198754;">
                                    <div class="card-body">
                                        <div class="text-muted small">Usuários Ativos</div>
                                        <div class="h3 mb-0" id="activeUsers">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card" style="border-left-color: #ffc107;">
                                    <div class="card-body">
                                        <div class="text-muted small">Planos Ativos</div>
                                        <div class="h3 mb-0" id="totalPlans">-</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card" style="border-left-color: #dc3545;">
                                    <div class="card-body">
                                        <div class="text-muted small">Tipos de Usuário</div>
                                        <div class="h3 mb-0" id="totalRoles">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Usuários por Plano</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="planStatsChart"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimos Usuários</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="recentUsers"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users Tab -->
                    <div class="tab-pane fade" id="users-tab">
                        <h2 class="mb-4"><i class="bi bi-people"></i> Gerenciar Usuários</h2>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Usuário</th>
                                                <th>Email</th>
                                                <th>Tipo</th>
                                                <th>Plano</th>
                                                <th>Status</th>
                                                <th>Encarts</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <tr>
                                                <td colspan="7" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Carregando...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plans Tab -->
                    <div class="tab-pane fade" id="plans-tab">
                        <h2 class="mb-4"><i class="bi bi-credit-card"></i> Gerenciar Planos</h2>
                        
                        <div class="mb-3">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPlanModal">
                                <i class="bi bi-plus-lg"></i> Novo Plano
                            </button>
                        </div>

                        <div class="row g-4" id="plansContainer">
                            <!-- Preenchido via JavaScript -->
                        </div>
                    </div>

                    <!-- Roles Tab -->
                    <div class="tab-pane fade" id="roles-tab">
                        <h2 class="mb-4"><i class="bi bi-shield-lock"></i> Tipos de Usuário (Roles)</h2>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Descrição</th>
                                                <th>Usuários</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="rolesTableBody">
                                            <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><?php echo $role['id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($role['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($role['description'] ?? '-'); ?></td>
                                                <td>
                                                    <?php
                                                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = :role_id");
                                                    $stmt->execute(['role_id' => $role['id']]);
                                                    $count = $stmt->fetch()['count'];
                                                    echo $count;
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editRole(<?php echo $role['id']; ?>)">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignments Tab -->
                    <div class="tab-pane fade" id="assignments-tab">
                        <h2 class="mb-4"><i class="bi bi-person-check"></i> Atribuir Assinatura a Usuário</h2>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Selecionar Usuário</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="assignUserSelect" class="form-label">Buscar Usuário</label>
                                            <input type="text" class="form-control" id="assignUserSearch" placeholder="Digite nome ou email...">
                                        </div>
                                        <select class="form-select" id="assignUserSelect" size="10">
                                            <option value="">Selecione um usuário...</option>
                                        </select>
                                        <div id="selectedUserInfo" class="mt-3 d-none">
                                            <div class="alert alert-info">
                                                <strong>Usuário selecionado:</strong>
                                                <div id="selectedUserName"></div>
                                                <small class="text-muted" id="selectedUserEmail"></small>
                                                <hr>
                                                <div>Plano atual: <strong id="selectedUserPlan"></strong></div>
                                                <div>Tipo: <strong id="selectedUserRole"></strong></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Selecionar Plano</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="planSelectionContainer">
                                            <?php foreach ($allPlans as $plan): ?>
                                            <div class="form-check mb-3 plan-option" data-plan-id="<?php echo $plan['id']; ?>">
                                                <input class="form-check-input" type="radio" name="planAssignment" 
                                                     id="plan_<?php echo $plan['id']; ?>" value="<?php echo $plan['id']; ?>">
                                                <label class="form-check-label" for="plan_<?php echo $plan['id']; ?>">
                                                    <strong><?php echo htmlspecialchars($plan['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?>
                                                        | <?php echo $plan['max_encarts']; ?> encarts
                                                        | <?php echo $plan['max_uploads']; ?> uploads
                                                    </small>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="btn btn-success mt-3" id="assignPlanBtn" disabled>
                                            <i class="bi bi-check-lg"></i> Confirmar Atribuição
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal Novo Plano -->
    <div class="modal fade" id="newPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newPlanForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Plano</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (identificador)</label>
                                <input type="text" class="form-control" name="slug" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preço (R$)</label>
                                <input type="number" step="0.01" class="form-control" name="price" value="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_active">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Máximo de Encarts</label>
                                <input type="number" class="form-control" name="max_encarts" value="3">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Máximo de Uploads</label>
                                <input type="number" class="form-control" name="max_uploads" value="5">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Recursos (JSON)</label>
                                <textarea class="form-control" name="features" rows="3">{}</textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="savePlanBtn">Salvar Plano</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Plano -->
    <div class="modal fade" id="editPlanModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Plano</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPlanForm">
                        <input type="hidden" name="id" id="editPlanId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nome do Plano</label>
                                <input type="text" class="form-control" name="name" id="editPlanName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug (identificador)</label>
                                <input type="text" class="form-control" name="slug" id="editPlanSlug" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preço (R$)</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="editPlanPrice">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_active" id="editPlanIsActive">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Máximo de Encarts</label>
                                <input type="number" class="form-control" name="max_encarts" id="editPlanMaxEncarts">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Máximo de Uploads</label>
                                <input type="number" class="form-control" name="max_uploads" id="editPlanMaxUploads">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Recursos (JSON)</label>
                                <textarea class="form-control" name="features" id="editPlanFeatures" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="updatePlanBtn">Atualizar Plano</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const API_BASE = '/encarts/api/admin.php';
        let selectedUserId = null;
        let selectedPlanId = null;

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            if (typeof clerk !== 'undefined') {
                await clerk.signOut();
            }
            window.location.href = '/encarts/index.php';
        });

        // Carregar estatísticas
        async function loadDashboard() {
            try {
                // Total de usuários
                const usersRes = await fetch(`${API_BASE}?action=get_users&page=1&limit=1`);
                const usersData = await usersRes.json();
                document.getElementById('totalUsers').textContent = usersData.data?.total || 0;
                
                // Planos stats
                const plansRes = await fetch(`${API_BASE}?action=get_plan_stats`);
                const plansData = await plansRes.json();
                
                if (plansData.success && plansData.data) {
                    let totalActive = 0;
                    let html = '<ul class="list-group">';
                    plansData.data.forEach(plan => {
                        totalActive += parseInt(plan.active_users || 0);
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            ${plan.name}
                            <span class="badge bg-primary rounded-pill">${plan.total_users}</span>
                        </li>`;
                    });
                    html += '</ul>';
                    document.getElementById('planStatsChart').innerHTML = html;
                    document.getElementById('totalPlans').textContent = plansData.data.length;
                }
                
                document.getElementById('activeUsers').textContent = totalActive;
                document.getElementById('totalRoles').textContent = <?php echo count($roles); ?>;
                
                // Últimos usuários
                const recentRes = await fetch(`${API_BASE}?action=get_users&page=1&limit=5`);
                const recentData = await recentRes.json();
                if (recentData.success && recentData.data?.users) {
                    let html = '<ul class="list-group">';
                    recentData.data.users.forEach(user => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <img src="${user.avatar_url || 'https://via.placeholder.com/40'}" 
                                     class="rounded-circle me-2" width="30" height="30">
                                ${escapeHtml(user.name)}
                            </div>
                            <small class="text-muted">${formatDate(user.created_at)}</small>
                        </li>`;
                    });
                    html += '</ul>';
                    document.getElementById('recentUsers').innerHTML = html;
                }
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
            }
        }

        // Carregar usuários
        async function loadUsers() {
            try {
                const res = await fetch(`${API_BASE}?action=get_users&page=1&limit=100`);
                const data = await res.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = '';
                
                data.data.users.forEach(user => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <img src="${user.avatar_url || 'https://via.placeholder.com/40'}" 
                                 class="user-avatar-sm me-2">
                            ${escapeHtml(user.name)}
                        </td>
                        <td>${escapeHtml(user.email)}</td>
                        <td><span class="badge bg-info badge-role">${escapeHtml(user.role_name || 'user')}</span></td>
                        <td><span class="badge bg-primary badge-role">${escapeHtml(user.plan_name || 'free')}</span></td>
                        <td>
                            <span class="badge bg-${user.is_active ? 'success' : 'danger'}">
                                ${user.is_active ? 'Ativo' : 'Inativo'}
                            </span>
                        </td>
                        <td>${user.encarts_count || 0}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="changeUserRole(${user.id}, ${user.role_id})">
                                <i class="bi bi-person-gear"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="changeUserPlan(${user.id})">
                                <i class="bi bi-credit-card"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-${user.is_active ? 'warning' : 'success'}" 
                                    onclick="toggleUserStatus(${user.id}, ${user.is_active})">
                                <i class="bi bi-${user.is_active ? 'pause' : 'play'}"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (error) {
                console.error('Erro ao carregar usuários:', error);
                document.getElementById('usersTableBody').innerHTML = 
                    '<tr><td colspan="7" class="text-center text-danger">Erro ao carregar usuários</td></tr>';
            }
        }

        // Carregar planos
        async function loadPlans() {
            try {
                const res = await fetch(`${API_BASE}?action=get_plans`);
                const data = await res.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                const container = document.getElementById('plansContainer');
                container.innerHTML = '';
                
                data.data.forEach(plan => {
                    const col = document.createElement('div');
                    col.className = 'col-md-4';
                    col.innerHTML = `
                        <div class="card plan-card ${plan.is_active ? 'active' : ''}">
                            <div class="card-body">
                                <h5 class="card-title">${escapeHtml(plan.name)}</h5>
                                <p class="text-muted small">Slug: ${escapeHtml(plan.slug)}</p>
                                <h3 class="text-primary">R$ ${parseFloat(plan.price).toFixed(2).replace('.', ',')}</h3>
                                <ul class="list-unstyled mt-3 mb-4">
                                    <li><i class="bi bi-check-circle text-success"></i> ${plan.max_encarts} encarts</li>
                                    <li><i class="bi bi-check-circle text-success"></i> ${plan.max_uploads} uploads</li>
                                </ul>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="editPlan(${plan.id})">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deletePlan(${plan.id})">
                                        <i class="bi bi-trash"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(col);
                });
            } catch (error) {
                console.error('Erro ao carregar planos:', error);
            }
        }

        // Salvar novo plano
        document.getElementById('savePlanBtn').addEventListener('click', async function() {
            const form = document.getElementById('newPlanForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const res = await fetch(`${API_BASE}?action=create_plan`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...data,
                        price: parseFloat(data.price),
                        max_encarts: parseInt(data.max_encarts),
                        max_uploads: parseInt(data.max_uploads),
                        is_active: parseInt(data.is_active),
                        features: JSON.parse(data.features || '{}')
                    })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast('Plano criado com sucesso!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('newPlanModal')).hide();
                    loadPlans();
                    form.reset();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        });

        // Editar plano
        async function editPlan(id) {
            try {
                const res = await fetch(`${API_BASE}?action=get_plan&plan_id=${id}`);
                const data = await res.json();
                
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                const plan = data.data;
                document.getElementById('editPlanId').value = plan.id;
                document.getElementById('editPlanName').value = plan.name;
                document.getElementById('editPlanSlug').value = plan.slug;
                document.getElementById('editPlanPrice').value = plan.price;
                document.getElementById('editPlanIsActive').value = plan.is_active;
                document.getElementById('editPlanMaxEncarts').value = plan.max_encarts;
                document.getElementById('editPlanMaxUploads').value = plan.max_uploads;
                document.getElementById('editPlanFeatures').value = JSON.stringify(JSON.parse(plan.features || '{}'), null, 2);
                
                new bootstrap.Modal(document.getElementById('editPlanModal')).show();
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        }

        // Atualizar plano
        document.getElementById('updatePlanBtn').addEventListener('click', async function() {
            const form = document.getElementById('editPlanForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const res = await fetch(`${API_BASE}?action=update_plan_item`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: parseInt(data.id),
                        name: data.name,
                        slug: data.slug,
                        price: parseFloat(data.price),
                        max_encarts: parseInt(data.max_encarts),
                        max_uploads: parseInt(data.max_uploads),
                        is_active: parseInt(data.is_active),
                        features: JSON.parse(data.features || '{}')
                    })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast('Plano atualizado com sucesso!', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editPlanModal')).hide();
                    loadPlans();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        });

        // Excluir plano
        async function deletePlan(id) {
            if (!confirm('Tem certeza que deseja excluir este plano?')) return;
            
            try {
                const res = await fetch(`${API_BASE}?action=delete_plan`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast('Plano excluído com sucesso!', 'success');
                    loadPlans();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        }

        // Mudar role do usuário
        async function changeUserRole(userId, currentRoleId) {
            const newRoleId = prompt(`Digite o novo ID do tipo de usuário (atual: ${currentRoleId}):`);
            if (!newRoleId || newRoleId === currentRoleId) return;
            
            try {
                const res = await fetch(`${API_BASE}?action=update_role`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, role_id: parseInt(newRoleId) })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast('Tipo de usuário atualizado!', 'success');
                    loadUsers();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        }

        // Mudar plano do usuário
        async function changeUserPlan(userId) {
            const newPlanId = prompt('Digite o ID do novo plano:');
            if (!newPlanId) return;
            
            try {
                const res = await fetch(`${API_BASE}?action=update_plan`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, plan_id: parseInt(newPlanId) })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast('Plano atualizado!', 'success');
                    loadUsers();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        }

        // Toggle status do usuário
        async function toggleUserStatus(userId, isActive) {
            const action = isActive ? 'desativar' : 'ativar';
            if (!confirm(`Tem certeza que deseja ${action} este usuário?`)) return;
            
            try {
                const res = await fetch(`${API_BASE}?action=deactivate_user`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast(`Usuário ${isActive ? 'desativado' : 'ativado'}!`, 'success');
                    loadUsers();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        }

        // Busca de usuários para atribuição
        document.getElementById('assignUserSearch').addEventListener('input', debounce(async function(e) {
            const term = e.target.value.trim();
            if (term.length < 2) return;
            
            try {
                const res = await fetch(`${API_BASE}?action=get_users&page=1&limit=50`);
                const data = await res.json();
                
                if (!data.success) return;
                
                const select = document.getElementById('assignUserSelect');
                select.innerHTML = '<option value="">Selecione um usuário...</option>';
                
                const filtered = data.data.users.filter(u => 
                    u.name.toLowerCase().includes(term.toLowerCase()) ||
                    u.email.toLowerCase().includes(term.toLowerCase())
                );
                
                filtered.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.email})`;
                    option.dataset.plan = user.plan_name || 'free';
                    option.dataset.role = user.role_name || 'user';
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Erro na busca:', error);
            }
        }, 300));

        // Selecionar usuário
        document.getElementById('assignUserSelect').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('selectedUserInfo');
            
            if (this.value) {
                selectedUserId = parseInt(this.value);
                document.getElementById('selectedUserName').textContent = option.text.split('(')[0].trim();
                document.getElementById('selectedUserEmail').textContent = option.text.match(/\(([^)]+)\)/)?.[1] || '';
                document.getElementById('selectedUserPlan').textContent = option.dataset.plan;
                document.getElementById('selectedUserRole').textContent = option.dataset.role;
                infoDiv.classList.remove('d-none');
            } else {
                selectedUserId = null;
                infoDiv.classList.add('d-none');
            }
            
            updateAssignButton();
        });

        // Selecionar plano
        document.querySelectorAll('input[name="planAssignment"]').forEach(radio => {
            radio.addEventListener('change', function() {
                selectedPlanId = parseInt(this.value);
                updateAssignButton();
            });
        });

        function updateAssignButton() {
            const btn = document.getElementById('assignPlanBtn');
            btn.disabled = !(selectedUserId && selectedPlanId);
        }

        // Atribuir plano
        document.getElementById('assignPlanBtn').addEventListener('click', async function() {
            if (!selectedUserId || !selectedPlanId) {
                showToast('Selecione usuário e plano', 'warning');
                return;
            }
            
            try {
                const res = await fetch(`${API_BASE}?action=update_plan`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: selectedUserId, plan_id: selectedPlanId })
                });
                
                const result = await res.json();
                
                if (result.success) {
                    showToast('Assinatura atribuída com sucesso!', 'success');
                    // Reset
                    selectedUserId = null;
                    selectedPlanId = null;
                    document.getElementById('assignUserSelect').value = '';
                    document.getElementById('selectedUserInfo').classList.add('d-none');
                    document.querySelectorAll('input[name="planAssignment"]').forEach(r => r.checked = false);
                    updateAssignButton();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                showToast('Erro: ' + error.message, 'danger');
            }
        });

        // Utilitários
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

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

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            loadUsers();
            loadPlans();
        });
    </script>
</body>
</html>
