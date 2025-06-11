<?php
$page_title = "Détails de la commande - KinshaMarket";
$page_description = "Consultez les détails de votre commande sur KinshaMarket.";
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    redirect('/ecommerce/orders/my_orders.php');
    exit;
}

// Récupérer les détails de la commande
$order = fetchOne("
    SELECT * FROM orders 
    WHERE id = ? AND user_id = ?
", [$order_id, $user_id]);

if (!$order) {
    $_SESSION['error'] = "Commande non trouvée";
    redirect('/ecommerce/orders/my_orders.php');
    exit;
}

// Récupérer les articles de la commande
$order_items = fetchAll("
    SELECT 
        oi.*,
        p.name as product_name,
        p.image as product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id
", [$order_id]);

// Calculer le total
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['quantity'] * $item['price'];
}

// Statut de livraison
$status_info = [
    'pending' => ['icon' => '⏳', 'label' => 'En attente', 'color' => 'warning'],
    'confirmed' => ['icon' => '✅', 'label' => 'Confirmée', 'color' => 'info'],
    'processing' => ['icon' => '📦', 'label' => 'En préparation', 'color' => 'primary'],
    'shipped' => ['icon' => '🚚', 'label' => 'Expédiée', 'color' => 'purple'],
    'delivered' => ['icon' => '✅', 'label' => 'Livrée', 'color' => 'success'],
    'cancelled' => ['icon' => '❌', 'label' => 'Annulée', 'color' => 'danger']
];

$current_status = $status_info[$order['status']] ?? ['icon' => '', 'label' => $order['status'], 'color' => 'secondary'];
?>

<div class="container">
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="/ecommerce/index.php">Accueil</a>
            <span>›</span>
            <a href="/ecommerce/orders/my_orders.php">Mes commandes</a>
            <span>›</span>
            <span>Commande #<?php echo htmlspecialchars($order['order_number']); ?></span>
        </nav>
        
        <div class="order-header-info">
            <div class="order-title">
                <h1>📦 Commande #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <?php echo $current_status['icon'] . ' ' . $current_status['label']; ?>
                </span>
            </div>
            <p class="order-date">
                Passée le <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
            </p>
        </div>
    </div>

    <div class="order-details-layout">
        <!-- Informations de la commande -->
        <div class="order-main-content">
            <!-- Timeline de suivi -->
            <div class="order-timeline-card">
                <h3>📍 Suivi de votre commande</h3>
                <div class="timeline">
                    <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'confirmed', 'processing', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon">📝</div>
                        <div class="timeline-content">
                            <h4>Commande passée</h4>
                            <p><?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered']) ? 'completed' : ''; ?>">
                        <div class="timeline-icon">✅</div>
                        <div class="timeline-content">
                            <h4>Commande confirmée</h4>
                            <p>Votre commande a été acceptée</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'completed' : ($order['status'] === 'processing' ? 'active' : ''); ?>">
                        <div class="timeline-icon">📦</div>
                        <div class="timeline-content">
                            <h4>En préparation</h4>
                            <p>Vos articles sont en cours de préparation</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo in_array($order['status'], ['shipped', 'delivered']) ? 'completed' : ($order['status'] === 'shipped' ? 'active' : ''); ?>">
                        <div class="timeline-icon">🚚</div>
                        <div class="timeline-content">
                            <h4>En cours de livraison</h4>
                            <p>Votre commande est en route</p>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo $order['status'] === 'delivered' ? 'completed' : ''; ?>">
                        <div class="timeline-icon">🎉</div>
                        <div class="timeline-content">
                            <h4>Livrée</h4>
                            <p>Votre commande a été livrée</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Articles commandés -->
            <div class="order-items-card">
                <h3>🛍️ Articles commandés (<?php echo count($order_items); ?>)</h3>
                <div class="order-items-list">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="/ecommerce/assets/images/products/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                            </div>
                            
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <div class="item-meta">
                                    <span class="quantity">Quantité: <?php echo $item['quantity']; ?></span>
                                    <span class="price">Prix unitaire: <?php echo formatPrice($item['price']); ?></span>
                                </div>
                            </div>
                            
                            <div class="item-total">
                                <?php echo formatPrice($item['quantity'] * $item['price']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar avec résumé -->
        <div class="order-sidebar">
            <!-- Résumé de la commande -->
            <div class="order-summary-card">
                <h3>💰 Résumé</h3>
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Sous-total:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Frais de livraison:</span>
                        <span><?php echo formatPrice($order['shipping_cost'] ?? 0); ?></span>
                    </div>
                    
                    <?php if (isset($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                        <div class="summary-row">
                            <span>Taxes:</span>
                            <span><?php echo formatPrice($order['tax_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span><?php echo formatPrice($subtotal + ($order['shipping_cost'] ?? 0) + ($order['tax_amount'] ?? 0)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Informations de livraison -->
            <div class="shipping-info-card">
                <h3>🚚 Livraison</h3>
                <div class="shipping-details">
                    <div class="info-row">
                        <strong>Adresse:</strong>
                        <p><?php echo htmlspecialchars($order['shipping_address'] ?? 'Non spécifiée'); ?></p>
                    </div>
                    
                    <div class="info-row">
                        <strong>Méthode de paiement:</strong>
                        <p>
                            <?php 
                            $payment_methods = [
                                'orange_money' => '📱 Orange Money',
                                'airtel_money' => '📱 Airtel Money',
                                'cash_on_delivery' => '💵 Paiement à la livraison',
                                'bank_transfer' => '🏦 Virement bancaire'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </p>
                    </div>
                    
                    <div class="info-row">
                        <strong>Statut du paiement:</strong>
                        <p>
                            <span class="payment-status <?php echo $order['payment_status'] ?? 'pending'; ?>">
                                <?php 
                                $payment_statuses = [
                                    'pending' => '⏳ En attente',
                                    'paid' => '✅ Payé',
                                    'failed' => '❌ Échoué'
                                ];
                                echo $payment_statuses[$order['payment_status'] ?? 'pending'] ?? 'Non défini';
                                ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="order-actions-card">
                <h3>⚡ Actions</h3>
                <div class="action-buttons">
                    <?php if ($order['status'] === 'delivered'): ?>
                        <button class="btn btn-primary btn-block" onclick="reorderItems()">
                            🔄 Recommander ces articles
                        </button>
                    <?php endif; ?>
                    
                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                        <button class="btn btn-danger btn-block" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            ❌ Annuler la commande
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline btn-block" onclick="downloadInvoice()">
                        📄 Télécharger la facture
                    </button>
                    
                    <a href="/ecommerce/orders/my_orders.php" class="btn btn-secondary btn-block">
                        ← Retour à mes commandes
                    </a>
                </div>
            </div>

            <!-- Contact support -->
            <div class="support-card">
                <h3>🆘 Besoin d'aide ?</h3>
                <p>Notre équipe est là pour vous aider</p>
                <div class="support-options">
                    <a href="https://wa.me/243977654321" class="support-btn" target="_blank">
                        📱 WhatsApp
                    </a>
                    <a href="mailto:support@kinshamarket.cd" class="support-btn">
                        ✉️ Email
                    </a>
                    <a href="tel:+243977654321" class="support-btn">
                        📞 Appel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.order-header-info {
    margin-bottom: 2rem;
}

.order-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.order-title h1 {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--gray-900);
    margin: 0;
}

.order-date {
    color: var(--gray-600);
    font-size: var(--text-sm);
}

.order-details-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

@media (max-width: 1024px) {
    .order-details-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

/* Cards */
.order-timeline-card,
.order-items-card,
.order-summary-card,
.shipping-info-card,
.order-actions-card,
.support-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    margin-bottom: 1.5rem;
}

.order-timeline-card h3,
.order-items-card h3,
.order-summary-card h3,
.shipping-info-card h3,
.order-actions-card h3,
.support-card h3 {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--gray-900);
    margin-bottom: 1rem;
}

/* Timeline */
.timeline {
    position: relative;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 1rem;
    bottom: 1rem;
    width: 2px;
    background: var(--gray-200);
}

.timeline-item {
    position: relative;
    padding-left: 3rem;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 2rem;
    height: 2rem;
    background: var(--gray-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    border: 2px solid var(--gray-200);
}

.timeline-item.completed .timeline-icon {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

.timeline-item.active .timeline-icon {
    background: var(--white);
    border-color: var(--primary);
    animation: pulse 2s infinite;
}

.timeline-item.completed::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 2rem;
    width: 2px;
    height: calc(100% - 1rem);
    background: var(--primary);
}

.timeline-content h4 {
    font-weight: var(--font-medium);
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.timeline-content p {
    font-size: var(--text-sm);
    color: var(--gray-600);
}

/* Order Items */
.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--radius-md);
}

.item-image {
    width: 4rem;
    height: 4rem;
    flex-shrink: 0;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: var(--radius-md);
}

.item-details {
    flex: 1;
}

.item-details h4 {
    font-size: var(--text-base);
    font-weight: var(--font-medium);
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.item-meta {
    display: flex;
    gap: 1rem;
    font-size: var(--text-sm);
    color: var(--gray-600);
}

.item-total {
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--primary);
}

/* Summary */
.summary-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    border-top: 2px solid var(--gray-200);
    padding-top: 1rem;
    margin-top: 0.5rem;
    font-weight: var(--font-bold);
    font-size: var(--text-lg);
}

/* Shipping Info */
.shipping-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.info-row strong {
    display: block;
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.info-row p {
    color: var(--gray-700);
    margin: 0;
}

.payment-status {
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
}

.payment-status.pending { background: #fef3c7; color: #92400e; }
.payment-status.paid { background: #dcfce7; color: #166534; }
.payment-status.failed { background: #fee2e2; color: #991b1b; }

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Support */
.support-card p {
    color: var(--gray-600);
    margin-bottom: 1rem;
}

.support-options {
    display: flex;
    gap: 0.5rem;
}

.support-btn {
    flex: 1;
    padding: 0.5rem;
    text-align: center;
    background: var(--gray-100);
    color: var(--gray-700);
    text-decoration: none;
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
    transition: var(--transition-fast);
}

.support-btn:hover {
    background: var(--primary);
    color: var(--white);
}

/* Status badges */
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

/* Animations */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Responsive */
@media (max-width: 768px) {
    .order-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-meta {
        justify-content: center;
    }
    
    .support-options {
        flex-direction: column;
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

function reorderItems() {
    if (confirm('Voulez-vous ajouter tous ces articles à votre panier ?')) {
        // Rediriger vers une page de recommande ou ajouter les articles au panier
        window.location.href = '/ecommerce/orders/reorder.php?id=<?php echo $order['id']; ?>';
    }
}

function downloadInvoice() {
    // Simuler le téléchargement de facture
    alert('📄 Téléchargement de la facture...\n\nVotre facture sera envoyée par email sous peu.\n\nPour toute question, contactez-nous :\n📱 +243 97 765 4321');
}
</script>

<?php require_once '../includes/footer.php'; ?> 