<?php
/**
 * EncartePro - Configurações do Sistema (Admin)
 * Página com 5 abas para gerenciamento completo das configurações
 */

require_once '../includes/config.php';

// Verifica se é admin
if (!isAdmin()) {
    redirect('/auth/login.php');
}

$pdo = getDB();
$success = null;
$error = null;

// Processa salvamento via AJAX ou formulário tradicional
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'identity':
                // Identidade Visual
                updateSetting('site_name', sanitize($_POST['site_name']));
                updateSetting('site_description', sanitize($_POST['site_description']));
                
                // Upload de logo
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['logo'], ROOT_PATH . '/uploads/logos', ['image/jpeg', 'image/png', 'image/gif'], 2097152);
                    if ($uploadResult['success']) {
                        // Remove logo antiga
                        $oldLogo = getSetting('site_logo');
                        if ($oldLogo && file_exists(ROOT_PATH . '/uploads/logos/' . $oldLogo)) {
                            unlink(ROOT_PATH . '/uploads/logos/' . $oldLogo);
                        }
                        updateSetting('site_logo', $uploadResult['filename']);
                    }
                }
                
                // Upload de favicon
                if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadFile($_FILES['favicon'], ROOT_PATH . '/uploads/logos', ['image/x-icon', 'image/png', 'image/svg+xml'], 524288);
                    if ($uploadResult['success']) {
                        $oldFavicon = getSetting('site_favicon');
                        if ($oldFavicon && file_exists(ROOT_PATH . '/uploads/logos/' . $oldFavicon)) {
                            unlink(ROOT_PATH . '/uploads/logos/' . $oldFavicon);
                        }
                        updateSetting('site_favicon', $uploadResult['filename']);
                    }
                }
                
                updateSetting('primary_color', sanitize($_POST['primary_color']));
                updateSetting('secondary_color', sanitize($_POST['secondary_color']));
                updateSetting('default_theme', sanitize($_POST['default_theme']));
                
                // Limpa cache
                refreshSettings();
                
                echo json_encode(['success' => true, 'message' => 'Identidade visual atualizada com sucesso!']);
                break;
                
            case 'smtp':
                // Configurações SMTP
                updateSetting('smtp_host', sanitize($_POST['smtp_host']));
                updateSetting('smtp_port', sanitize($_POST['smtp_port']));
                updateSetting('smtp_user', sanitize($_POST['smtp_user']));
                
                if (!empty($_POST['smtp_pass'])) {
                    updateSetting('smtp_pass', $_POST['smtp_pass']);
                }
                
                updateSetting('smtp_from', sanitize($_POST['smtp_from']));
                updateSetting('smtp_from_name', sanitize($_POST['smtp_from_name']));
                
                // Notificações
                updateSetting('admin_notify_new_user', isset($_POST['admin_notify_new_user']) ? '1' : '0');
                updateSetting('admin_notify_new_payment', isset($_POST['admin_notify_new_payment']) ? '1' : '0');
                updateSetting('notify_expiry_days', (int) $_POST['notify_expiry_days']);
                
                refreshSettings();
                
                echo json_encode(['success' => true, 'message' => 'Configurações de e-mail salvas com sucesso!']);
                break;
                
            case 'test_smtp':
                // Teste SMTP
                require_once ROOT_PATH . '/includes/mail.php';
                
                $adminEmail = $_SESSION['user_email'];
                $result = sendMail(
                    $adminEmail,
                    $_SESSION['user_name'],
                    'Teste de SMTP - EncartePro',
                    ROOT_PATH . '/emails/welcome.php',
                    ['name' => $_SESSION['user_name'], 'verifyUrl' => SITE_URL]
                );
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => '✓ E-mail de teste enviado com sucesso!']);
                } else {
                    echo json_encode(['success' => false, 'message' => '✗ Falha ao enviar e-mail. Verifique as configurações.']);
                }
                break;
                
            case 'mercadopago':
                // Mercado Pago
                updateSetting('mp_access_token', sanitize($_POST['mp_access_token']));
                updateSetting('mp_public_key', sanitize($_POST['mp_public_key']));
                updateSetting('mp_environment', sanitize($_POST['mp_environment']));
                
                refreshSettings();
                
                echo json_encode(['success' => true, 'message' => 'Configurações do Mercado Pago salvas!']);
                break;
                
            case 'test_mp':
                // Teste conexão MP
                require_once ROOT_PATH . '/includes/mercadopago.php';
                
                $accessToken = getSetting('mp_access_token');
                if (empty($accessToken)) {
                    echo json_encode(['success' => false, 'message' => '✗ Access Token não configurado']);
                    break;
                }
                
                $result = testMPConnection();
                if ($result) {
                    echo json_encode(['success' => true, 'message' => '✓ Conectado ao Mercado Pago!']);
                } else {
                    echo json_encode(['success' => false, 'message' => '✗ Falha na conexão. Verifique o Access Token.']);
                }
                break;
                
            case 'access':
                // Acesso e Segurança
                updateSetting('allow_register', isset($_POST['allow_register']) ? '1' : '0');
                updateSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
                updateSetting('terms_url', sanitize($_POST['terms_url']));
                updateSetting('privacy_url', sanitize($_POST['privacy_url']));
                updateSetting('support_email', sanitize($_POST['support_email']));
                
                refreshSettings();
                
                echo json_encode(['success' => true, 'message' => 'Configurações de acesso salvas!']);
                break;
                
            case 'system':
                // Sistema - limpar cache
                if (isset($_POST['clear_cache'])) {
                    session_destroy();
                    session_start();
                    refreshSettings();
                    echo json_encode(['success' => true, 'message' => 'Cache limpo com sucesso!']);
                }
                
                // Exportar configurações
                if (isset($_POST['export_settings'])) {
                    $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
                    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="encartepro-settings-' . date('Y-m-d') . '.json"');
                    echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    exit;
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação inválida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Carrega configurações atuais
$settings = [];
$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}

// Informações do sistema
$phpVersion = phpversion();
$dbVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$extensions = ['PDO', 'JSON', 'cURL', 'GD', 'mbstring'];
$extStatus = [];
foreach ($extensions as $ext) {
    $extStatus[$ext] = extension_loaded(strtolower($ext));
}

include '../includes/header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>⚙️ Configurações do Sistema</h1>
        <p>Gerencie todas as configurações do EncartePro em um só lugar</p>
    </div>
    
    <!-- Tabs Navigation -->
    <div class="tabs-nav">
        <button class="tab-btn active" data-tab="identity">🎨 Identidade Visual</button>
        <button class="tab-btn" data-tab="smtp">📧 E-mail / SMTP</button>
        <button class="tab-btn" data-tab="mercadopago">💳 Mercado Pago</button>
        <button class="tab-btn" data-tab="access">🔐 Acesso e Segurança</button>
        <button class="tab-btn" data-tab="system">🖥️ Sistema</button>
    </div>
    
    <!-- Tab Content -->
    <div class="tabs-content">
        <!-- Identidade Visual -->
        <div class="tab-pane active" id="identity">
            <form id="identityForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="identity">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome do Sistema *</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'EncartePro') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição Curta</label>
                        <input type="text" name="site_description" value="<?= htmlspecialchars($settings['site_description'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Logo do Site</label>
                        <div class="file-upload-preview">
                            <?php if (!empty($settings['site_logo'])): ?>
                                <img src="/uploads/logos/<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo atual" class="preview-img">
                            <?php else: ?>
                                <div class="preview-placeholder">Nenhuma logo</div>
                            <?php endif; ?>
                            <input type="file" name="logo" accept="image/*" onchange="previewImage(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Favicon</label>
                        <div class="file-upload-preview">
                            <?php if (!empty($settings['site_favicon'])): ?>
                                <img src="/uploads/logos/<?= htmlspecialchars($settings['site_favicon']) ?>" alt="Favicon atual" class="preview-img" style="width: 32px; height: 32px;">
                            <?php else: ?>
                                <div class="preview-placeholder">Nenhum favicon</div>
                            <?php endif; ?>
                            <input type="file" name="favicon" accept="image/*,.ico,.svg">
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Cor Primária</label>
                        <div class="color-picker-group">
                            <input type="color" name="primary_color" value="<?= htmlspecialchars($settings['primary_color'] ?? '#e8401c') ?>" id="primaryColorPicker">
                            <input type="text" name="primary_color_hex" value="<?= htmlspecialchars($settings['primary_color'] ?? '#e8401c') ?>" class="hex-input" oninput="syncColor('primary', this.value)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cor Secundária</label>
                        <div class="color-picker-group">
                            <input type="color" name="secondary_color" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#f5a623') ?>" id="secondaryColorPicker">
                            <input type="text" name="secondary_color_hex" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#f5a623') ?>" class="hex-input" oninput="syncColor('secondary', this.value)">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tema Padrão</label>
                    <div class="toggle-group">
                        <label class="toggle-option">
                            <input type="radio" name="default_theme" value="light" <?= ($settings['default_theme'] ?? 'light') === 'light' ? 'checked' : '' ?>>
                            <span>☀️ Claro</span>
                        </label>
                        <label class="toggle-option">
                            <input type="radio" name="default_theme" value="dark" <?= ($settings['default_theme'] ?? 'light') === 'dark' ? 'checked' : '' ?>>
                            <span>🌙 Escuro</span>
                        </label>
                    </div>
                </div>
                
                <!-- Preview ao vivo -->
                <div class="live-preview">
                    <h4>Preview ao Vivo</h4>
                    <div class="preview-header" id="previewHeader">
                        <div class="preview-logo"></div>
                        <span class="preview-site-name"><?= htmlspecialchars($settings['site_name'] ?? 'EncartePro') ?></span>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Salvar Alterações</button>
            </form>
        </div>
        
        <!-- SMTP -->
        <div class="tab-pane" id="smtp">
            <form id="smtpForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="smtp">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Host SMTP</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" placeholder="smtp.exemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Porta</label>
                        <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" placeholder="587">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Usuário</label>
                        <input type="email" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" placeholder="noreply@exemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Senha</label>
                        <div class="password-toggle-group">
                            <input type="password" name="smtp_pass" id="smtpPass" value="">
                            <button type="button" class="btn-toggle-password" onclick="togglePassword('smtpPass')">👁️</button>
                        </div>
                        <small>Deixe em branco para manter a atual</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail Remetente</label>
                        <input type="email" name="smtp_from" value="<?= htmlspecialchars($settings['smtp_from'] ?? '') ?>" placeholder="noreply@exemplo.com">
                    </div>
                    <div class="form-group">
                        <label>Nome Remetente</label>
                        <input type="text" name="smtp_from_name" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? 'EncartePro') ?>" placeholder="EncartePro">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Notificações Automáticas</h3>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="admin_notify_new_user" <?= ($settings['admin_notify_new_user'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <span>Notificar admin sobre novos cadastros</span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="admin_notify_new_payment" <?= ($settings['admin_notify_new_payment'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <span>Notificar admin sobre novos pagamentos</span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="notify_users_expiry" <?= true ? 'checked' : '' ?> disabled>
                            <span>Notificar usuários sobre assinatura a vencer</span>
                        </label>
                        
                        <div class="form-inline">
                            <label>Dias antes:</label>
                            <input type="number" name="notify_expiry_days" value="<?= htmlspecialchars($settings['notify_expiry_days'] ?? '7') ?>" min="1" max="30" style="width: 80px;">
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="testSMTP()">🧪 Testar SMTP</button>
                    <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
                </div>
                
                <div id="smtpTestResult" class="test-result"></div>
            </form>
            
            <!-- Templates info -->
            <div class="info-box">
                <h4>📬 Templates de E-mail Disponíveis</h4>
                <ul>
                    <li>Boas-vindas ao usuário (welcome.php)</li>
                    <li>Redefinição de senha (reset-password.php)</li>
                    <li>Assinatura confirmada (subscription-confirmed.php)</li>
                    <li>Assinatura a vencer (subscription-expiring.php)</li>
                    <li>Novo cadastro para admin (new-user-admin.php)</li>
                </ul>
            </div>
        </div>
        
        <!-- Mercado Pago -->
        <div class="tab-pane" id="mercadopago">
            <form id="mpForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="mercadopago">
                
                <div class="form-group">
                    <label>Access Token</label>
                    <div class="password-toggle-group">
                        <input type="password" name="mp_access_token" id="mpToken" value="<?= htmlspecialchars($settings['mp_access_token'] ?? '') ?>" placeholder="APP_USR-...">
                        <button type="button" class="btn-toggle-password" onclick="togglePassword('mpToken')">👁️</button>
                    </div>
                    <small>Obtenha em <a href="https://www.mercadopago.com.br/developers" target="_blank">Mercado Pago Developers</a></small>
                </div>
                
                <div class="form-group">
                    <label>Chave Pública</label>
                    <input type="text" name="mp_public_key" value="<?= htmlspecialchars($settings['mp_public_key'] ?? '') ?>" placeholder="APP_USR-xxxx-xxxx">
                </div>
                
                <div class="form-group">
                    <label>Ambiente</label>
                    <div class="toggle-group">
                        <label class="toggle-option">
                            <input type="radio" name="mp_environment" value="sandbox" <?= ($settings['mp_environment'] ?? 'sandbox') === 'sandbox' ? 'checked' : '' ?>>
                            <span>🧪 Sandbox (Testes)</span>
                        </label>
                        <label class="toggle-option">
                            <input type="radio" name="mp_environment" value="production" <?= ($settings['mp_environment'] ?? 'sandbox') === 'production' ? 'checked' : '' ?>>
                            <span>🚀 Produção</span>
                        </label>
                    </div>
                    <?php if (($settings['mp_environment'] ?? 'sandbox') === 'sandbox'): ?>
                        <div class="warning-box">⚠️ Ambiente Sandbox ativo - transações são fictícias</div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="testMP()">🧪 Testar Conexão</button>
                    <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
                </div>
                
                <div id="mpTestResult" class="test-result"></div>
            </form>
        </div>
        
        <!-- Acesso e Segurança -->
        <div class="tab-pane" id="access">
            <form id="accessForm">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="access">
                
                <div class="form-section">
                    <h3>Controle de Acesso</h3>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="allow_register" <?= ($settings['allow_register'] ?? '1') === '1' ? 'checked' : '' ?>>
                            <span>Permitir novos cadastros públicos</span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="maintenance_mode" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                            <span>Modo manutenção (somente admins acessam)</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>URL dos Termos de Uso</label>
                        <input type="url" name="terms_url" value="<?= htmlspecialchars($settings['terms_url'] ?? '') ?>" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label>URL da Política de Privacidade</label>
                        <input type="url" name="privacy_url" value="<?= htmlspecialchars($settings['privacy_url'] ?? '') ?>" placeholder="https://...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>E-mail de Suporte</label>
                    <input type="email" name="support_email" value="<?= htmlspecialchars($settings['support_email'] ?? '') ?>" placeholder="suporte@exemplo.com">
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Salvar Configurações</button>
            </form>
        </div>
        
        <!-- Sistema -->
        <div class="tab-pane" id="system">
            <div class="system-info">
                <h3>Informações do Sistema</h3>
                
                <table class="info-table">
                    <tr>
                        <td>Versão do PHP</td>
                        <td><code><?= $phpVersion ?></code></td>
                    </tr>
                    <tr>
                        <td>Versão do MySQL</td>
                        <td><code><?= $dbVersion ?></code></td>
                    </tr>
                    <tr>
                        <td>Upload Máximo</td>
                        <td><code><?= $uploadMax ?> (post: <?= $postMax ?>)</code></td>
                    </tr>
                    <tr>
                        <td>Extensões</td>
                        <td>
                            <?php foreach ($extStatus as $ext => $loaded): ?>
                                <span class="badge <?= $loaded ? 'badge-success' : 'badge-error' ?>">
                                    <?= $ext ?>: <?= $loaded ? '✓' : '✗' ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-section">
                    <h3>Ações</h3>
                    
                    <div class="form-actions">
                        <button type="submit" name="clear_cache" class="btn btn-warning">🗑️ Limpar Cache</button>
                        <button type="submit" name="export_settings" class="btn btn-secondary">📥 Exportar Configurações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.admin-header {
    margin-bottom: 30px;
}

.admin-header h1 {
    font-size: 28px;
    color: var(--text);
    margin-bottom: 8px;
}

.admin-header p {
    color: var(--text-muted);
}

.tabs-nav {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid var(--border);
    margin-bottom: 24px;
    overflow-x: auto;
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
    white-space: nowrap;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: var(--primary);
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

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="url"],
.form-group input[type="number"],
.form-group input[type="password"] {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    color: var(--text);
    font-size: 14px;
}

.file-upload-preview {
    border: 2px dashed var(--border);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.preview-img {
    max-width: 150px;
    max-height: 80px;
    margin-bottom: 10px;
}

.preview-placeholder {
    color: var(--text-muted);
    padding: 20px;
}

.color-picker-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.color-picker-group input[type="color"] {
    width: 50px;
    height: 40px;
    border: 1px solid var(--border);
    border-radius: 6px;
    cursor: pointer;
}

.hex-input {
    flex: 1;
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

.password-toggle-group {
    display: flex;
    gap: 8px;
}

.btn-toggle-password {
    padding: 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
}

.form-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: var(--text);
}

.form-inline {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.live-preview {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin: 20px 0;
}

.preview-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    border-radius: 8px;
    color: white;
}

.preview-logo {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
}

.info-box {
    background: #f0fdf4;
    border: 1px solid #40916c;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.info-box h4 {
    margin-top: 0;
    color: #2d6a4f;
}

.info-box ul {
    margin: 10px 0 0 20px;
    color: #6b6b6b;
}

.warning-box {
    background: #fff8f5;
    border-left: 4px solid #e8401c;
    padding: 12px 16px;
    border-radius: 6px;
    margin-top: 10px;
    color: #e8401c;
    font-weight: 600;
}

.test-result {
    margin-top: 16px;
    padding: 12px 16px;
    border-radius: 8px;
    display: none;
}

.test-result.success {
    display: block;
    background: #f0fdf4;
    color: #2d6a4f;
    border: 1px solid #40916c;
}

.test-result.error {
    display: block;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #ef4444;
}

.system-info {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 12px;
    border-bottom: 1px solid var(--border);
}

.info-table tr:last-child td {
    border-bottom: none;
}

.info-table td:first-child {
    font-weight: 600;
    color: var(--text-muted);
    width: 200px;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 8px;
}

.badge-success {
    background: #f0fdf4;
    color: #2d6a4f;
}

.badge-error {
    background: #fef2f2;
    color: #dc2626;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.btn-secondary {
    background: var(--surface);
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}
</style>

<script>
// Tabs functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById(tabId).classList.add('active');
    });
});

// Preview image
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const container = input.parentElement;
            const existingImg = container.querySelector('.preview-img');
            if (existingImg) {
                existingImg.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-img';
                container.insertBefore(img, input);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Sync color picker with hex input
function syncColor(type, value) {
    const picker = document.getElementById(type + 'ColorPicker');
    if (picker) {
        picker.value = value;
    }
    updatePreview();
}

// Update live preview
function updatePreview() {
    const primary = document.querySelector('input[name="primary_color"]').value;
    const secondary = document.querySelector('input[name="secondary_color"]').value;
    const siteName = document.querySelector('input[name="site_name"]').value;
    
    const previewHeader = document.getElementById('previewHeader');
    if (previewHeader) {
        previewHeader.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
    }
    
    const previewName = previewHeader.querySelector('.preview-site-name');
    if (previewName) {
        previewName.textContent = siteName || 'EncartePro';
    }
}

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type === 'password';
    }
}

// Test SMTP
async function testSMTP() {
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    formData.append('action', 'test_smtp');
    
    const resultDiv = document.getElementById('smtpTestResult');
    resultDiv.className = 'test-result';
    resultDiv.textContent = 'Enviando...';
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        resultDiv.textContent = data.message;
        resultDiv.className = 'test-result ' + (data.success ? 'success' : 'error');
    } catch (error) {
        resultDiv.textContent = 'Erro: ' + error.message;
        resultDiv.className = 'test-result error';
    }
}

// Test Mercado Pago
async function testMP() {
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    formData.append('action', 'test_mp');
    
    const resultDiv = document.getElementById('mpTestResult');
    resultDiv.className = 'test-result';
    resultDiv.textContent = 'Testando...';
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        resultDiv.textContent = data.message;
        resultDiv.className = 'test-result ' + (data.success ? 'success' : 'error');
    } catch (error) {
        resultDiv.textContent = 'Erro: ' + error.message;
        resultDiv.className = 'test-result error';
    }
}

// Form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        if (this.querySelector('input[name="export_settings"]')) {
            return; // Deixa exportação funcionar normalmente
        }
        
        e.preventDefault();
        
        const formData = new FormData(this);
        
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
    });
});

// Toast notification
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        background: ${type === 'success' ? '#f0fdf4' : '#fef2f2'};
        color: ${type === 'success' ? '#2d6a4f' : '#dc2626'};
        border: 1px solid ${type === 'success' ? '#40916c' : '#ef4444'};
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add animations
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

// Initialize
updatePreview();
</script>

<?php include '../includes/footer.php'; ?>
