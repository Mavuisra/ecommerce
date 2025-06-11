<?php
$page_title = "Mon Panier";
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Récupérer les articles du panier
$cart_items = fetchAll("
    SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ? 
    ORDER BY c.added_at DESC
", [$_SESSION['user_id']]);

$cart_total = getCartTotal();
$cart_count = getCartItemCount();
?>

<div class="cart-container">
    <h1>Mon Panier</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart" style="text-align: center; padding: 4rem 0;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
            <h2>Votre panier est vide</h2>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                Découvrez nos produits et ajoutez-les à votre panier
            </p>
            <a href="/ecommerce/products/index.php" class="btn btn-primary">Voir les produits</a>
        </div>
    <?php else: ?>
        <div class="cart-content" style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem; margin-top: 2rem;">
            <!-- Articles du panier -->
            <div class="cart-items">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Articles (<?php echo $cart_count; ?>)</h3>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                                <img src="/ecommerce/assets/images/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="cart-item-price"><?php echo formatPrice($item['price']); ?></div>
                                    
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                        <input type="number" 
                                               class="quantity-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['stock_quantity']; ?>"
                                               data-product-id="<?php echo $item['product_id']; ?>"
                                               style="width: 60px; text-align: center; border: 1px solid #e9ecef; border-radius: 4px; padding: 0.25rem;">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                    </div>
                                    
                                    <div class="item-subtotal" style="font-weight: bold; margin-top: 0.5rem;">
                                        Sous-total: <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                                
                                <button class="btn btn-danger remove-from-cart" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        style="margin-left: auto;">
                                    🗑️ Supprimer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Résumé de la commande -->
            <div class="order-summary">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Résumé de la commande</h3>
                        
                        <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Sous-total (<?php echo $cart_count; ?> articles)</span>
                            <span><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Livraison</span>
                            <span><?php echo $cart_total >= 50 ? 'Gratuite' : formatPrice(5.99); ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-total" style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2rem;">
                            <span>Total</span>
                            <span><?php echo formatPrice($cart_total + ($cart_total >= 50 ? 0 : 5.99)); ?></span>
                        </div>
                        
                        <?php if ($cart_total < 50): ?>
                            <div class="shipping-notice" style="background: var(--light-color); padding: 1rem; border-radius: var(--border-radius); margin: 1rem 0; font-size: 0.9rem;">
                                🚚 Ajoutez <?php echo formatPrice(50 - $cart_total); ?> pour bénéficier de la livraison gratuite !
                            </div>
                        <?php else: ?>
                            <div class="shipping-notice" style="background: var(--success-color); color: white; padding: 1rem; border-radius: var(--border-radius); margin: 1rem 0; font-size: 0.9rem;">
                                ✅ Vous bénéficiez de la livraison gratuite !
                            </div>
                        <?php endif; ?>
                        
                        <a href="/ecommerce/checkout/shipping.php" class="btn btn-primary w-100" style="margin-top: 1rem;">
                            Procéder au checkout
                        </a>
                        
                        <a href="/ecommerce/products/index.php" class="btn btn-outline w-100" style="margin-top: 0.5rem;">
                            Continuer les achats
                        </a>
                    </div>
                </div>
                
                <!-- Avantages -->
                <div class="benefits" style="margin-top: 1rem;">
                    <div class="card">
                        <div class="card-body">
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span style="margin-right: 0.5rem;">🔒</span>
                                <small>Paiement 100% sécurisé</small>
                            </div>
                            <div style="display: flex; align-items: center; margin-bottom: 0.5rem;">
                                <span style="margin-right: 0.5rem;">↩️</span>
                                <small>Retours gratuits sous 30 jours</small>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="margin-right: 0.5rem;">📞</span>
                                <small>Support client 24/7</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Mettre à jour la quantité
async function updateQuantity(productId, newQuantity) {
    if (newQuantity <= 0) {
        if (confirm('Voulez-vous supprimer cet article du panier ?')) {
            removeFromCart(productId);
        }
        return;
    }
    
    try {
        const response = await fetch('/ecommerce/cart/update_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${newQuantity}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload(); // Recharger pour mettre à jour les totaux
        } else {
            showNotification(result.message || 'Erreur lors de la mise à jour', 'danger');
        }
    } catch (error) {
        showNotification('Erreur réseau', 'danger');
    }
}

// Supprimer du panier
async function removeFromCart(productId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
        return;
    }
    
    try {
        showLoading();
        const response = await fetch('/ecommerce/cart/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            showNotification(result.message || 'Erreur lors de la suppression', 'danger');
        }
    } catch (error) {
        showNotification('Erreur réseau', 'danger');
    } finally {
        hideLoading();
    }
}

// Gestion des inputs de quantité
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const quantity = parseInt(this.value);
            updateQuantity(productId, quantity);
        });
    });
});
</script>

<style>
@media (max-width: 768px) {
    .cart-content {
        grid-template-columns: 1fr !important;
    }
    
    .cart-item {
        flex-direction: column !important;
        text-align: center;
    }
    
    .cart-item img {
        margin: 0 0 1rem 0 !important;
    }
    
    .quantity-controls {
        justify-content: center;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>