<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Récupérer le numéro de commande
$order_number = sanitize($_GET['order'] ?? '');

if (empty($order_number)) {
    header('Location: /ecommerce/');
    exit();
}

// Récupérer les détails de la commande
$order = fetchOne("
    SELECT o.*, u.first_name, u.last_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.order_number = ? AND o.user_id = ?
", [$order_number, $_SESSION['user_id']]);

if (!$order) {
    header('Location: /ecommerce/');
    exit();
}

// Récupérer les articles de la commande
$order_items = fetchAll("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
", [$order['id']]);

$page_title = "Confirmation de commande - Commande #" . $order_number;
require_once '../includes/header.php';
?>

<div class="confirmation-page fade-in-up">
    <!-- Animation de succès -->
    <div class="success-animation" style="text-align: center; margin-bottom: 3rem;">
        <div class="success-icon" style="width: 120px; height: 120px; background: var(--gradient-primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 2rem; box-shadow: var(--box-shadow-hover); animation: bounce 0.6s ease-out;">
            <span style="font-size: 4rem; color: white;">✅</span>
        </div>
        
        <h1 style="background: var(--gradient-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-size: 3rem; font-weight: 800; margin-bottom: 1rem;">
            Commande confirmée !
        </h1>
        
        <p style="font-size: 1.3rem; color: var(--text-muted); max-width: 600px; margin: 0 auto;">
            Félicitations ! Votre commande a été passée avec succès. Vous allez recevoir un email de confirmation sous peu.
        </p>
    </div>

    <!-- Barre de progression complète -->
    <div class="progress-bar" style="display: flex; justify-content: center; margin-bottom: 4rem;">
        <div class="progress-step completed" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; background: rgba(40, 167, 69, 0.1); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
            <div class="step-icon" style="width: 40px; height: 40px; background: var(--success-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">✓</div>
            <span style="font-weight: 600; color: var(--success-color);">🛒 Panier</span>
        </div>
        <div class="progress-line" style="width: 50px; height: 2px; background: var(--success-color); margin: auto 1rem;"></div>
        <div class="progress-step completed" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; background: rgba(40, 167, 69, 0.1); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
            <div class="step-icon" style="width: 40px; height: 40px; background: var(--success-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">✓</div>
            <span style="font-weight: 600; color: var(--success-color);">📦 Livraison</span>
        </div>
        <div class="progress-line" style="width: 50px; height: 2px; background: var(--success-color); margin: auto 1rem;"></div>
        <div class="progress-step completed" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; background: rgba(40, 167, 69, 0.1); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
            <div class="step-icon" style="width: 40px; height: 40px; background: var(--success-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">✓</div>
            <span style="font-weight: 600; color: var(--success-color);">✅ Confirmation</span>
        </div>
    </div>

    <div class="confirmation-content" style="display: grid; grid-template-columns: 1fr 400px; gap: 3rem; align-items: start;">
        <!-- Détails de la commande -->
        <div class="order-details">
            <div class="card fade-in-up">
                <div class="card-body">
                    <h2 style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; color: var(--dark-color);">
                        <span style="font-size: 2rem;">📋</span>
                        Détails de votre commande
                    </h2>

                    <!-- Informations de base -->
                    <div class="order-info" style="background: rgba(240, 137, 2, 0.05); padding: 2rem; border-radius: var(--border-radius); margin-bottom: 2rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                            <div>
                                <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">📦 Numéro de commande</h4>
                                <p style="font-weight: 700; font-size: 1.1rem; margin: 0;">#<?php echo $order_number; ?></p>
                            </div>
                            
                            <div>
                                <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">📅 Date de commande</h4>
                                <p style="font-weight: 600; margin: 0;"><?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                            
                            <div>
                                <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">💰 Montant total</h4>
                                <p style="font-weight: 700; font-size: 1.2rem; color: var(--primary-color); margin: 0;"><?php echo formatPrice($order['total_amount']); ?></p>
                            </div>
                            
                            <div>
                                <h4 style="color: var(--primary-color); margin-bottom: 0.5rem;">💳 Mode de paiement</h4>
                                <p style="font-weight: 600; margin: 0; text-transform: capitalize;"><?php echo $order['payment_method']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Articles commandés -->
                    <div class="ordered-items" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🛍️</span> Articles commandés
                        </h3>
                        
                        <?php foreach ($order_items as $item): ?>
                            <div class="order-item" style="display: flex; gap: 1.5rem; margin-bottom: 1.5rem; padding: 1.5rem; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);">
                                <img src="/ecommerce/assets/images/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);"
                                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.75rem 0; font-size: 1.1rem; color: var(--dark-color);"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <p style="margin: 0; color: var(--text-muted);">Quantité: <strong><?php echo $item['quantity']; ?></strong></p>
                                        <p style="margin: 0; font-weight: 700; color: var(--primary-color); font-size: 1.1rem;"><?php echo formatPrice($item['price'] * $item['quantity']); ?></p>
                                    </div>
                                    <p style="margin: 0.5rem 0 0 0; color: var(--text-muted); font-size: 0.9rem;">Prix unitaire: <?php echo formatPrice($item['price']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Adresse de livraison -->
                    <div class="shipping-address" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); padding: 2rem; border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);">
                        <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>🏠</span> Adresse de livraison
                        </h3>
                        <address style="font-style: normal; line-height: 1.6; color: var(--dark-color);">
                            <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong><br>
                            <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_postal_code'] . ' ' . $order['shipping_city']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_country']); ?>
                        </address>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prochaines étapes -->
        <div class="next-steps">
            <div class="card fade-in-up" style="position: sticky; top: 120px;">
                <div class="card-body">
                    <h2 style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; color: var(--dark-color);">
                        <span style="font-size: 2rem;">🚀</span>
                        Prochaines étapes
                    </h2>

                    <!-- Timeline des étapes -->
                    <div class="timeline" style="margin-bottom: 2rem;">
                        <div class="timeline-item" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                            <div class="timeline-icon" style="width: 40px; height: 40px; background: var(--success-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">✓</div>
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: var(--success-color);">Commande confirmée</h4>
                                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Votre commande a été enregistrée avec succès</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                            <div class="timeline-icon" style="width: 40px; height: 40px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">2</div>
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: var(--primary-color);">Préparation</h4>
                                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Nous préparons vos articles avec soin</p>
                                <small style="color: var(--warning-color);">⏱️ 1-2 jours ouvrés</small>
                            </div>
                        </div>
                        
                        <div class="timeline-item" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                            <div class="timeline-icon" style="width: 40px; height: 40px; background: var(--gray-300); color: var(--gray-800); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">3</div>
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: var(--text-muted);">Expédition</h4>
                                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Votre colis est en route</p>
                                <small style="color: var(--text-muted);">📦 2-3 jours ouvrés</small>
                            </div>
                        </div>
                        
                        <div class="timeline-item" style="display: flex; gap: 1rem;">
                            <div class="timeline-icon" style="width: 40px; height: 40px; background: var(--gray-300); color: var(--gray-800); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0;">4</div>
                            <div>
                                <h4 style="margin: 0 0 0.5rem 0; color: var(--text-muted);">Livraison</h4>
                                <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Réception à votre domicile</p>
                                <small style="color: var(--text-muted);">🏠 3-5 jours ouvrés</small>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="notifications" style="background: rgba(240, 137, 2, 0.05); padding: 1.5rem; border-radius: var(--border-radius-sm); margin-bottom: 2rem;">
                        <h4 style="color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>📧</span> Notifications
                        </h4>
                        <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-muted);">
                            <li style="margin-bottom: 0.5rem;">Email de confirmation envoyé</li>
                            <li style="margin-bottom: 0.5rem;">Notification d'expédition à venir</li>
                            <li>Suivi de livraison disponible</li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div class="actions" style="display: grid; gap: 1rem;">
                        <a href="/ecommerce/" class="btn btn-primary">
                            🛍️ Continuer mes achats
                        </a>
                        
                        <a href="/ecommerce/orders/" class="btn btn-outline">
                            📋 Voir mes commandes
                        </a>
                        
                        <a href="mailto:support@ecommerce.com?subject=Commande <?php echo $order_number; ?>" class="btn btn-secondary">
                            📞 Contacter le support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section d'aide -->
    <div class="help-section" style="margin-top: 4rem;">
        <div class="card fade-in-up">
            <div class="card-body" style="text-align: center;">
                <h2 style="color: var(--primary-color); margin-bottom: 2rem;">❓ Besoin d'aide ?</h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                    <div class="help-item" style="padding: 1.5rem; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📞</div>
                        <h4 style="color: var(--dark-color); margin-bottom: 0.5rem;">Support téléphonique</h4>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">+33 1 23 45 67 89</p>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Lun-Ven 9h-18h</p>
                    </div>
                    
                    <div class="help-item" style="padding: 1.5rem; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">💬</div>
                        <h4 style="color: var(--dark-color); margin-bottom: 0.5rem;">Chat en ligne</h4>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Réponse immédiate</p>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">24h/24 - 7j/7</p>
                    </div>
                    
                    <div class="help-item" style="padding: 1.5rem; background: rgba(255,255,255,0.8); backdrop-filter: blur(10px); border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">📧</div>
                        <h4 style="color: var(--dark-color); margin-bottom: 0.5rem;">Email</h4>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">support@ecommerce.com</p>
                        <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Réponse sous 24h</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.timeline-item:hover {
    transform: translateX(5px);
    transition: var(--transition);
}

.help-item:hover {
    transform: translateY(-5px);
    transition: var(--transition);
    box-shadow: var(--box-shadow-hover);
}

@media (max-width: 968px) {
    .confirmation-content {
        grid-template-columns: 1fr !important;
        gap: 2rem;
    }
    
    .progress-bar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .progress-line {
        display: none;
    }
    
    .card {
        position: static !important;
    }
    
    .order-info > div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation des cartes
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
    });
    
    // Animation des éléments de timeline
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
        setTimeout(() => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = 'all 0.5s ease-out';
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 100);
        }, index * 200);
    });
    
    // Effet de confetti (optionnel)
    if (typeof confetti === 'function') {
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 