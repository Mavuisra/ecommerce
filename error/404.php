<?php
$page_title = "Page non trouvée";
$page_description = "La page que vous cherchez n'existe pas.";
require_once '../includes/header.php';
?>

<div style="text-align: center; padding: 4rem 2rem;">
    <div style="font-size: 6rem; margin-bottom: 2rem;">🔍</div>
    <h1 style="font-size: 3rem; margin-bottom: 1rem; color: var(--danger-color);">404</h1>
    <h2 style="margin-bottom: 2rem;">Page non trouvée</h2>
    <p style="font-size: 1.2rem; margin-bottom: 3rem; color: var(--text-muted);">
        Désolé, la page que vous cherchez n'existe pas ou a été déplacée.
    </p>
    
    <div style="margin-bottom: 3rem;">
        <a href="/ecommerce/" class="btn btn-primary" style="margin-right: 1rem;">🏠 Retour à l'accueil</a>
        <a href="/ecommerce/products/" class="btn btn-outline">🛍️ Voir nos produits</a>
    </div>
    
    <div style="max-width: 500px; margin: 0 auto;">
        <h3 style="margin-bottom: 1.5rem;">Que pouvez-vous faire ?</h3>
        <ul style="text-align: left; list-style: none; padding: 0;">
            <li style="margin-bottom: 1rem;">✅ Vérifier l'orthographe de l'URL</li>
            <li style="margin-bottom: 1rem;">✅ Retourner à la page d'accueil</li>
            <li style="margin-bottom: 1rem;">✅ Parcourir nos produits</li>
            <li style="margin-bottom: 1rem;">✅ Utiliser notre moteur de recherche</li>
        </ul>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 