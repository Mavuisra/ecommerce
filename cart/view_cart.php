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

<style>
/* Styles spécifiques au panier */
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.cart-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .cart-content {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: white;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.cart-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.cart-item img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

.cart-item-details {
    flex: 1;
}

.cart-item-details h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.cart-item-price {
    color: #ff6b35;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1rem 0;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    transition: all 0.2s ease;
    color: #374151;
}

.quantity-btn:hover {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
    transform: translateY(-1px);
}

.quantity-btn:active {
    transform: translateY(0);
}

.quantity-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.quantity-input {
    width: 70px;
    text-align: center;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    padding: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.quantity-input:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.item-subtotal {
    font-weight: bold;
    margin-top: 0.5rem;
    color: #1f2937;
    background: #f3f4f6;
    padding: 0.5rem;
    border-radius: 4px;
    display: inline-block;
}

.remove-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-left: auto;
}

.remove-btn:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

.empty-cart {
    text-align: center;
    padding: 4rem 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-cart-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.order-summary {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    height: fit-content;
    position: sticky;
    top: 2rem;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    padding: 0.5rem 0;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 1.2rem;
    padding: 1rem 0;
    border-top: 2px solid #e5e7eb;
    margin-top: 1rem;
}

.shipping-notice {
    padding: 1rem;
    border-radius: 8px;
    margin: 1rem 0;
    font-size: 0.9rem;
}

.shipping-notice.free {
    background: #10b981;
    color: white;
}

.shipping-notice.paid {
    background: #f3f4f6;
    color: #374151;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #ff6b35;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast.success {
    background: #10b981;
}

.toast.error {
    background: #ef4444;
}

.toast.warning {
    background: #f59e0b;
}
</style>

<div class="cart-container">
    <h1>Mon Panier</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h2>Votre panier est vide</h2>
            <p style="color: #6b7280; margin-bottom: 2rem;">
                Découvrez nos produits et ajoutez-les à votre panier
            </p>
            <a href="/ecommerce/products/index.php" class="btn btn-primary">Voir les produits</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <!-- Articles du panier -->
            <div class="cart-items">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Articles (<?php echo count($cart_items); ?> produits)</h3>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                                <img src="/ecommerce/assets/images/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="cart-item-price"><?php echo formatPrice($item['price']); ?></div>
                                    
                                    <div class="quantity-controls">
                                        <button class="quantity-btn decrease-btn" 
                                                data-product-id="<?php echo $item['product_id']; ?>"
                                                title="Diminuer la quantité">-</button>
                                        
                                        <input type="number" 
                                               class="quantity-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['stock_quantity']; ?>"
                                               data-product-id="<?php echo $item['product_id']; ?>"
                                               data-price="<?php echo $item['price']; ?>">
                                        
                                        <button class="quantity-btn increase-btn" 
                                                data-product-id="<?php echo $item['product_id']; ?>" 
                                                data-max-quantity="<?php echo $item['stock_quantity']; ?>"
                                                title="Augmenter la quantité">+</button>
                                    </div>
                                    
                                    <div class="item-subtotal">
                                        Sous-total: <span class="subtotal-amount"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                                    </div>
                                </div>
                                
                                <button class="remove-btn" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        title="Supprimer cet article">
                                    🗑️ Supprimer
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Résumé de la commande -->
            <div class="order-summary">
                <h3>Résumé de la commande</h3>
                
                <div class="summary-line">
                    <span>Sous-total (<?php echo count($cart_items); ?> produits, <?php echo getCartTotalQuantity(); ?> unités)</span>
                    <span id="cart-subtotal"><?php echo formatPrice($cart_total); ?></span>
                </div>
                
                <div class="summary-line">
                    <span>Livraison</span>
                    <span id="shipping-cost"><?php echo $cart_total >= 50 ? 'Gratuite' : formatPrice(5.99); ?></span>
                </div>
                
                <div class="summary-total">
                    <span>Total</span>
                    <span id="cart-total"><?php echo formatPrice($cart_total + ($cart_total >= 50 ? 0 : 5.99)); ?></span>
                </div>
                
                <div class="shipping-notice <?php echo $cart_total >= 50 ? 'free' : 'paid'; ?>" id="shipping-notice">
                    <?php if ($cart_total < 50): ?>
                        🚚 Ajoutez <?php echo formatPrice(50 - $cart_total); ?> pour bénéficier de la livraison gratuite !
                    <?php else: ?>
                        ✅ Vous bénéficiez de la livraison gratuite !
                    <?php endif; ?>
                </div>
                
                <a href="/ecommerce/checkout/shipping.php" class="btn btn-primary w-full" style="margin-top: 1rem;">
                    Procéder au checkout
                </a>
                
                <a href="/ecommerce/products/index.php" class="btn btn-outline w-full" style="margin-top: 0.5rem;">
                    Continuer les achats
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<script>
console.log('🚀 DEBUT SCRIPT PANIER');

// JavaScript ultra-simple pour les boutons + et -
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM chargé');
    
    // Fonction simple pour mettre à jour la quantité
    function updateQuantity(productId, newQuantity) {
        console.log(`🔄 updateQuantity appelée: produit ${productId}, quantité ${newQuantity}`);
        
        // Vérifier le minimum
        if (newQuantity < 1) {
            alert('⚠️ La quantité minimum est 1');
            return;
        }
        
        // Faire la requête AJAX
        fetch('/ecommerce/cart/update_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${newQuantity}`
        })
        .then(response => {
            console.log('📡 Réponse reçue:', response.status);
            return response.json();
        })
        .then(result => {
            console.log('📋 Résultat:', result);
            if (result.success) {
                console.log('✅ Quantité mise à jour avec succès !');
                // Recharger la page après un court délai
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                console.error('❌ Erreur:', result.message || 'Erreur inconnue');
                // Afficher seulement les erreurs importantes
                if (result.message) {
                    alert('❌ ' + result.message);
                }
            }
        })
        .catch(error => {
            console.error('💥 Erreur:', error);
            // Afficher seulement les erreurs réseau critiques
            alert('💥 Erreur de connexion');
        });
    }
    
    // Fonction pour supprimer un article du panier
    function removeFromCart(productId) {
        console.log(`🗑️ removeFromCart appelée: produit ${productId}`);
        
        // Faire la requête AJAX
        fetch('/ecommerce/cart/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => {
            console.log('📡 Réponse suppression reçue:', response.status);
            return response.json();
        })
        .then(result => {
            console.log('📋 Résultat suppression:', result);
            if (result.success) {
                console.log('✅ Article supprimé avec succès !');
                // Recharger la page immédiatement
                window.location.reload();
            } else {
                console.error('❌ Erreur suppression:', result.message || 'Erreur inconnue');
                alert('❌ Erreur lors de la suppression: ' + (result.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('💥 Erreur suppression:', error);
            alert('💥 Erreur de connexion lors de la suppression');
        });
    }
    
    // Attacher les événements aux boutons + (METHODE DIRECTE)
    const increaseBtns = document.querySelectorAll('.increase-btn');
    console.log(`🔍 Boutons + trouvés: ${increaseBtns.length}`);
    
    increaseBtns.forEach(function(btn, index) {
        console.log(`🔧 Configuration bouton + ${index}`);
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(`🖱️ CLIC BOUTON + ${index} DETECTE !`);
            
            const productId = this.getAttribute('data-product-id');
            const maxQuantity = this.getAttribute('data-max-quantity');
            
            console.log(`📊 ProductID: ${productId}, MaxQuantity: ${maxQuantity}`);
            
            // Trouver l'input de quantité
            const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
            
            if (quantityInput) {
                const currentQuantity = parseInt(quantityInput.value);
                const newQuantity = currentQuantity + 1;
                
                console.log(`📈 ${currentQuantity} -> ${newQuantity}`);
                
                                 if (newQuantity > parseInt(maxQuantity)) {
                     console.warn(`⚠️ Stock maximum atteint (${maxQuantity} disponibles)`);
                     alert(`⚠️ Stock maximum atteint (${maxQuantity} disponibles)`);
                     return;
                 }
                
                // Mettre à jour immédiatement l'input
                quantityInput.value = newQuantity;
                
                // Appeler la fonction de mise à jour
                updateQuantity(productId, newQuantity);
            } else {
                console.error('❌ Input de quantité non trouvé');
            }
        });
    });
    
    // Attacher les événements aux boutons - (METHODE DIRECTE)
    const decreaseBtns = document.querySelectorAll('.decrease-btn');
    console.log(`🔍 Boutons - trouvés: ${decreaseBtns.length}`);
    
    decreaseBtns.forEach(function(btn, index) {
        console.log(`🔧 Configuration bouton - ${index}`);
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(`🖱️ CLIC BOUTON - ${index} DETECTE !`);
            
            const productId = this.getAttribute('data-product-id');
            
            console.log(`📊 ProductID: ${productId}`);
            
            // Trouver l'input de quantité
            const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
            
            if (quantityInput) {
                const currentQuantity = parseInt(quantityInput.value);
                const newQuantity = currentQuantity - 1;
                
                console.log(`📉 ${currentQuantity} -> ${newQuantity}`);
                
                if (newQuantity <= 0) {
                    if (confirm('Voulez-vous supprimer cet article du panier ?')) {
                        console.log('🗑️ Suppression confirmée');
                        removeFromCart(productId);
                    }
                    return;
                }
                
                // Mettre à jour immédiatement l'input
                quantityInput.value = newQuantity;
                
                // Appeler la fonction de mise à jour
                updateQuantity(productId, newQuantity);
            } else {
                console.error('❌ Input de quantité non trouvé');
            }
        });
    });
    
    // Attacher les événements aux boutons supprimer (METHODE DIRECTE)
    const removeBtns = document.querySelectorAll('.remove-btn');
    console.log(`🔍 Boutons supprimer trouvés: ${removeBtns.length}`);
    
    removeBtns.forEach(function(btn, index) {
        console.log(`🔧 Configuration bouton supprimer ${index}`);
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log(`🖱️ CLIC BOUTON SUPPRIMER ${index} DETECTE !`);
            
            const productId = this.getAttribute('data-product-id');
            console.log(`📊 ProductID à supprimer: ${productId}`);
            
            if (confirm('Êtes-vous sûr de vouloir supprimer cet article du panier ?')) {
                console.log('🗑️ Suppression confirmée par l\'utilisateur');
                removeFromCart(productId);
            } else {
                console.log('❌ Suppression annulée par l\'utilisateur');
            }
        });
    });
    
    console.log('✅ Tous les événements configurés');
    
    // Test de diagnostic
    setTimeout(function() {
        console.log('🧪 TEST DIAGNOSTIC:');
        console.log('- Boutons + :', document.querySelectorAll('.increase-btn').length);
        console.log('- Boutons - :', document.querySelectorAll('.decrease-btn').length);
        console.log('- Boutons supprimer :', document.querySelectorAll('.remove-btn').length);
        console.log('- Inputs :', document.querySelectorAll('.quantity-input').length);
        
        // Tester le premier bouton +
        const firstIncreaseBtn = document.querySelector('.increase-btn');
        if (firstIncreaseBtn) {
            console.log('🎯 Premier bouton + trouvé:', firstIncreaseBtn);
            console.log('🎯 ProductID:', firstIncreaseBtn.getAttribute('data-product-id'));
        } else {
            console.error('❌ Aucun bouton + trouvé !');
        }
        
        // Tester le premier bouton supprimer
        const firstRemoveBtn = document.querySelector('.remove-btn');
        if (firstRemoveBtn) {
            console.log('🎯 Premier bouton supprimer trouvé:', firstRemoveBtn);
            console.log('🎯 ProductID:', firstRemoveBtn.getAttribute('data-product-id'));
        } else {
            console.error('❌ Aucun bouton supprimer trouvé !');
        }
    }, 1000);
});
</script>

<?php require_once '../includes/footer.php'; ?>