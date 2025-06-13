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
$user_id = $_SESSION['user_id'];

// Logs de debug
error_log("DEBUG remove_from_cart.php - product_id: $product_id, user_id: $user_id");
error_log("DEBUG remove_from_cart.php - POST data: " . print_r($_POST, true));

if ($product_id <= 0) {
    error_log("DEBUG remove_from_cart.php - Données invalides: product_id=$product_id");
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit();
}

try {
    executeQuery("DELETE FROM cart WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Article supprimé du panier',
        'cart_count' => getCartItemCount()
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?> 