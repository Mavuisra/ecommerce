<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    // Récupérer le nombre distinct de produits dans le panier (pas la somme des quantités)
    $result = fetchOne("
        SELECT COUNT(*) as count 
        FROM cart 
        WHERE user_id = ?
    ", [$_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'count' => 0,
        'message' => 'Erreur lors de la récupération du panier'
    ]);
}
?> 