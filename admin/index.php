<?php
// Gestion de l'accès au panneau d'administration
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Démarrer la session
startSession();

// Vérifier l'état de connexion de l'utilisateur
if (!isLoggedIn()) {
    // L'utilisateur n'est pas connecté -> redirection vers login
    header('Location: /ecommerce/admin/login.php');
    exit();
}

// L'utilisateur est connecté, vérifier s'il est admin
if (!isAdmin()) {
    // L'utilisateur est connecté mais n'est pas admin -> redirection vers l'accueil
    setFlashMessage('Accès refusé. Vous devez être administrateur pour accéder à cette section.', 'error');
    header('Location: /ecommerce/index.php');
    exit();
}

// L'utilisateur est connecté ET admin -> redirection vers le tableau de bord
header('Location: /ecommerce/admin/dashboard.php');
exit();
?> 