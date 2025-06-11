<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour exécuter des requêtes préparées
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        throw new Exception("Erreur lors de l'exécution de la requête: " . $e->getMessage());
    }
}

// Fonction pour obtenir un seul résultat
function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

// Fonction pour obtenir tous les résultats
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

// Fonction pour obtenir le dernier ID inséré
function getLastInsertId() {
    global $pdo;
    return $pdo->lastInsertId();
}
?> 