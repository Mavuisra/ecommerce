<?php
require_once '../includes/functions.php';
startSession();

// Détruire toutes les données de session
session_unset();
session_destroy();

// Rediriger vers l'accueil avec un message
header('Location: /ecommerce/index.php?message=Vous avez été déconnecté avec succès&type=info');
exit();
?> 