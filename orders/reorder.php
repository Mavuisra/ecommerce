<?php
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    $_SESSION['error'] = "Commande non spécifiée";
    redirect('/ecommerce/orders/my_orders.php');
    exit;
}

// Vérifier que la commande appartient à l'utilisateur
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
        p.image as product_image,
        p.price as current_price,
        p.stock_quantity,
        p.id as product_id
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
", [$order_id]);

$items_added = 0;
$items_unavailable = [];

// Ajouter les articles au panier
foreach ($order_items as $item) {
    if ($item['stock_quantity'] >= $item['quantity']) {
        // Vérifier si l'article est déjà dans le panier
        $existing_item = fetchOne("
            SELECT * FROM cart 
            WHERE user_id = ? AND product_id = ?
        ", [$user_id, $item['product_id']]);
        
        if ($existing_item) {
            // Mettre à jour la quantité
            $new_quantity = $existing_item['quantity'] + $item['quantity'];
            executeQuery("
                UPDATE cart 
                SET quantity = ? 
                WHERE user_id = ? AND product_id = ?
            ", [$new_quantity, $user_id, $item['product_id']]);
        } else {
            // Ajouter nouvel article
            executeQuery("
                INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, ?)
            ", [$user_id, $item['product_id'], $item['quantity']]);
        }
        $items_added++;
    } else {
        $items_unavailable[] = [
            'name' => $item['product_name'],
            'requested' => $item['quantity'],
            'available' => $item['stock_quantity']
        ];
    }
}

$page_title = "Recommander - KinshaMarket";
?>

<div class="container">
    <div class="reorder-result">
        <div class="result-card">
            <?php if ($items_added > 0): ?>
                <div class="success-section">
                    <div class="success-icon">✅</div>
                    <h2>Articles ajoutés au panier !</h2>
                    <p><?php echo $items_added; ?> article(s) ont été ajoutés à votre panier avec succès.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($items_unavailable)): ?>
                <div class="warning-section">
                    <div class="warning-icon">⚠️</div>
                    <h3>Certains articles ne sont plus disponibles</h3>
                    <div class="unavailable-items">
                        <?php foreach ($items_unavailable as $item): ?>
                            <div class="unavailable-item">
                                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                <span class="item-stock">
                                    Demandé: <?php echo $item['requested']; ?> | 
                                    Disponible: <?php echo $item['available']; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="/ecommerce/cart/view_cart.php" class="btn btn-primary btn-lg">
                    🛒 Voir mon panier
                </a>
                <a href="/ecommerce/products/index.php" class="btn btn-secondary">
                    🛍️ Continuer mes achats
                </a>
                <a href="/ecommerce/orders/my_orders.php" class="btn btn-outline">
                    ← Retour aux commandes
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.reorder-result {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 60vh;
    padding: 2rem 0;
}

.result-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 3rem;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--gray-200);
    max-width: 600px;
    width: 100%;
    text-align: center;
}

.success-section {
    margin-bottom: 2rem;
}

.success-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.success-section h2 {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.success-section p {
    color: var(--gray-600);
    font-size: var(--text-lg);
}

.warning-section {
    background: var(--yellow-50);
    border: 1px solid var(--yellow-200);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.warning-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.warning-section h3 {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--yellow-800);
    margin-bottom: 1rem;
}

.unavailable-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.unavailable-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: var(--white);
    border-radius: var(--radius-md);
    font-size: var(--text-sm);
}

.item-name {
    font-weight: var(--font-medium);
    color: var(--gray-900);
}

.item-stock {
    color: var(--gray-600);
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

@media (min-width: 640px) {
    .action-buttons {
        flex-direction: row;
        justify-content: center;
    }
}

/* Colors */
:root {
    --yellow-50: #fefce8;
    --yellow-200: #fde047;
    --yellow-800: #854d0e;
}
</style>

<?php require_once '../includes/footer.php'; ?> 