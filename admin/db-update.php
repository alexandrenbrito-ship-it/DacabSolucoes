<?php
/**
 * Admin - Atualização do Banco de Dados
 */
require_once __DIR__ . '/../config.php';
Auth::requireAdmin();

$db = Database::getInstance();
$conn = $db->getConnection();
$pageTitle = 'Atualizar Banco de Dados';
$migrationLog = [];
$error = '';

// Função para registrar migração executada
function registerMigration($conn, $migrationName) {
    $stmt = $conn->prepare("INSERT IGNORE INTO migrations (migration) VALUES (?)");
    $stmt->execute([$migrationName]);
}

// Função para verificar se migração já foi executada
function migrationExists($conn, $migrationName) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM migrations WHERE migration = ?");
    $stmt->execute([$migrationName]);
    return $stmt->fetch()['count'] > 0;
}

// Processa atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Auth::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF inválido.';
    } else {
        $migrationsDir = __DIR__ . '/../database/migrations/';
        
        if (!is_dir($migrationsDir)) {
            $error = 'Diretório de migrações não encontrado.';
        } else {
            $files = glob($migrationsDir . '*.sql');
            sort($files);
            
            foreach ($files as $file) {
                $migrationName = basename($file);
                
                if (migrationExists($conn, $migrationName)) {
                    $migrationLog[] = ['name' => $migrationName, 'status' => 'skip', 'message' => 'Já executada'];
                    continue;
                }
                
                try {
                    $sql = file_get_contents($file);
                    
                    // Executa cada statement separadamente
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $conn->exec($statement);
                        }
                    }
                    
                    registerMigration($conn, $migrationName);
                    $migrationLog[] = ['name' => $migrationName, 'status' => 'success', 'message' => 'Executada com sucesso'];
                    
                } catch (Exception $e) {
                    $migrationLog[] = ['name' => $migrationName, 'status' => 'error', 'message' => $e->getMessage()];
                }
            }
            
            if (empty($migrationLog)) {
                $migrationLog[] = ['name' => '-', 'status' => 'info', 'message' => 'Nenhuma migração pendente.'];
            }
        }
    }
}

// Lista migrações já executadas
$executedMigrations = [];
try {
    $stmt = $conn->query("SELECT migration, executed_at FROM migrations ORDER BY executed_at DESC");
    $executedMigrations = $stmt->fetchAll();
} catch (Exception $e) {
    // Tabela migrations pode não existir ainda
}

// Verifica migrações pendentes
$pendingMigrations = [];
$migrationsDir = __DIR__ . '/../database/migrations/';
if (is_dir($migrationsDir)) {
    $files = glob($migrationsDir . '*.sql');
    sort($files);
    foreach ($files as $file) {
        $migrationName = basename($file);
        if (!migrationExists($conn, $migrationName)) {
            $pendingMigrations[] = $migrationName;
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="row">
    <div class="col-12">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= sanitize($error) ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">🔄 Verificar e Executar Migrações</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Este processo verifica novas migrações na pasta <code>database/migrations/</code> 
                    e executa as que ainda não foram aplicadas ao banco de dados.
                </p>
                
                <?php if (count($pendingMigrations) > 0): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ Existem <?= count($pendingMigrations) ?> migração(ões) pendente(s):</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($pendingMigrations as $mig): ?>
                                <li><?= sanitize($mig) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="run_migrations">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Executar Migrações Pendentes
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        ✅ Todas as migrações estão atualizadas. Nenhuma ação necessária.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($migrationLog)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">📋 Log de Execução</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Migração</th>
                            <th>Status</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($migrationLog as $log): ?>
                            <tr>
                                <td><code><?= sanitize($log['name']) ?></code></td>
                                <td>
                                    <?php if ($log['status'] == 'success'): ?>
                                        <span class="badge bg-success">Sucesso</span>
                                    <?php elseif ($log['status'] == 'error'): ?>
                                        <span class="badge bg-danger">Erro</span>
                                    <?php elseif ($log['status'] == 'skip'): ?>
                                        <span class="badge bg-secondary">Ignorada</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Info</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= sanitize($log['message']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📜 Migrações Já Executadas</h5>
            </div>
            <div class="card-body">
                <?php if (count($executedMigrations) > 0): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Migração</th>
                                <th>Data de Execução</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($executedMigrations as $mig): ?>
                                <tr>
                                    <td><code><?= sanitize($mig['migration']) ?></code></td>
                                    <td><?= formatDate($mig['executed_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Nenhuma migração registrada ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
