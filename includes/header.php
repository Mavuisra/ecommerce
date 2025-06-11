<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
startSession();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>E-Commerce</title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Boutique en ligne moderne avec une large gamme de produits'; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/ecommerce/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/ecommerce/assets/images/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="/ecommerce/index.php" class="logo">
                    <span class="logo-icon">🛍️</span>
                    KinshaMarket
                </a>
                
                <div class="search-container flex-1 mx-8 hidden md:block">
                    <form action="/ecommerce/products/index.php" method="GET" class="search-form">
                        <input type="text" 
                               name="search" 
                               placeholder="Rechercher un produit à Kinshasa..." 
                               class="search-input"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="search-btn">🔍</button>
                    </form>
                </div>
                
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="/ecommerce/index.php">Accueil</a></li>
                    <li><a href="/ecommerce/products/index.php">Produits</a></li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li><a href="/ecommerce/orders/my_orders.php">Mes Commandes</a></li>
                        <li><a href="/ecommerce/user/profile.php">Mon Profil</a></li>
                        
                        <?php if (isAdmin()): ?>
                            <li><a href="/ecommerce/admin/dashboard.php">Administration</a></li>
                        <?php endif; ?>
                        
                        <li><a href="/ecommerce/user/logout.php">Se Déconnecter</a></li>
                    <?php else: ?>
                        <li><a href="/ecommerce/user/login.php">Se Connecter</a></li>
                        <li><a href="/ecommerce/user/register.php">S'inscrire</a></li>
                    <?php endif; ?>
                </ul>
                
                <div class="navbar-actions">
                    <a href="/ecommerce/cart/view_cart.php" class="cart-btn">
                        🛒 
                        <?php $cartCount = getCartItemCount(); ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle">☰</button>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php displayFlashMessage(); ?>
</body>
</html>