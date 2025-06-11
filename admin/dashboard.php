<?php
$page_title = "Tableau de bord - Administration";
require_once '../includes/header.php';

// Vérifier les droits admin
requireAdmin();

// Récupérer les statistiques
$stats = [
    'total_products' => fetchOne("SELECT COUNT(*) as count FROM products")['count'],
    'total_users' => fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'user'")['count'],
    'total_orders' => fetchOne("SELECT COUNT(*) as count FROM orders")['count'],
    'total_revenue' => fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'")['total'],
    'pending_orders' => fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
    'low_stock' => fetchOne("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 5 AND status = 'active'")['count']
];

// Récupérer les commandes récentes
$recent_orders = fetchAll("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");

// Récupérer les produits en rupture de stock
$low_stock_products = fetchAll("
    SELECT * FROM products 
    WHERE stock_quantity <= 5 AND status = 'active' 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");
?>

<div class="admin-dashboard">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>Tableau de bord</h1>
            <p style="color: var(--secondary-color);">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
        </div>
        <div>
            <a href="/ecommerce/admin/add_product.php" class="btn btn-primary">📦 Nouveau produit</a>
            <a href="/ecommerce/index.php" class="btn btn-outline">🌐 Voir le site</a>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📦</div>
            <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $stats['total_products']; ?></h3>
            <p style="opacity: 0.9; margin: 0;">Produits</p>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 2rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">👥</div>
            <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $stats['total_users']; ?></h3>
            <p style="opacity: 0.9; margin: 0;">Utilisateurs</p>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 2rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🛒</div>
            <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $stats['total_orders']; ?></h3>
            <p style="opacity: 0.9; margin: 0;">Commandes</p>
        </div>
        
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 2rem; border-radius: var(--border-radius); text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">💰</div>
            <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo formatPrice($stats['total_revenue']); ?></h3>
            <p style="opacity: 0.9; margin: 0;">Chiffre d'affaires</p>
        </div>
    </div>

    <!-- Alertes -->
    <?php if ($stats['pending_orders'] > 0 || $stats['low_stock'] > 0): ?>
        <div class="alerts-section" style="margin-bottom: 3rem;">
            <h2>Alertes</h2>
            <div style="display: grid; gap: 1rem;">
                <?php if ($stats['pending_orders'] > 0): ?>
                    <div class="alert alert-warning">
                        ⚠️ Vous avez <?php echo $stats['pending_orders']; ?> commande(s) en attente de traitement.
                        <a href="/ecommerce/admin/manage_orders.php" class="btn btn-outline" style="margin-left: 1rem;">Voir les commandes</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($stats['low_stock'] > 0): ?>
                    <div class="alert alert-danger">
                        🔔 <?php echo $stats['low_stock']; ?> produit(s) en stock faible (≤ 5 unités).
                        <a href="#low-stock-section" class="btn btn-outline" style="margin-left: 1rem;">Voir les produits</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <div class="dashboard-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Commandes récentes -->
        <div class="recent-orders">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Commandes récentes</h3>
                    
                    <?php if (empty($recent_orders)): ?>
                        <p style="color: var(--secondary-color); text-align: center; padding: 2rem;">
                            Aucune commande pour le moment
                        </p>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e9ecef;">
                                    <div>
                                        <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></small><br>
                                        <small style="color: var(--secondary-color);">
                                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-weight: bold;"><?php echo formatPrice($order['total_amount']); ?></div>
                                        <span class="status-badge status-<?php echo $order['status']; ?>" 
                                              style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.8rem; font-weight: 500;
                                                     background: <?php echo $order['status'] === 'pending' ? '#fff3cd' : ($order['status'] === 'delivered' ? '#d4edda' : '#d1ecf1'); ?>;
                                                     color: <?php echo $order['status'] === 'pending' ? '#856404' : ($order['status'] === 'delivered' ? '#155724' : '#0c5460'); ?>;">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'En attente',
                                                'processing' => 'En cours',
                                                'shipped' => 'Expédiée',
                                                'delivered' => 'Livrée',
                                                'cancelled' => 'Annulée'
                                            ];
                                            echo $status_labels[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-2">
                            <a href="/ecommerce/admin/manage_orders.php" class="btn btn-outline">Voir toutes les commandes</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Produits en stock faible -->
        <div class="low-stock" id="low-stock-section">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Stock faible</h3>
                    
                    <?php if (empty($low_stock_products)): ?>
                        <p style="color: var(--success-color); text-align: center; padding: 2rem;">
                            ✅ Tous les produits sont bien approvisionnés
                        </p>
                    <?php else: ?>
                        <div class="products-list">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="product-item" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid #e9ecef;">
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="/ecommerce/assets/images/<?php echo $product['image'] ?: 'default-product.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"
                                             onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                            <small style="color: var(--secondary-color);"><?php echo formatPrice($product['price']); ?></small>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="color: <?php echo $product['stock_quantity'] == 0 ? 'var(--danger-color)' : 'var(--warning-color)'; ?>; font-weight: bold;">
                                            <?php echo $product['stock_quantity']; ?> en stock
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-2">
                            <a href="/ecommerce/admin/manage_products.php" class="btn btn-outline">Gérer les stocks</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="quick-actions" style="margin-top: 3rem;">
        <h2>Actions rapides</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <a href="/ecommerce/admin/add_product.php" class="action-card" style="display: block; padding: 1.5rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); text-decoration: none; text-align: center; transition: var(--transition);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">📦</div>
                <h4>Ajouter un produit</h4>
                <p style="color: var(--secondary-color); font-size: 0.9rem;">Créer un nouveau produit</p>
            </a>
            
            <a href="/ecommerce/admin/manage_orders.php" class="action-card" style="display: block; padding: 1.5rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); text-decoration: none; text-align: center; transition: var(--transition);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🛒</div>
                <h4>Gérer les commandes</h4>
                <p style="color: var(--secondary-color); font-size: 0.9rem;">Traiter les commandes</p>
            </a>
            
            <a href="/ecommerce/admin/manage_users.php" class="action-card" style="display: block; padding: 1.5rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); text-decoration: none; text-align: center; transition: var(--transition);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                <h4>Gérer les utilisateurs</h4>
                <p style="color: var(--secondary-color); font-size: 0.9rem;">Administrer les comptes</p>
            </a>
            
            <a href="/ecommerce/admin/settings.php" class="action-card" style="display: block; padding: 1.5rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow); text-decoration: none; text-align: center; transition: var(--transition);">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚙️</div>
                <h4>Paramètres</h4>
                <p style="color: var(--secondary-color); font-size: 0.9rem;">Configuration du site</p>
            </a>
        </div>
    </div>
</div>

<style>
.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column !important;
        text-align: center;
        gap: 1rem;
    }
    
    .dashboard-content {
        grid-template-columns: 1fr !important;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 