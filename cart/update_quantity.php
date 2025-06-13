<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

// Logs de debug
error_log("DEBUG update_quantity.php - product_id: $product_id, quantity: $quantity, user_id: $user_id");
error_log("DEBUG update_quantity.php - POST data: " . print_r($_POST, true));

if ($product_id <= 0 || $quantity < 0) {
    error_log("DEBUG update_quantity.php - Données invalides: product_id=$product_id, quantity=$quantity");
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

try {
    // Si la quantité est 0, supprimer l'article du panier
    if ($quantity == 0) {
        executeQuery("DELETE FROM cart WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
        echo json_encode([
            'success' => true, 
            'message' => 'Article supprimé du panier',
            'cart_count' => getCartItemCount()
        ]);
        exit();
    }
    
    // Vérifier si le produit existe et est disponible
    $product = fetchOne("SELECT * FROM products WHERE id = ? AND status = 'active'", [$product_id]);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit();
    }
    
    // Vérifier le stock
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant. Stock disponible: ' . $product['stock_quantity']]);
        exit();
    }
    
    // Vérifier si l'article est dans le panier
    $existing_item = fetchOne("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
    
    if ($existing_item) {
        // Mettre à jour la quantité
        executeQuery("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?", 
                    [$quantity, $user_id, $product_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Quantité mise à jour',
            'cart_count' => getCartItemCount(),
            'new_quantity' => $quantity,
            'price' => $product['price']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Article non trouvé dans le panier']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?> 