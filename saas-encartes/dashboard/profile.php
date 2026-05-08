<?php
/**
 * EncartePro - Perfil do Usuário
 * 3 abas: Dados Pessoais, Segurança, Preferências
 */

require_once '../includes/config.php';

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

$pdo = getDB();
$userId = $_SESSION['user_id'];
$success = null;
$error = null;

// Busca dados do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Processa upload de avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    if (isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
        $uploadResult = uploadFile($_FILES['avatar'], ROOT_PATH . '/uploads/avatars', ['image/jpeg', 'image/png', 'image/gif'], 2097152);
        
        if ($uploadResult['success']) {
            // Remove avatar antigo
            if ($user['avatar'] && file_exists(ROOT_PATH . '/uploads/avatars/' . $user['avatar'])) {
                unlink(ROOT_PATH . '/uploads/avatars/' . $user['avatar']);
            }
            
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$uploadResult['filename'], $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Avatar atualizado!', 'filename' => $uploadResult['filename']]);
        } else {
            echo json_encode(['success' => false, 'message' => $uploadResult['error']]);
        }
        exit;
    }
}

// Processa atualização de perfil via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    try {
        switch ($_POST['action']) {
            case 'update_profile':
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, document = ?, address = ? WHERE id = ?");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['phone']),
                    sanitize($_POST['document']),
                    sanitize($_POST['address']),
                    $userId
                ]);
                $_SESSION['user_name'] = sanitize($_POST['name']);
                echo json_encode(['success' => true, 'message' => 'Perfil atualizado!']);
                break;
                
            case 'change_password':
                // Verifica senha atual
                if (!password_verify($_POST['current_password'], $user['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
                    break;
                }
                
                // Valida nova senha
                if (strlen($_POST['new_password']) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Nova senha deve ter pelo menos 6 caracteres']);
                    break;
                }
                
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
                    break;
                }
                
                $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $userId]);
                
                echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso!']);
                break;
                
            case 'update_preferences':
                $theme = sanitize($_POST['theme']);
                $notificationEmail = isset($_POST['notification_email']) ? 1 : 0;
                $notificationExpiry = isset($_POST['notification_expiry']) ? 1 : 0;
                
                $stmt = $pdo->prepare("UPDATE users SET theme = ?, notification_email = ?, notification_expiry = ? WHERE id = ?");
                $stmt->execute([$theme, $notificationEmail, $notificationExpiry, $userId]);
                
                $_SESSION['user_theme'] = $theme;
                
                echo json_encode(['success' => true, 'message' => 'Preferências atualizadas!']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Busca sessões recentes
$sessions = [];
$stmt = $pdo->prepare("SELECT * FROM user_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$sessions = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <div class="profile-avatar-section">
            <div class="avatar-preview" id="avatarPreview">
                <?php if ($user['avatar']): ?>
                    <img src="/uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <span><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <label class="btn-upload-avatar">
                📷 Alterar Foto
                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
            </label>
        </div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($user['name']) ?></h1>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <span class="badge badge-<?= $user['status'] ?>"><?= $user['status'] ?></span>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs-nav">
        <button class="tab-btn active" data-tab="personal">👤 Dados Pessoais</button>
        <button class="tab-btn" data-tab="security">🔒 Segurança</button>
        <button class="tab-btn" data-tab="preferences">⚙️ Preferências</button>
    </div>
    
    <!-- Tab Content -->
    <div class="tabs-content">
        <!-- Dados Pessoais -->
        <div class="tab-pane active" id="personal">
            <form id="profileForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome Completo *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled>
                        <small>E-mail não pode ser alterado</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="(00) 00000-0000" class="mask-phone">
                    </div>
                    <div class="form-group">
                        <label>CPF/CNPJ</label>
                        <input type="text" name="document" value="<?= htmlspecialchars($user['document'] ?? '') ?>" placeholder="000.000.000-00" class="mask-document">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Endereço</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
            </form>
        </div>
        
        <!-- Segurança -->
        <div class="tab-pane" id="security">
            <form id="passwordForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label>Senha Atual *</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nova Senha *</label>
                        <input type="password" name="new_password" id="newPassword" required minlength="6">
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    <div class="form-group">
                        <label>Confirmar Nova Senha *</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">🔐 Alterar Senha</button>
            </form>
            
            <!-- Sessões Ativas -->
            <div class="sessions-section">
                <h3>📱 Histórico de Sessões</h3>
                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>IP</th>
                            <th>Navegador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= formatDate($session['created_at'], 'd/m/Y H:i') ?></td>
                                <td><code><?= htmlspecialchars($session['ip']) ?></code></td>
                                <td><?= htmlspecialchars(substr($session['user_agent'], 0, 50)) ?>...</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Preferências -->
        <div class="tab-pane" id="preferences">
            <form id="preferencesForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="update_preferences">
                
                <div class="form-group">
                    <label>Tema do Sistema</label>
                    <div class="toggle-group">
                        <label class="toggle-option">
                            <input type="radio" name="theme" value="light" <?= $user['theme'] === 'light' ? 'checked' : '' ?>>
                            <span>☀️ Claro</span>
                        </label>
                        <label class="toggle-option">
                            <input type="radio" name="theme" value="dark" <?= $user['theme'] === 'dark' ? 'checked' : '' ?>>
                            <span>🌙 Escuro</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Notificações por E-mail</h3>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="notification_email" <?= $user['notification_email'] ? 'checked' : '' ?>>
                            <span>Receber e-mails informativos do sistema</span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="notification_expiry" <?= $user['notification_expiry'] ? 'checked' : '' ?>>
                            <span>Receber aviso de vencimento da assinatura</span>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Salvar Preferências</button>
            </form>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    display: flex;
    gap: 24px;
    align-items: center;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.profile-avatar-section {
    text-align: center;
}

.avatar-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-preview span {
    color: white;
    font-size: 40px;
    font-weight: 700;
}

.btn-upload-avatar {
    display: inline-block;
    padding: 8px 16px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-upload-avatar:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.profile-info h1 {
    margin: 0 0 8px 0;
    font-size: 24px;
    color: var(--text);
}

.profile-info p {
    margin: 0 0 12px 0;
    color: var(--text-muted);
}

.tabs-nav {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 24px;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-muted);
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    color: var(--text);
    font-size: 14px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: var(--text-muted);
    font-size: 13px;
}

.password-strength {
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
}

.password-strength::after {
    content: '';
    display: block;
    height: 100%;
    width: 0%;
    background: #ef4444;
    transition: all 0.3s;
}

.password-strength.weak::after {
    width: 33%;
    background: #ef4444;
}

.password-strength.medium::after {
    width: 66%;
    background: #f59e0b;
}

.password-strength.strong::after {
    width: 100%;
    background: #2d6a4f;
}

.toggle-group {
    display: flex;
    gap: 20px;
}

.toggle-option {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.form-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin: 24px 0;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: var(--text);
}

.sessions-section {
    margin-top: 32px;
}

.sessions-section h3 {
    margin-bottom: 16px;
    color: var(--text);
}

.sessions-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    overflow: hidden;
}

.sessions-table th,
.sessions-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.sessions-table th {
    background: var(--bg);
    font-weight: 600;
    color: var(--text-muted);
    font-size: 13px;
}

.sessions-table td {
    font-size: 14px;
}

.sessions-table tr:last-child td {
    border-bottom: none;
}

.badge-active {
    background: #f0fdf4;
    color: #2d6a4f;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-inactive,
.badge-suspended {
    background: #fef2f2;
    color: #dc2626;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}
</style>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    });
});

// Upload avatar
document.getElementById('avatarInput').addEventListener('change', async function(e) {
    if (!this.files[0]) return;
    
    const formData = new FormData();
    formData.append('action', 'upload_avatar');
    formData.append('avatar', this.files[0]);
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = `<img src="/uploads/avatars/${data.filename}" alt="Avatar">`;
            showToast('success', data.message);
        } else {
            showToast('error', data.message);
        }
    } catch (error) {
        showToast('error', 'Erro: ' + error.message);
    }
});

// Profile form
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await submitForm(this);
});

// Password form
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await submitForm(this);
});

// Preferences form
document.getElementById('preferencesForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    await submitForm(this);
});

async function submitForm(form) {
    const formData = new FormData(form);
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        showToast(data.success ? 'success' : 'error', data.message);
    } catch (error) {
        showToast('error', 'Erro: ' + error.message);
    }
}

// Password strength
document.getElementById('newPassword').addEventListener('input', function() {
    const value = this.value;
    const strengthEl = document.getElementById('passwordStrength');
    
    let strength = 0;
    if (value.length >= 6) strength++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) strength++;
    if (/\d/.test(value)) strength++;
    if (/[^A-Za-z0-9]/.test(value)) strength++;
    
    strengthEl.className = 'password-strength';
    if (strength === 1) strengthEl.classList.add('weak');
    else if (strength === 2 || strength === 3) strengthEl.classList.add('medium');
    else if (strength === 4) strengthEl.classList.add('strong');
});

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
</script>

<?php include '../includes/footer.php'; ?>
