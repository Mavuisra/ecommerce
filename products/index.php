<?php
$page_title = "Produits";
require_once '../includes/header.php';

// Paramètres de recherche et filtrage
$search = sanitize($_GET['search'] ?? '');
$category_id = intval($_GET['category'] ?? 0);
$sort = sanitize($_GET['sort'] ?? 'created_at');
$order = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 12;

// Construction de la requête
$where_conditions = ["status = 'active'"];
$params = [];

if ($search) {
    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $where_conditions[] = "category_id = ?";
    $params[] = $category_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Compter le total
$total_query = "SELECT COUNT(*) as total FROM products WHERE $where_clause";
$total_result = fetchOne($total_query, $params);
$total_items = $total_result['total'];

// Pagination
$pagination = paginate($total_items, $items_per_page, $page);

// Récupérer les produits
$products_query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $where_clause 
    ORDER BY p.$sort $order 
    LIMIT {$pagination['items_per_page']} OFFSET {$pagination['offset']}
";

$products = fetchAll($products_query, $params);

// Récupérer les catégories pour le filtre
$categories = fetchAll("SELECT * FROM categories ORDER BY name");
?>

<div class="products-page">
    <!-- En-tête et filtres -->
    <div class="page-header" style="margin-bottom: 2rem;">
        <h1>Nos Produits</h1>
        <p style="color: var(--secondary-color);">Découvrez notre sélection de <?php echo $total_items; ?> produits</p>
    </div>
    
    <!-- Barre de recherche et filtres -->
    <div class="filters-section" style="background: var(--light-color); padding: 1.5rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
        <form method="GET" class="filters-form">
            <div style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: end;">
                <!-- Recherche -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" class="form-label">Rechercher</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           class="form-control" 
                           placeholder="Nom ou description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <!-- Catégorie -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category" class="form-label">Catégorie</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Tri -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="sort" class="form-label">Trier par</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date d'ajout</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nom</option>
                        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Prix</option>
                    </select>
                </div>
                
                <!-- Ordre -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="order" class="form-label">Ordre</label>
                    <select id="order" name="order" class="form-control">
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">🔍 Rechercher</button>
                                    <a href="/ecommerce/products/index.php" class="btn btn-outline">Réinitialiser</a>
            </div>
        </form>
    </div>
    
    <!-- Résultats -->
    <?php if (empty($products)): ?>
        <div class="no-results" style="text-align: center; padding: 4rem 0;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
            <h2>Aucun produit trouvé</h2>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                Essayez de modifier vos critères de recherche
            </p>
            <a href="/ecommerce/products/index.php" class="btn btn-primary">Voir tous les produits</a>
        </div>
    <?php else: ?>
        <!-- Informations sur les résultats -->
        <div class="results-info" style="margin-bottom: 1rem; color: var(--secondary-color);">
            Affichage de <?php echo $pagination['offset'] + 1; ?> à 
            <?php echo min($pagination['offset'] + $pagination['items_per_page'], $total_items); ?> 
            sur <?php echo $total_items; ?> produits
        </div>
        
        <!-- Grille de produits -->
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="card">
                    <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>">
                        <img src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="card-img"
                             onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                    </a>
                    
                    <div class="card-body">
                        <div class="product-category" style="font-size: 0.8rem; color: var(--secondary-color); margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Sans catégorie'); ?>
                        </div>
                        
                        <h3 class="card-title">
                            <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        
                        <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        
                        <div class="card-price"><?php echo formatPrice($product['price']); ?></div>
                        
                        <div class="stock-info" style="margin-bottom: 1rem;">
                            <?php if ($product['stock_quantity'] > 10): ?>
                                <span style="color: var(--success-color); font-size: 0.9rem;">✅ En stock</span>
                            <?php elseif ($product['stock_quantity'] > 0): ?>
                                <span style="color: var(--warning-color); font-size: 0.9rem;">⚠️ Stock limité (<?php echo $product['stock_quantity']; ?>)</span>
                            <?php else: ?>
                                <span style="color: var(--danger-color); font-size: 0.9rem;">❌ Rupture de stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">
                                Voir détails
                            </a>
                            
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-primary add-to-cart" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-quantity="1">
                                        🛒 Ajouter
                                    </button>
                                <?php else: ?>
                                    <a href="/ecommerce/user/login.php" class="btn btn-primary">Se connecter</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>
                                    Indisponible
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <nav class="pagination" style="margin-top: 3rem;">
                <?php if ($pagination['has_prev']): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>">
                        « Précédent
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                    <?php if ($i == $pagination['current_page']): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_next']): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>">
                        Suivant »
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
@media (max-width: 768px) {
    .filters-form > div {
        grid-template-columns: 1fr !important;
    }
    
    .results-info {
        font-size: 0.9rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 