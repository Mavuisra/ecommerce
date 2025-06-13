<?php
// Démarrer la session si elle n'est pas déjà démarrée
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    startSession();
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Rediriger si pas connecté
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /ecommerce/user/login.php');
        exit();
    }
}

// Rediriger si pas admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /ecommerce/index.php');
        exit();
    }
}

// Nettoyer et sécuriser les données d'entrée
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Valider l'email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hasher le mot de passe
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Vérifier le mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Générer un token CSRF
function generateCSRFToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier le token CSRF
function verifyCSRFToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Formater le prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' fc';
}

// Obtenir les informations de l'utilisateur connecté
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Compter les éléments dans le panier
function getCartItemCount() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Obtenir la quantité totale d'articles dans le panier
function getCartTotalQuantity() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Calculer le total du panier
function getCartTotal() {
    if (!isLoggedIn()) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT SUM(c.quantity * p.price) as total 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// Générer un numéro de commande unique
function generateOrderNumber() {
    return 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
}

// Pagination
function paginate($total_items, $items_per_page = 12, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

// Afficher les messages flash
function displayFlashMessage() {
    startSession();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// Définir un message flash
function setFlashMessage($message, $type = 'info') {
    startSession();
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Rediriger avec message
function redirectWithMessage($url, $message, $type = 'info') {
    setFlashMessage($message, $type);
    header("Location: {$url}");
    exit();
}

// Upload d'image
function uploadImage($file, $directory = 'assets/images/') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Type de fichier non autorisé.');
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception('Le fichier est trop volumineux.');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}
?> 