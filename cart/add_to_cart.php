<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter des articles au panier']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

// Logs pour debug
error_log("DEBUG add_to_cart.php - product_id: $product_id, quantity reçue: " . ($_POST['quantity'] ?? 'undefined') . ", quantity après intval: $quantity");

if ($product_id <= 0 || $quantity <= 0) {
    error_log("DEBUG add_to_cart.php - Données invalides: product_id=$product_id, quantity=$quantity");
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

try {
    // Vérifier si le produit existe et est disponible
    $product = fetchOne("SELECT * FROM products WHERE id = ? AND status = 'active'", [$product_id]);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit();
    }
    
    // Vérifier le stock
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
        exit();
    }
    
    // Vérifier si l'article est déjà dans le panier
    $existing_item = fetchOne("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
    
    if ($existing_item) {
        // Remplacer la quantité au lieu de l'ajouter
        $new_quantity = $quantity;
        
        error_log("DEBUG add_to_cart.php - Produit existant dans le panier, nouvelle quantité: $new_quantity");
        
        if ($new_quantity > $product['stock_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant pour cette quantité']);
            exit();
        }
        
        executeQuery("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?", 
                    [$new_quantity, $user_id, $product_id]);
    } else {
        // Ajouter nouvel article
        error_log("DEBUG add_to_cart.php - Nouveau produit, quantité à ajouter: $quantity");
        executeQuery("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)", 
                    [$user_id, $product_id, $quantity]);
    }
    
    // Récupérer le nouveau nombre d'articles dans le panier
    $cart_count = getCartItemCount();
    
    error_log("DEBUG add_to_cart.php - Succès! Nouveau cart_count: $cart_count");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Produit ajouté au panier !',
        'cart_count' => $cart_count,
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => $quantity
        ]
    ]);
    
} catch (Exception $e) {
    error_log("DEBUG add_to_cart.php - Erreur: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?> 