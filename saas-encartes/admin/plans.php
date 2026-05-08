<?php
/**
 * EncartePro - Admin: Gerenciar Planos
 */

require_once __DIR__ . '/../includes/config.php';
$requireLogin = true;
$requireAdmin = true;
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Processa ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $billing_cycle = $_POST['billing_cycle'];
                $max_encartes = (int)$_POST['max_encartes'];
                $status = $_POST['status'];
                
                if ($_POST['action'] === 'create') {
                    $stmt = $db->prepare("INSERT INTO plans (name, description, price, billing_cycle, max_encartes, status) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $billing_cycle, $max_encartes, $status]);
                } else {
                    $stmt = $db->prepare("UPDATE plans SET name=?, description=?, price=?, billing_cycle=?, max_encartes=?, status=? WHERE id=?");
                    $stmt->execute([$name, $description, $price, $billing_cycle, $max_encartes, $status, $_POST['id']]);
                }
                setFlashMessage('success', 'Plano salvo!');
                break;
            case 'delete':
                $stmt = $db->prepare("DELETE FROM plans WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                setFlashMessage('success', 'Plano excluído!');
                break;
        }
    }
}

$stmt = $db->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>

<style>
.table-container { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; margin-bottom: 2rem; }
th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
th { background: var(--light-color); font-weight: 600; }
.badge { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.85rem; }
.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #f8d7da; color: #721c24; }
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; }
.modal.active { display: flex; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 2rem; border-radius: 10px; max-width: 500px; width: 90%; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; }
</style>

<h1 style="font-family: var(--font-heading); margin-bottom: 1.5rem;">📦 Gerenciar Planos</h1>

<button class="btn btn-primary" onclick="openModal()">+ Novo Plano</button>

<div class="table-container mt-3">
    <table>
        <thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Ciclo</th><th>Max Encartes</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($plans as $plan): ?>
            <tr>
                <td><?php echo $plan['id']; ?></td>
                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                <td><?php echo formatCurrency($plan['price']); ?></td>
                <td><?php echo ucfirst($plan['billing_cycle']); ?></td>
                <td><?php echo $plan['max_encartes'] > 0 ? $plan['max_encartes'] : 'Ilimitado'; ?></td>
                <td><span class="badge badge-<?php echo $plan['status']; ?>"><?php echo ucfirst($plan['status']); ?></span></td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick='editPlan(<?php echo json_encode($plan); ?>)'>Editar</button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir plano?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="planModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Novo Plano</h2>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="planId">
            
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="name" id="planName" required>
            </div>
            <div class="form-group">
                <label>Descrição</label>
                <textarea name="description" id="planDescription" rows="3"></textarea>
            </div>
            <div class="form-row" style="display:grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Preço (R$)</label>
                    <input type="number" step="0.01" name="price" id="planPrice" required>
                </div>
                <div class="form-group">
                    <label>Ciclo de Cobrança</label>
                    <select name="billing_cycle" id="planBillingCycle">
                        <option value="monthly">Mensal</option>
                        <option value="yearly">Anual</option>
                        <option value="lifetime">Vitalício</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Máximo de Encartes (0 = ilimitado)</label>
                <input type="number" name="max_encartes" id="planMaxEncartes" value="0">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="planStatus">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar</button>
                <button type="button" class="btn btn-outline" onclick="closeModal()" style="flex: 1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('planModal').classList.add('active');
}
function closeModal() {
    document.getElementById('planModal').classList.remove('active');
    document.getElementById('formAction').value = 'create';
    document.getElementById('planId').value = '';
}
function editPlan(plan) {
    document.getElementById('modalTitle').textContent = 'Editar Plano';
    document.getElementById('formAction').value = 'update';
    document.getElementById('planId').value = plan.id;
    document.getElementById('planName').value = plan.name;
    document.getElementById('planDescription').value = plan.description || '';
    document.getElementById('planPrice').value = plan.price;
    document.getElementById('planBillingCycle').value = plan.billing_cycle;
    document.getElementById('planMaxEncartes').value = plan.max_encartes;
    document.getElementById('planStatus').value = plan.status;
    openModal();
}
</script>

<p><a href="index.php" class="btn btn-outline">← Voltar ao Admin</a></p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
