<?php
$page_title = "KinshaMarket - Votre marketplace N°1 à Kinshasa";
$page_description = "Découvrez KinshaMarket, la première plateforme d'achat en ligne en RDC. Produits de qualité, livraison rapide à Kinshasa et paiement sécurisé.";
require_once 'includes/header.php';

// Récupérer les produits pour le slider
$slider_products = fetchAll("
    SELECT * FROM products 
    WHERE status = 'active' AND image IS NOT NULL AND image != ''
    ORDER BY created_at DESC 
    LIMIT 5
");

// Récupérer les produits populaires (simulation basée sur la date de création récente)
$popular_products = fetchAll("
    SELECT * FROM products 
    WHERE status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 8
");

// Récupérer les produits recommandés (simulation basée sur le stock)
$recommended_products = fetchAll("
    SELECT * FROM products 
    WHERE status = 'active' AND stock_quantity > 10
    ORDER BY RAND()
    LIMIT 8
");

// Récupérer les catégories
$categories = fetchAll("SELECT * FROM categories ORDER BY name");
?>

<!-- Slider Section -->
<section class="slider-section">
    <div class="slider-container">
        <div class="slider-wrapper" id="product-slider">
            <?php foreach ($slider_products as $index => $product): ?>
                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                    <div class="slide-content">
                        <div class="slide-info">
                            <h2 class="slide-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <p class="slide-description"><?php echo htmlspecialchars(substr($product['description'], 0, 150)) . '...'; ?></p>
                            <div class="slide-price"><?php echo formatPrice($product['price']); ?></div>
                            <div class="slide-actions">
                                <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                    Voir le produit
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-outline add-to-cart" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-quantity="1">
                                        Ajouter au panier
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="slide-image">
                            <img src="/ecommerce/assets/images/<?php echo $product['image']; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Navigation du slider -->
        <div class="slider-nav">
            <button class="slider-btn prev-btn" onclick="changeSlide(-1)">‹</button>
            <div class="slider-dots">
                <?php for ($i = 0; $i < count($slider_products); $i++): ?>
                    <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $i + 1; ?>)"></span>
                <?php endfor; ?>
            </div>
            <button class="slider-btn next-btn" onclick="changeSlide(1)">›</button>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <h2 class="section-title">Nos Catégories</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="/ecommerce/products/index.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <?php
                        $category_icons = [
                            'Electronics' => '📱',
                            'Clothing' => '👕',
                            'Books' => '📚',
                            'Home & Garden' => '🏡',
                            'Sports' => '⚽'
                        ];
                        echo $category_icons[$category['name']] ?? '🛍️';
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Popular Products Section -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🔥 Produits les plus populaires</h2>
            <p class="section-subtitle">Les produits les plus appréciés par nos clients à Kinshasa</p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($popular_products as $product): ?>
                <div class="product-card">
                    <?php if ($product['stock_quantity'] <= 5): ?>
                        <div class="product-badge urgent">Stock limité</div>
                    <?php else: ?>
                        <div class="product-badge popular">Populaire</div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <img src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        
                        <div class="product-actions">
                            <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">
                                Détails
                            </a>
                            
                            <?php if (isLoggedIn()): ?>
                                <button class="btn btn-primary btn-sm add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-quantity="1">
                                    Ajouter
                                </button>
                            <?php else: ?>
                                <a href="/ecommerce/user/login.php" class="btn btn-primary btn-sm">Se connecter</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($product['stock_quantity'] <= 5): ?>
                            <div class="stock-alert">
                                ⚠️ Plus que <?php echo $product['stock_quantity']; ?> en stock !
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-footer">
            <a href="/ecommerce/products/index.php" class="btn btn-outline btn-lg">Voir tous les produits populaires</a>
        </div>
    </div>
</section>

<!-- Recommended Products Section -->
<section class="products-section recommended-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">✨ Recommandés pour vous</h2>
            <p class="section-subtitle">Sélection personnalisée de produits qui pourraient vous intéresser</p>
        </div>
        
        <div class="products-grid">
            <?php foreach ($recommended_products as $product): ?>
                <div class="product-card">
                    <div class="product-badge recommended">Recommandé</div>
                    
                    <div class="product-image">
                        <img src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></p>
                        <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                        
                        <div class="product-actions">
                            <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary btn-sm">
                                Détails
                            </a>
                            
                            <?php if (isLoggedIn()): ?>
                                <button class="btn btn-primary btn-sm add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-quantity="1">
                                    Ajouter
                                </button>
                            <?php else: ?>
                                <a href="/ecommerce/user/login.php" class="btn btn-primary btn-sm">Se connecter</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section-footer">
            <a href="/ecommerce/products/index.php" class="btn btn-primary btn-lg">Découvrir plus de produits</a>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section">
    <div class="container">
        <h2 class="section-title">Pourquoi choisir KinshaMarket ?</h2>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">🚚</div>
                <h3>Livraison à Kinshasa</h3>
                <p>Livraison rapide dans toutes les communes de Kinshasa. Service de livraison à domicile disponible.</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">💳</div>
                <h3>Paiement Mobile Money</h3>
                <p>Paiements sécurisés via Orange Money, Airtel Money, et paiement à la livraison.</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">🛡️</div>
                <h3>Garantie Qualité</h3>
                <p>Tous nos produits sont authentiques et couverts par notre garantie qualité.</p>
            </div>
            
            <div class="service-card">
                <div class="service-icon">📞</div>
                <h3>Support Client</h3>
                <p>Notre équipe est disponible pour vous aider. Contact WhatsApp et téléphone.</p>
            </div>
        </div>
    </div>
</section>

<style>
/* Styles pour le slider */
.slider-section {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 2rem 0;
    margin-bottom: 3rem;
}

.slider-container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-xl);
}

.slider-wrapper {
    position: relative;
    height: 400px;
}

.slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
    background: var(--white);
}

.slide.active {
    opacity: 1;
}

.slide-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    height: 100%;
    align-items: center;
}

.slide-info {
    padding: 3rem;
}

.slide-title {
    font-size: var(--text-3xl);
    font-weight: var(--font-bold);
    color: var(--gray-900);
    margin-bottom: 1rem;
}

.slide-description {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.slide-price {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--primary);
    margin-bottom: 2rem;
}

.slide-actions {
    display: flex;
    gap: 1rem;
}

.slide-image {
    height: 100%;
    overflow: hidden;
}

.slide-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slider-nav {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.5rem 1rem;
    border-radius: var(--radius-full);
    backdrop-filter: blur(10px);
}

.slider-btn {
    background: var(--primary);
    color: var(--white);
    border: none;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    transition: var(--transition-fast);
}

.slider-btn:hover {
    transform: scale(1.1);
}

.slider-dots {
    display: flex;
    gap: 0.5rem;
}

.dot {
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
    background: var(--gray-300);
    cursor: pointer;
    transition: var(--transition-fast);
}

.dot.active {
    background: var(--primary);
}

/* Styles pour les sections */
.section-title {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    text-align: center;
    margin-bottom: 1rem;
    color: var(--gray-900);
}

.section-subtitle {
    text-align: center;
    color: var(--gray-600);
    margin-bottom: 2rem;
}

.section-header {
    margin-bottom: 3rem;
}

.section-footer {
    text-align: center;
    margin-top: 3rem;
}

/* Categories Grid */
.categories-section {
    margin-bottom: 4rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.category-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 2rem 1rem;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition: var(--transition-normal);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
}

.category-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.category-card h3 {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    margin-bottom: 0.5rem;
}

.category-card p {
    color: var(--gray-600);
    font-size: var(--text-sm);
}

/* Products Grid */
.products-section {
    margin-bottom: 4rem;
}

.recommended-section {
    background: var(--gray-50);
    padding: 3rem 0;
    border-radius: var(--radius-2xl);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
}

.product-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-normal);
    position: relative;
    border: 1px solid var(--gray-200);
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.product-badge {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: var(--font-semibold);
    z-index: 2;
}

.product-badge.popular {
    background: var(--primary);
    color: var(--white);
}

.product-badge.recommended {
    background: var(--accent);
    color: var(--gray-900);
}

.product-badge.urgent {
    background: var(--error);
    color: var(--white);
}

.product-image {
    height: 200px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition-slow);
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.product-info {
    padding: 1.5rem;
}

.product-title {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    margin-bottom: 0.5rem;
    color: var(--gray-900);
}

.product-description {
    color: var(--gray-600);
    font-size: var(--text-sm);
    margin-bottom: 1rem;
}

.product-price {
    font-size: var(--text-xl);
    font-weight: var(--font-bold);
    color: var(--primary);
    margin-bottom: 1rem;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.stock-alert {
    margin-top: 1rem;
    padding: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: var(--radius-md);
    color: var(--error);
    font-size: var(--text-sm);
    text-align: center;
}

/* Services Grid */
.services-section {
    background: var(--gray-900);
    color: var(--white);
    padding: 4rem 0;
    margin-top: 4rem;
}

.services-section .section-title {
    color: var(--white);
    margin-bottom: 3rem;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.service-card {
    text-align: center;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-lg);
    backdrop-filter: blur(10px);
}

.service-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.service-card h3 {
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    margin-bottom: 1rem;
}

.service-card p {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
}

/* Header Search Styles */
.search-container {
    max-width: 500px;
}

.search-form {
    display: flex;
    background: var(--white);
    border-radius: var(--radius-full);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.search-input {
    flex: 1;
    border: none;
    padding: 0.75rem 1rem;
    outline: none;
    font-size: var(--text-base);
}

.search-btn {
    background: var(--primary);
    color: var(--white);
    border: none;
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: var(--transition-fast);
}

.search-btn:hover {
    background: var(--primary-dark);
}

.cart-btn {
    background: var(--primary);
    color: var(--white);
    padding: 0.75rem 1rem;
    border-radius: var(--radius-lg);
    text-decoration: none;
    position: relative;
    transition: var(--transition-fast);
}

.cart-btn:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .slide-content {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .slide-info {
        padding: 2rem 1rem;
    }
    
    .slide-image {
        display: none;
    }
    
    .slider-wrapper {
        height: 300px;
    }
    
    .search-container {
        display: none !important;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .categories-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
}
</style>

<script>
// Slider functionality
let currentSlideIndex = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
    
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

function changeSlide(direction) {
    currentSlideIndex += direction;
    
    if (currentSlideIndex >= slides.length) {
        currentSlideIndex = 0;
    } else if (currentSlideIndex < 0) {
        currentSlideIndex = slides.length - 1;
    }
    
    showSlide(currentSlideIndex);
}

function currentSlide(index) {
    currentSlideIndex = index - 1;
    showSlide(currentSlideIndex);
}

// Auto-slide every 5 seconds
setInterval(() => {
    changeSlide(1);
}, 5000);

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    showSlide(0);
});
</script>

<?php require_once 'includes/footer.php'; ?> 