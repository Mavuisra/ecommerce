<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: /ecommerce/products/index.php');
    exit();
}

// Récupérer le produit
$product = fetchOne("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active'
", [$product_id]);

if (!$product) {
    header('Location: /ecommerce/products/index.php');
    exit();
}

$page_title = $product['name'];
$page_description = substr($product['description'], 0, 160);
require_once '../includes/header.php';

// Récupérer des produits similaires
$related_products = fetchAll("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? AND status = 'active' 
    ORDER BY RAND() 
    LIMIT 4
", [$product['category_id'], $product_id]);
?>

<div class="product-details">
    <!-- Breadcrumb -->
    <nav class="breadcrumb" style="margin-bottom: 2rem; font-size: 0.9rem; color: var(--secondary-color);">
        <a href="/ecommerce/index.php" style="color: var(--primary-color);">Accueil</a> 
        <span> > </span>
        <a href="/ecommerce/products/index.php" style="color: var(--primary-color);">Produits</a>
        <?php if ($product['category_name']): ?>
            <span> > </span>
            <a href="/ecommerce/products/index.php?category=<?php echo $product['category_id']; ?>" style="color: var(--primary-color);">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </a>
        <?php endif; ?>
        <span> > </span>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </nav>

    <!-- Contenu principal du produit -->
    <div class="product-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 4rem;">
        <!-- Images du produit -->
        <div class="product-images">
            <div class="main-image" style="margin-bottom: 1rem;">
                <img id="main-product-image" 
                     src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="width: 100%; height: 400px; object-fit: cover; border-radius: var(--border-radius); box-shadow: var(--box-shadow);"
                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
            </div>
            
            <!-- Images supplémentaires (si disponibles) -->
            <div class="image-thumbnails" style="display: flex; gap: 0.5rem; overflow-x: auto;">
                <img src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid var(--primary-color);"
                     onclick="changeMainImage(this.src)">
                <!-- Ici, on pourrait ajouter d'autres images depuis le champ 'images' JSON -->
            </div>
        </div>

        <!-- Informations du produit -->
        <div class="product-info">
            <!-- Catégorie -->
            <?php if ($product['category_name']): ?>
                <div class="product-category" style="color: var(--secondary-color); font-size: 0.9rem; margin-bottom: 0.5rem;">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </div>
            <?php endif; ?>

            <!-- Nom du produit -->
            <h1 class="product-name" style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--dark-color);">
                <?php echo htmlspecialchars($product['name']); ?>
            </h1>

            <!-- Prix -->
            <div class="product-price" style="font-size: 2rem; font-weight: bold; color: var(--primary-color); margin-bottom: 1.5rem;">
                <?php echo formatPrice($product['price']); ?>
            </div>

            <!-- Stock -->
            <div class="stock-info" style="margin-bottom: 2rem;">
                <?php if ($product['stock_quantity'] > 10): ?>
                    <span style="color: var(--success-color); font-weight: 500;">✅ En stock (<?php echo $product['stock_quantity']; ?> disponibles)</span>
                <?php elseif ($product['stock_quantity'] > 0): ?>
                    <span style="color: var(--warning-color); font-weight: 500;">⚠️ Stock limité (<?php echo $product['stock_quantity']; ?> restants)</span>
                <?php else: ?>
                    <span style="color: var(--danger-color); font-weight: 500;">❌ Rupture de stock</span>
                <?php endif; ?>
            </div>

            <!-- Description courte -->
            <div class="product-description" style="margin-bottom: 2rem; line-height: 1.6; color: var(--secondary-color);">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>

            <!-- Spécifications -->
            <?php if ($product['weight'] || $product['dimensions']): ?>
                <div class="product-specs" style="background: var(--light-color); padding: 1rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Spécifications</h3>
                    <?php if ($product['weight']): ?>
                        <div style="margin-bottom: 0.5rem;"><strong>Poids:</strong> <?php echo $product['weight']; ?> kg</div>
                    <?php endif; ?>
                    <?php if ($product['dimensions']): ?>
                        <div><strong>Dimensions:</strong> <?php echo htmlspecialchars($product['dimensions']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="product-actions">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <?php if (isLoggedIn()): ?>
                        <!-- Sélecteur de quantité -->
                        <div class="quantity-selector" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                            <label for="quantity" style="font-weight: 500;">Quantité:</label>
                            <div style="display: flex; align-items: center; border: 2px solid #e9ecef; border-radius: var(--border-radius);">
                                <button type="button" onclick="changeQuantity(-1)" style="padding: 0.5rem 1rem; border: none; background: none; cursor: pointer;">-</button>
                                <input type="number" 
                                       id="quantity" 
                                       value="1" 
                                       min="1" 
                                       max="<?php echo $product['stock_quantity']; ?>"
                                       style="width: 60px; text-align: center; border: none; padding: 0.5rem;">
                                <button type="button" onclick="changeQuantity(1)" style="padding: 0.5rem 1rem; border: none; background: none; cursor: pointer;">+</button>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="action-buttons" style="display: flex; gap: 1rem; margin-bottom: 2rem;">
                            <button class="btn btn-primary" 
                                    style="flex: 2; padding: 1rem; font-size: 1.1rem;"
                                    onclick="addToCartWithQuantity()">
                                🛒 Ajouter au panier
                            </button>
                            <button class="btn btn-outline" 
                                    style="flex: 1; padding: 1rem;"
                                    onclick="addToWishlist(<?php echo $product_id; ?>)">
                                ❤️ Favoris
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="login-prompt" style="background: var(--light-color); padding: 1.5rem; border-radius: var(--border-radius); text-align: center; margin-bottom: 2rem;">
                            <p style="margin-bottom: 1rem;">Connectez-vous pour ajouter ce produit à votre panier</p>
                            <a href="/ecommerce/user/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary">
                                Se connecter
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="out-of-stock" style="background: #f8d7da; color: #721c24; padding: 1.5rem; border-radius: var(--border-radius); text-align: center; margin-bottom: 2rem;">
                        <h3>Produit indisponible</h3>
                        <p>Ce produit est actuellement en rupture de stock.</p>
                        <button class="btn btn-outline" onclick="notifyWhenAvailable(<?php echo $product_id; ?>)">
                            📧 M'avertir quand disponible
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Garanties et services -->
                <div class="guarantees" style="border-top: 1px solid #e9ecef; padding-top: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>🚚</span>
                            <small>Livraison gratuite dès 50€</small>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>↩️</span>
                            <small>Retours gratuits 30 jours</small>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>🔒</span>
                            <small>Paiement sécurisé</small>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>📞</span>
                            <small>Support client 24/7</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits similaires -->
    <?php if (!empty($related_products)): ?>
        <section class="related-products">
            <h2 style="margin-bottom: 2rem; text-align: center;">Produits similaires</h2>
            <div class="products-grid">
                <?php foreach ($related_products as $related_product): ?>
                    <div class="card">
                        <a href="/ecommerce/products/details.php?id=<?php echo $related_product['id']; ?>">
                            <img src="/ecommerce/assets/images/<?php echo $related_product['image'] ?: 'default-product.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($related_product['name']); ?>" 
                                 class="card-img"
                                 onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                        </a>
                        <div class="card-body">
                            <h3 class="card-title">
                                <a href="/ecommerce/products/details.php?id=<?php echo $related_product['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <?php echo htmlspecialchars($related_product['name']); ?>
                                </a>
                            </h3>
                            <div class="card-price"><?php echo formatPrice($related_product['price']); ?></div>
                            <div class="mt-2">
                                <?php if ($related_product['stock_quantity'] > 0 && isLoggedIn()): ?>
                                    <button class="btn btn-primary add-to-cart" 
                                            data-product-id="<?php echo $related_product['id']; ?>"
                                            data-quantity="1">
                                        🛒 Ajouter
                                    </button>
                                <?php else: ?>
                                    <a href="/ecommerce/products/details.php?id=<?php echo $related_product['id']; ?>" class="btn btn-outline">
                                        Voir détails
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
// Changer la quantité
function changeQuantity(delta) {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    const newValue = currentValue + delta;
    const maxValue = parseInt(quantityInput.max);
    
    if (newValue >= 1 && newValue <= maxValue) {
        quantityInput.value = newValue;
    }
}

// Changer l'image principale
function changeMainImage(src) {
    document.getElementById('main-product-image').src = src;
    
    // Mettre à jour les bordures des miniatures
    document.querySelectorAll('.image-thumbnails img').forEach(img => {
        if (img.src === src) {
            img.style.border = '2px solid var(--primary-color)';
        } else {
            img.style.border = '2px solid transparent';
        }
    });
}

// Ajouter au panier avec quantité
function addToCartWithQuantity() {
    const quantity = document.getElementById('quantity').value;
    addToCart(<?php echo $product_id; ?>, quantity);
}

// Ajouter aux favoris (fonction placeholder)
function addToWishlist(productId) {
    showNotification('Fonctionnalité à venir : Ajout aux favoris', 'info');
}

// Notification de disponibilité (fonction placeholder)
function notifyWhenAvailable(productId) {
    showNotification('Fonctionnalité à venir : Notification de disponibilité', 'info');
}
</script>

<style>
@media (max-width: 768px) {
    .product-content {
        grid-template-columns: 1fr !important;
        gap: 2rem;
    }
    
    .product-name {
        font-size: 2rem !important;
    }
    
    .action-buttons {
        flex-direction: column !important;
    }
    
    .action-buttons .btn {
        flex: none !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 