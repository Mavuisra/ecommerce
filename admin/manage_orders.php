<?php
$page_title = "Gestion des commandes - Administration";
require_once '../includes/header.php';

// Vérifier les droits admin
requireAdmin();

// Paramètres de filtrage et pagination
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$payment_status = sanitize($_GET['payment_status'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'created_at');
$order = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 20;

// Construction de la requête
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status;
}

if ($payment_status) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_status;
}

$where_clause = implode(' AND ', $where_conditions);

// Compter le total
$total_query = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE $where_clause";
$total_result = fetchOne($total_query, $params);
$total_items = $total_result['total'];

// Pagination
$pagination = paginate($total_items, $items_per_page, $page);

// Récupérer les commandes
$orders_query = "
    SELECT o.*, u.first_name, u.last_name, u.email, u.phone,
           COUNT(oi.id) as items_count
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE $where_clause 
    GROUP BY o.id
    ORDER BY o.$sort $order 
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
";

$orders = fetchAll($orders_query, $params);

// Traitement des actions (changer statut)
if ($_POST) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Token de sécurité invalide.', 'error');
    } else {
        $action = $_POST['action'] ?? '';
        $order_id = intval($_POST['order_id'] ?? 0);
        
        if ($order_id > 0) {
            try {
                switch ($action) {
                    case 'update_status':
                        $new_status = sanitize($_POST['new_status'] ?? '');
                        $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                        if (in_array($new_status, $valid_statuses)) {
                            executeQuery("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$new_status, $order_id]);
                            setFlashMessage('Statut de la commande mis à jour avec succès.', 'success');
                        }
                        break;
                        
                    case 'update_payment':
                        $new_payment_status = sanitize($_POST['new_payment_status'] ?? '');
                        $valid_payment_statuses = ['pending', 'paid', 'failed'];
                        if (in_array($new_payment_status, $valid_payment_statuses)) {
                            executeQuery("UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$new_payment_status, $order_id]);
                            setFlashMessage('Statut de paiement mis à jour avec succès.', 'success');
                        }
                        break;
                        
                    case 'delete_order':
                        executeQuery("DELETE FROM orders WHERE id = ?", [$order_id]);
                        setFlashMessage('Commande supprimée avec succès.', 'success');
                        break;
                }
            } catch (Exception $e) {
                setFlashMessage('Erreur : ' . $e->getMessage(), 'error');
            }
        }
        
        // Rediriger pour éviter la re-soumission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Récupérer les statistiques rapides
$stats = [
    'total_orders' => fetchOne("SELECT COUNT(*) as count FROM orders")['count'],
    'pending_orders' => fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'processing_orders' => fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")['count'],
    'total_revenue' => fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'")['total'],
];
?>

<div class="admin-page">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>🛒 Gestion des commandes</h1>
            <p style="color: var(--secondary-color);">Gérer <?php echo $total_items; ?> commande(s)</p>
        </div>
        <div>
            <a href="/ecommerce/admin/dashboard.php" class="btn btn-outline">← Tableau de bord</a>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
            <h3 style="color: white; margin: 0;"><?php echo $stats['total_orders']; ?></h3>
            <p style="opacity: 0.9; margin: 0;">Total commandes</p>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏳</div>
            <h3 style="color: white; margin: 0;"><?php echo $stats['pending_orders']; ?></h3>
            <p style="opacity: 0.9; margin: 0;">En attente</p>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div>
            <h3 style="color: white; margin: 0;"><?php echo $stats['processing_orders']; ?></h3>
            <p style="opacity: 0.9; margin: 0;">En cours</p>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
            <h3 style="color: white; margin: 0;"><?php echo formatPrice($stats['total_revenue']); ?></h3>
            <p style="opacity: 0.9; margin: 0;">Chiffre d'affaires</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-section" style="background: var(--light-color); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
        <form method="GET" class="filters-form">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           class="form-control" 
                           placeholder="N° commande, nom, email..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status" class="form-label">Statut</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>En cours</option>
                        <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                        <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Livrée</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="payment_status" class="form-label">Paiement</label>
                    <select id="payment_status" name="payment_status" class="form-control">
                        <option value="">Tous les paiements</option>
                        <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>Payé</option>
                        <option value="failed" <?php echo $payment_status === 'failed' ? 'selected' : ''; ?>>Échoué</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="sort" class="form-label">Trier par</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date</option>
                        <option value="order_number" <?php echo $sort === 'order_number' ? 'selected' : ''; ?>>N° commande</option>
                        <option value="total_amount" <?php echo $sort === 'total_amount' ? 'selected' : ''; ?>>Montant</option>
                        <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Statut</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </div>
            
            <div style="margin-top: 1rem;">
                <a href="/ecommerce/admin/manage_orders.php" class="btn btn-outline btn-sm">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Liste des commandes -->
    <?php if (empty($orders)): ?>
        <div class="no-results" style="text-align: center; padding: 4rem 0;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
            <h2>Aucune commande trouvée</h2>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                <?php echo $search ? 'Aucune commande ne correspond à votre recherche.' : 'Aucune commande n\'a été passée pour le moment.'; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="orders-table-container">
            <table class="orders-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--box-shadow);">
                <thead style="background: var(--primary-color); color: white;">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">Commande</th>
                        <th style="padding: 1rem; text-align: left;">Client</th>
                        <th style="padding: 1rem; text-align: right;">Montant</th>
                        <th style="padding: 1rem; text-align: center;">Statut</th>
                        <th style="padding: 1rem; text-align: center;">Paiement</th>
                        <th style="padding: 1rem; text-align: center;">Date</th>
                        <th style="padding: 1rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr style="border-bottom: 1px solid #e9ecef;">
                            <td style="padding: 1rem;">
                                <div>
                                    <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong><br>
                                    <small style="color: var(--secondary-color);">
                                        <?php echo $order['items_count']; ?> produit(s)
                                    </small>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <div>
                                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong><br>
                                    <small style="color: var(--secondary-color);">
                                        <?php echo htmlspecialchars($order['email']); ?>
                                        <?php if ($order['phone']): ?>
                                            <br><?php echo htmlspecialchars($order['phone']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            <td style="padding: 1rem; text-align: right; font-weight: bold;">
                                <?php echo formatPrice($order['total_amount']); ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="new_status" 
                                            onchange="this.form.submit()" 
                                            class="status-select"
                                            style="padding: 0.25rem 0.5rem; border-radius: 12px; border: none; font-size: 0.8rem; font-weight: 500;">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>⏳ En attente</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>⚙️ En cours</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>🚚 Expédiée</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>✅ Livrée</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Annulée</option>
                                    </select>
                                </form>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <span style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; font-weight: 500;
                                             background: <?php 
                                             switch($order['payment_status']) {
                                                 case 'pending': echo '#fff3cd'; break;
                                                 case 'paid': echo '#d4edda'; break;
                                                 case 'failed': echo '#f8d7da'; break;
                                                 default: echo '#e9ecef';
                                             }
                                             ?>;
                                             color: <?php 
                                             switch($order['payment_status']) {
                                                 case 'pending': echo '#856404'; break;
                                                 case 'paid': echo '#155724'; break;
                                                 case 'failed': echo '#721c24'; break;
                                                 default: echo '#495057';
                                             }
                                             ?>;">
                                    <?php 
                                    switch($order['payment_status']) {
                                        case 'pending': echo '⏳ En attente'; break;
                                        case 'paid': echo '✅ Payé'; break;
                                        case 'failed': echo '❌ Échoué'; break;
                                        default: echo $order['payment_status'];
                                    }
                                    ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <div style="font-size: 0.9rem;">
                                    <?php echo date('d/m/Y', strtotime($order['created_at'])); ?><br>
                                    <small style="color: var(--secondary-color);">
                                        <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                    </small>
                                </div>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <button onclick="viewOrder(<?php echo $order['id']; ?>)" 
                                            class="btn btn-sm btn-outline" 
                                            title="Voir détails">
                                        👁️
                                    </button>
                                    <button onclick="deleteOrder(<?php echo $order['id']; ?>, '#<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>')" 
                                            class="btn btn-sm btn-danger" 
                                            title="Supprimer">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="pagination" style="margin-top: 2rem; display: flex; justify-content: center; gap: 0.5rem;">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>" 
                       class="btn btn-outline">
                        « Précédent
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="btn btn-primary"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="btn btn-outline">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_next']): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>" 
                       class="btn btn-outline">
                        Suivant »
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Formulaire caché pour la suppression -->
<form id="delete-form" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete_order">
    <input type="hidden" name="order_id" id="delete-order-id">
</form>

<style>
.orders-table th,
.orders-table td {
    border-bottom: 1px solid #e9ecef;
}

.orders-table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: 1px solid #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

@media (max-width: 768px) {
    .filters-form > div {
        grid-template-columns: 1fr !important;
    }
    
    .page-header {
        flex-direction: column !important;
        text-align: center;
        gap: 1rem;
    }
    
    .orders-table-container {
        overflow-x: auto;
    }
    
    .orders-table {
        min-width: 1000px;
    }
    
    .stats-row {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
</style>

<script>
function deleteOrder(id, orderNumber) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la commande ${orderNumber} ?\n\nCette action est irréversible.`)) {
        document.getElementById('delete-order-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

function viewOrder(id) {
    // Pour l'instant, afficher une alerte - vous pouvez créer une page de détails plus tard
    alert('Fonctionnalité de détails de commande à venir.\nCommande ID: ' + id);
}
</script>

<?php require_once '../includes/footer.php'; ?> 