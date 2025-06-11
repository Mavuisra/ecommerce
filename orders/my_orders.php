<?php
$page_title = "Mes Commandes - KinshaMarket";
$page_description = "Consultez l'historique de vos commandes sur KinshaMarket, votre marketplace de confiance à Kinshasa.";
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$user_id = $_SESSION['user_id'];

// Récupérer les commandes de l'utilisateur avec pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Compter le nombre total de commandes
$total_orders = fetchOne("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user_id])['count'];

// Récupérer les commandes
$orders = fetchAll("
    SELECT 
        o.*,
        COUNT(oi.id) as item_count,
        SUM(oi.quantity * oi.price) as total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
", [$user_id]);

// Pagination
$pagination = paginate($total_orders, $limit, $page);
?>

<div class="container">
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="/ecommerce/index.php">Accueil</a>
            <span>›</span>
            <span>Mes Commandes</span>
        </nav>
        
        <div class="page-title-section">
            <h1 class="page-title">📦 Mes Commandes</h1>
            <p class="page-subtitle">Suivez l'état de vos commandes et consultez votre historique d'achats</p>
        </div>
    </div>

    <div class="orders-container">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <h2>Aucune commande pour le moment</h2>
                <p>Vous n'avez pas encore passé de commande sur KinshaMarket.</p>
                <a href="/ecommerce/products/index.php" class="btn btn-primary btn-lg">
                    Découvrir nos produits
                </a>
            </div>
        <?php else: ?>
            <div class="orders-stats">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Commandes totales</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-number">
                            <?php 
                            $total_spent = fetchOne("
                                SELECT SUM(oi.quantity * oi.price) as total 
                                FROM orders o 
                                JOIN order_items oi ON o.id = oi.order_id 
                                WHERE o.user_id = ?
                            ", [$user_id])['total'] ?? 0;
                            echo formatPrice($total_spent);
                            ?>
                        </div>
                        <div class="stat-label">Total dépensé</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🚚</div>
                    <div class="stat-info">
                        <div class="stat-number">
                            <?php 
                            $delivered_count = fetchOne("
                                SELECT COUNT(*) as count 
                                FROM orders 
                                WHERE user_id = ? AND status = 'delivered'
                            ", [$user_id])['count'] ?? 0;
                            echo $delivered_count;
                            ?>
                        </div>
                        <div class="stat-label">Commandes livrées</div>
                    </div>
                </div>
            </div>

            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <h3 class="order-number">Commande #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                <p class="order-date">
                                    📅 Passée le <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            
                            <div class="order-status">
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => '⏳ En attente',
                                        'confirmed' => '✅ Confirmée',
                                        'processing' => '📦 En préparation',
                                        'shipped' => '🚚 Expédiée',
                                        'delivered' => '✅ Livrée',
                                        'cancelled' => '❌ Annulée'
                                    ];
                                    echo $status_labels[$order['status']] ?? $order['status'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-content">
                            <div class="order-summary">
                                <div class="summary-item">
                                    <span class="label">Articles :</span>
                                    <span class="value"><?php echo $order['item_count']; ?> produit(s)</span>
                                </div>
                                
                                <div class="summary-item">
                                    <span class="label">Total :</span>
                                    <span class="value total-price"><?php echo formatPrice($order['total_amount']); ?></span>
                                </div>
                                
                                <div class="summary-item">
                                    <span class="label">Mode de paiement :</span>
                                    <span class="value">
                                        <?php 
                                        $payment_methods = [
                                            'orange_money' => '📱 Orange Money',
                                            'airtel_money' => '📱 Airtel Money',
                                            'cash_on_delivery' => '💵 Paiement à la livraison',
                                            'bank_transfer' => '🏦 Virement bancaire'
                                        ];
                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <a href="/ecommerce/orders/order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                    👁️ Voir détails
                                </a>
                                
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <button class="btn btn-secondary btn-sm" onclick="showReorderModal(<?php echo $order['id']; ?>)">
                                        🔄 Recommander
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                        ❌ Annuler
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'shipped'): ?>
                            <div class="order-tracking">
                                <div class="tracking-info">
                                    🚚 Votre commande est en route vers <strong><?php echo htmlspecialchars($order['shipping_address']); ?></strong>
                                </div>
                                <button class="btn btn-ghost btn-sm" onclick="trackOrder('<?php echo $order['order_number']; ?>')">
                                    📍 Suivre la livraison
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="pagination-container">
                    <nav class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?page=<?php echo $pagination['current_page'] - 1; ?>" class="pagination-btn">
                                ‹ Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <?php if ($i == $pagination['current_page']): ?>
                                <span class="pagination-btn active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" class="pagination-btn"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?php echo $pagination['current_page'] + 1; ?>" class="pagination-btn">
                                Suivant ›
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Page Header */
.page-header {
    margin-bottom: 3rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: var(--text-sm);
    color: var(--gray-600);
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
    transition: var(--transition-fast);
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.page-title-section {
    text-align: center;
    padding: 2rem 0;
}

.page-title {
    font-size: var(--text-4xl);
    font-weight: var(--font-extrabold);
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    font-size: var(--text-lg);
    color: var(--gray-600);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h2 {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--gray-900);
    margin-bottom: 1rem;
}

.empty-state p {
    color: var(--gray-600);
    margin-bottom: 2rem;
}

/* Orders Stats */
.orders-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition-normal);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-icon {
    font-size: 2.5rem;
    background: var(--gray-100);
    width: 4rem;
    height: 4rem;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-number {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--primary);
}

.stat-label {
    font-size: var(--text-sm);
    color: var(--gray-600);
}

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    overflow: hidden;
    transition: var(--transition-normal);
}

.order-card:hover {
    box-shadow: var(--shadow-md);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.order-number {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.order-date {
    font-size: var(--text-sm);
    color: var(--gray-600);
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    white-space: nowrap;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-confirmed { background: #dbeafe; color: #1e40af; }
.status-processing { background: #e0e7ff; color: #5b21b6; }
.status-shipped { background: #f3e8ff; color: #7c2d12; }
.status-delivered { background: #dcfce7; color: #166534; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

.order-content {
    padding: 1.5rem;
}

.order-summary {
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-item .label {
    font-weight: var(--font-medium);
    color: var(--gray-700);
}

.summary-item .value {
    color: var(--gray-900);
}

.total-price {
    font-weight: var(--font-bold);
    color: var(--primary);
    font-size: var(--text-lg);
}

.order-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.order-tracking {
    background: var(--gray-50);
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tracking-info {
    font-size: var(--text-sm);
    color: var(--gray-700);
}

/* Pagination */
.pagination-container {
    margin-top: 3rem;
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    gap: 0.25rem;
}

.pagination-btn {
    padding: 0.75rem 1rem;
    border: 1px solid var(--gray-300);
    background: var(--white);
    color: var(--gray-700);
    text-decoration: none;
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    transition: var(--transition-fast);
}

.pagination-btn:hover:not(.active) {
    background: var(--gray-50);
    border-color: var(--primary);
}

.pagination-btn.active {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

/* Responsive */
@media (max-width: 768px) {
    .page-title {
        font-size: var(--text-2xl);
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-actions {
        justify-content: center;
    }
    
    .order-tracking {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .orders-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function cancelOrder(orderId) {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        fetch('/ecommerce/orders/cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Commande annulée avec succès', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Erreur lors de l\'annulation', 'error');
            }
        })
        .catch(error => {
            showNotification('Erreur de connexion', 'error');
        });
    }
}

function trackOrder(orderNumber) {
    // Simuler un système de suivi
    alert(`Suivi de la commande ${orderNumber}\n\nVotre commande est actuellement en transit vers Kinshasa.\nLivraison prévue dans 1-2 jours ouvrables.\n\nPour plus d'informations, contactez-nous :\n📱 WhatsApp: +243 97 765 4321`);
}

function showReorderModal(orderId) {
    if (confirm('Voulez-vous recommander les mêmes articles ?')) {
        window.location.href = `/ecommerce/orders/reorder.php?id=${orderId}`;
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 