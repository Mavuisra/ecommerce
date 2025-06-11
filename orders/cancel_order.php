<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'ID de commande manquant']);
    exit;
}

try {
    // Vérifier que la commande appartient à l'utilisateur et peut être annulée
    $order = fetchOne("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
    ", [$order_id, $user_id]);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée ou ne peut pas être annulée']);
        exit;
    }
    
    // Mettre à jour le statut de la commande
    executeQuery("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$order_id]);
    
    // Log de l'action
    logActivity($user_id, 'order_cancelled', "Commande #{$order['order_number']} annulée");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Commande annulée avec succès'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de l'annulation de commande: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'annulation']);
}
?> 