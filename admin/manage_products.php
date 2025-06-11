<?php
$page_title = "Gestion des produits - Administration";
require_once '../includes/header.php';

// Vérifier les droits admin
requireAdmin();

// Paramètres de filtrage et pagination
$search = sanitize($_GET['search'] ?? '');
$category_id = intval($_GET['category'] ?? 0);
$status = sanitize($_GET['status'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'created_at');
$order = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));
$items_per_page = 20;

// Construction de la requête
$where_conditions = ["1=1"];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

if ($status) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

// Compter le total
$total_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
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

// Traitement des actions (supprimer, changer statut)
if ($_POST) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Token de sécurité invalide.', 'error');
    } else {
        $action = $_POST['action'] ?? '';
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if ($product_id > 0) {
            try {
                switch ($action) {
                    case 'delete':
                        executeQuery("DELETE FROM products WHERE id = ?", [$product_id]);
                        setFlashMessage('Produit supprimé avec succès.', 'success');
                        break;
                        
                    case 'toggle_status':
                        $current_status = fetchOne("SELECT status FROM products WHERE id = ?", [$product_id]);
                        $new_status = $current_status['status'] === 'active' ? 'inactive' : 'active';
                        executeQuery("UPDATE products SET status = ? WHERE id = ?", [$new_status, $product_id]);
                        setFlashMessage('Statut du produit modifié avec succès.', 'success');
                        break;
                        
                    case 'update_stock':
                        $new_stock = intval($_POST['new_stock'] ?? 0);
                        executeQuery("UPDATE products SET stock_quantity = ? WHERE id = ?", [$new_stock, $product_id]);
                        setFlashMessage('Stock mis à jour avec succès.', 'success');
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
?>

<div class="admin-page">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>📦 Gestion des produits</h1>
            <p style="color: var(--secondary-color);">Gérer <?php echo $total_items; ?> produit(s)</p>
        </div>
        <div>
            <a href="/ecommerce/admin/add_product.php" class="btn btn-primary">+ Nouveau produit</a>
            <a href="/ecommerce/admin/dashboard.php" class="btn btn-outline">← Tableau de bord</a>
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
                           placeholder="Nom ou description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category" class="form-label">Catégorie</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="status" class="form-label">Statut</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Tous</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="sort" class="form-label">Trier par</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Nom</option>
                        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Prix</option>
                        <option value="stock_quantity" <?php echo $sort === 'stock_quantity' ? 'selected' : ''; ?>>Stock</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </div>
            
            <div style="margin-top: 1rem;">
                <a href="/ecommerce/admin/manage_products.php" class="btn btn-outline btn-sm">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Liste des produits -->
    <?php if (empty($products)): ?>
        <div class="no-results" style="text-align: center; padding: 4rem 0;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📦</div>
            <h2>Aucun produit trouvé</h2>
            <p style="color: var(--secondary-color); margin-bottom: 2rem;">
                <?php echo $search ? 'Aucun produit ne correspond à votre recherche.' : 'Commencez par ajouter des produits.'; ?>
            </p>
            <a href="/ecommerce/admin/add_product.php" class="btn btn-primary">Ajouter un produit</a>
        </div>
    <?php else: ?>
        <div class="products-table-container">
            <table class="products-table" style="width: 100%; border-collapse: collapse; background: white; border-radius: var(--border-radius); overflow: hidden; box-shadow: var(--box-shadow);">
                <thead style="background: var(--primary-color); color: white;">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">Produit</th>
                        <th style="padding: 1rem; text-align: left;">Catégorie</th>
                        <th style="padding: 1rem; text-align: right;">Prix</th>
                        <th style="padding: 1rem; text-align: center;">Stock</th>
                        <th style="padding: 1rem; text-align: center;">Statut</th>
                        <th style="padding: 1rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr style="border-bottom: 1px solid #e9ecef;">
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"
                                         onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                    <div>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small style="color: var(--secondary-color);">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?>
                                        </small><br>
                                        <small style="color: var(--secondary-color);">
                                            Ajouté le <?php echo date('d/m/Y', strtotime($product['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="background: #e9ecef; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem;">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Sans catégorie'); ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right; font-weight: bold;">
                                <?php echo formatPrice($product['price']); ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <span style="color: <?php echo $product['stock_quantity'] <= 5 ? 'var(--danger-color)' : ($product['stock_quantity'] <= 10 ? 'var(--warning-color)' : 'var(--success-color)'); ?>; font-weight: bold;">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                    <button onclick="updateStock(<?php echo $product['id']; ?>, <?php echo $product['stock_quantity']; ?>)" 
                                            class="stock-btn" 
                                            style="background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 0.8rem;"
                                            title="Modifier le stock">
                                        ✏️
                                    </button>
                                </div>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" 
                                            class="status-btn" 
                                            style="background: <?php echo $product['status'] === 'active' ? '#28a745' : '#dc3545'; ?>; color: white; border: none; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem; cursor: pointer;">
                                        <?php echo $product['status'] === 'active' ? '✅ Actif' : '❌ Inactif'; ?>
                                    </button>
                                </form>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                    <a href="/ecommerce/products/details.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline" 
                                       title="Voir le produit">
                                        👁️
                                    </a>
                                    <a href="/ecommerce/admin/edit_product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Modifier">
                                        ✏️
                                    </a>
                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')" 
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

<!-- Formulaires cachés pour les actions -->
<form id="delete-form" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="product_id" id="delete-product-id">
</form>

<form id="stock-form" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" name="action" value="update_stock">
    <input type="hidden" name="product_id" id="stock-product-id">
    <input type="hidden" name="new_stock" id="stock-new-value">
</form>

<style>
.products-table th,
.products-table td {
    border-bottom: 1px solid #e9ecef;
}

.products-table tbody tr:hover {
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
    
    .products-table-container {
        overflow-x: auto;
    }
    
    .products-table {
        min-width: 800px;
    }
}
</style>

<script>
function deleteProduct(id, name) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le produit "${name}" ?\n\nCette action est irréversible.`)) {
        document.getElementById('delete-product-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

function updateStock(id, currentStock) {
    const newStock = prompt(`Nouveau stock pour ce produit :\n(Stock actuel : ${currentStock})`, currentStock);
    
    if (newStock !== null) {
        const stockValue = parseInt(newStock);
        if (!isNaN(stockValue) && stockValue >= 0) {
            document.getElementById('stock-product-id').value = id;
            document.getElementById('stock-new-value').value = stockValue;
            document.getElementById('stock-form').submit();
        } else {
            alert('Veuillez entrer un nombre valide (≥ 0).');
        }
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 