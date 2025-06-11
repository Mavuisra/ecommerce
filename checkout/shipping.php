<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Récupérer les articles du panier
$cart_items = fetchAll("
    SELECT c.*, p.name, p.price, p.image, p.stock_quantity 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
", [$_SESSION['user_id']]);

if (empty($cart_items)) {
    header('Location: /ecommerce/cart/view_cart.php');
    exit();
}

// Calculer le total
$cart_total = array_sum(array_map(function($item) {
    return $item['price'] * $item['quantity'];
}, $cart_items));

$shipping_cost = $cart_total >= 50 ? 0 : 5.99;
$total_amount = $cart_total + $shipping_cost;

// Traitement du formulaire
if ($_POST) {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $postal_code = sanitize($_POST['postal_code'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($first_name)) $errors[] = 'Le prénom est requis';
    if (empty($last_name)) $errors[] = 'Le nom est requis';
    if (empty($email) || !validateEmail($email)) $errors[] = 'Email valide requis';
    if (empty($address)) $errors[] = 'L\'adresse est requise';
    if (empty($city)) $errors[] = 'La ville est requise';
    if (empty($postal_code)) $errors[] = 'Le code postal est requis';
    if (empty($payment_method)) $errors[] = 'Le mode de paiement est requis';
    
    if (empty($errors)) {
        try {
            // Créer la commande
            $order_number = generateOrderNumber();
            
            executeQuery("
                INSERT INTO orders (user_id, order_number, total_amount, payment_method, 
                                  shipping_address, shipping_city, shipping_postal_code, shipping_country, 
                                  created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ", [$_SESSION['user_id'], $order_number, $total_amount, $payment_method, 
                $address, $city, $postal_code, $country]);
            
            $order_id = getLastInsertId();
            
            // Ajouter les articles de la commande
            foreach ($cart_items as $item) {
                executeQuery("
                    INSERT INTO order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ", [$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Mettre à jour le stock
                executeQuery("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE id = ?
                ", [$item['quantity'], $item['product_id']]);
            }
            
            // Vider le panier
            executeQuery("DELETE FROM cart WHERE user_id = ?", [$_SESSION['user_id']]);
            
            // Rediriger vers la confirmation
            redirectWithMessage('/ecommerce/orders/confirmation.php?order=' . $order_number, 
                              'Commande passée avec succès !', 'success');
            
        } catch (Exception $e) {
            $error = 'Erreur lors de la création de la commande. Veuillez réessayer.';
        }
    }
}

$page_title = "Checkout - Informations de livraison";
require_once '../includes/header.php';
?>

<div class="checkout-page fade-in-up">
    <div class="checkout-header" style="text-align: center; margin-bottom: 3rem;">
        <h1 style="background: var(--gradient-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; font-size: 2.5rem; font-weight: 800;">
            🛒 Finaliser votre commande
        </h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Dernière étape avant de recevoir vos produits !</p>
    </div>

    <!-- Barre de progression -->
    <div class="progress-bar" style="display: flex; justify-content: center; margin-bottom: 3rem;">
        <div class="progress-step active" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
            <div class="step-icon" style="width: 40px; height: 40px; background: var(--gradient-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">1</div>
            <span style="font-weight: 600; color: var(--dark-color);">🛒 Panier</span>
        </div>
        <div class="progress-line" style="width: 50px; height: 2px; background: var(--gradient-primary); margin: auto 1rem;"></div>
        <div class="progress-step active" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
            <div class="step-icon" style="width: 40px; height: 40px; background: var(--gradient-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">2</div>
            <span style="font-weight: 600; color: var(--dark-color);">📦 Livraison</span>
        </div>
        <div class="progress-line" style="width: 50px; height: 2px; background: var(--gray-300); margin: auto 1rem;"></div>
        <div class="progress-step" style="display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border-radius: var(--border-radius); box-shadow: var(--box-shadow-light);">
            <div class="step-icon" style="width: 40px; height: 40px; background: var(--gray-300); color: var(--gray-800); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">3</div>
            <span style="font-weight: 600; color: var(--text-muted);">✅ Confirmation</span>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger fade-in-up">
            <h4>❌ Erreurs détectées :</h4>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-content" style="display: grid; grid-template-columns: 1fr 400px; gap: 3rem; align-items: start;">
        <!-- Formulaire de livraison -->
        <div class="shipping-form">
            <div class="card fade-in-up">
                <div class="card-body">
                    <h2 style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; color: var(--dark-color);">
                        <span style="font-size: 2rem;">📋</span>
                        Informations de livraison
                    </h2>

                    <form method="POST" data-validate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Informations personnelles -->
                        <div class="form-section" style="margin-bottom: 2.5rem;">
                            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <span>👤</span> Informations personnelles
                            </h3>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">Prénom *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($first_name ?? ''); ?>"
                                           placeholder="Jean">
                                </div>
                                
                                <div class="form-group">
                                    <label for="last_name" class="form-label">Nom *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($last_name ?? ''); ?>"
                                           placeholder="Dupont">
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" required 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                           placeholder="jean.dupont@email.com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                           placeholder="06 12 34 56 78">
                                </div>
                            </div>
                        </div>

                        <!-- Adresse de livraison -->
                        <div class="form-section" style="margin-bottom: 2.5rem;">
                            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <span>🏠</span> Adresse de livraison
                            </h3>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">Adresse complète *</label>
                                <textarea id="address" name="address" class="form-control" required rows="3" 
                                          placeholder="123 Rue de la Paix, Apt 4B"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                <div class="form-group">
                                    <label for="city" class="form-label">Ville *</label>
                                    <input type="text" id="city" name="city" class="form-control" required 
                                           value="<?php echo htmlspecialchars($city ?? ''); ?>"
                                           placeholder="Paris">
                                </div>
                                
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">Code postal *</label>
                                    <input type="text" id="postal_code" name="postal_code" class="form-control" required 
                                           value="<?php echo htmlspecialchars($postal_code ?? ''); ?>"
                                           placeholder="75001">
                                </div>
                                
                                <div class="form-group">
                                    <label for="country" class="form-label">Pays</label>
                                    <select id="country" name="country" class="form-control">
                                        <option value="France" <?php echo ($country ?? '') === 'France' ? 'selected' : ''; ?>>France</option>
                                        <option value="Belgique" <?php echo ($country ?? '') === 'Belgique' ? 'selected' : ''; ?>>Belgique</option>
                                        <option value="Suisse" <?php echo ($country ?? '') === 'Suisse' ? 'selected' : ''; ?>>Suisse</option>
                                        <option value="Canada" <?php echo ($country ?? '') === 'Canada' ? 'selected' : ''; ?>>Canada</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Mode de paiement -->
                        <div class="form-section" style="margin-bottom: 2rem;">
                            <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <span>💳</span> Mode de paiement
                            </h3>
                            
                            <div class="payment-methods" style="display: grid; gap: 1rem;">
                                <label class="payment-option" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border: 2px solid var(--gray-300); border-radius: var(--border-radius-sm); cursor: pointer; transition: var(--transition); background: rgba(255,255,255,0.8); backdrop-filter: blur(5px);">
                                    <input type="radio" name="payment_method" value="card" style="accent-color: var(--primary-color);" <?php echo ($payment_method ?? '') === 'card' ? 'checked' : ''; ?>>
                                    <span style="font-size: 1.5rem;">💳</span>
                                    <div>
                                        <strong>Carte bancaire</strong>
                                        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Visa, Mastercard, American Express</p>
                                    </div>
                                </label>
                                
                                <label class="payment-option" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border: 2px solid var(--gray-300); border-radius: var(--border-radius-sm); cursor: pointer; transition: var(--transition); background: rgba(255,255,255,0.8); backdrop-filter: blur(5px);">
                                    <input type="radio" name="payment_method" value="paypal" style="accent-color: var(--primary-color);" <?php echo ($payment_method ?? '') === 'paypal' ? 'checked' : ''; ?>>
                                    <span style="font-size: 1.5rem;">🅿️</span>
                                    <div>
                                        <strong>PayPal</strong>
                                        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Paiement sécurisé via PayPal</p>
                                    </div>
                                </label>
                                
                                <label class="payment-option" style="display: flex; align-items: center; gap: 1rem; padding: 1.5rem; border: 2px solid var(--gray-300); border-radius: var(--border-radius-sm); cursor: pointer; transition: var(--transition); background: rgba(255,255,255,0.8); backdrop-filter: blur(5px);">
                                    <input type="radio" name="payment_method" value="transfer" style="accent-color: var(--primary-color);" <?php echo ($payment_method ?? '') === 'transfer' ? 'checked' : ''; ?>>
                                    <span style="font-size: 1.5rem;">🏦</span>
                                    <div>
                                        <strong>Virement bancaire</strong>
                                        <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Paiement par virement SEPA</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" style="padding: 1.25rem; font-size: 1.1rem; font-weight: 700;">
                            🛒 Confirmer la commande
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Résumé de commande -->
        <div class="order-summary">
            <div class="card fade-in-up" style="position: sticky; top: 120px;">
                <div class="card-body">
                    <h2 style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; color: var(--dark-color);">
                        <span style="font-size: 2rem;">📋</span>
                        Résumé de commande
                    </h2>

                    <!-- Articles -->
                    <div class="order-items" style="margin-bottom: 2rem;">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="order-item" style="display: flex; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--gray-200);">
                                <img src="/ecommerce/assets/images/<?php echo $item['image'] ?: 'default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: var(--border-radius-sm); box-shadow: var(--box-shadow-light);"
                                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1rem; color: var(--dark-color);"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Quantité: <?php echo $item['quantity']; ?></p>
                                    <p style="margin: 0.25rem 0 0 0; font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($item['price'] * $item['quantity']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totaux -->
                    <div class="order-totals" style="background: rgba(240, 137, 2, 0.05); padding: 1.5rem; border-radius: var(--border-radius-sm); margin-bottom: 2rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span>Sous-total</span>
                            <span style="font-weight: 600;"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                            <span>Livraison</span>
                            <span style="font-weight: 600; color: <?php echo $shipping_cost == 0 ? 'var(--success-color)' : 'var(--dark-color)'; ?>">
                                <?php echo $shipping_cost == 0 ? 'GRATUITE' : formatPrice($shipping_cost); ?>
                            </span>
                        </div>
                        
                        <?php if ($cart_total < 50 && $shipping_cost > 0): ?>
                            <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem; padding: 0.75rem; background: rgba(255,193,7,0.1); border-radius: var(--border-radius-sm);">
                                💡 Ajoutez <?php echo formatPrice(50 - $cart_total); ?> pour la livraison gratuite !
                            </div>
                        <?php endif; ?>
                        
                        <hr style="border: none; border-top: 2px solid var(--primary-color); margin: 1rem 0;">
                        
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 700;">
                            <span>Total</span>
                            <span style="color: var(--primary-color);"><?php echo formatPrice($total_amount); ?></span>
                        </div>
                    </div>

                    <!-- Garanties -->
                    <div class="guarantees" style="text-align: center;">
                        <h4 style="color: var(--primary-color); margin-bottom: 1rem;">🛡️ Vos garanties</h4>
                        <div style="display: grid; gap: 0.75rem; font-size: 0.9rem; color: var(--text-muted);">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>🔒</span>
                                <span>Paiement 100% sécurisé</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>↩️</span>
                                <span>Retours gratuits 30 jours</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>🚚</span>
                                <span>Livraison rapide et soignée</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span>📞</span>
                                <span>Support client 24/7</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-option:hover {
    border-color: var(--primary-color);
    background: rgba(240, 137, 2, 0.05) !important;
    transform: translateY(-2px);
    box-shadow: var(--box-shadow-light);
}

.payment-option input[type="radio"]:checked + span + div {
    color: var(--primary-color);
}

@media (max-width: 968px) {
    .checkout-content {
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
}

@media (max-width: 768px) {
    .checkout-content > div:nth-child(1) > .card .form-section > div {
        grid-template-columns: 1fr !important;
    }
    
    .progress-step {
        padding: 0.75rem 1rem !important;
    }
    
    .progress-step span {
        font-size: 0.9rem;
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
    
    // Gestion des options de paiement
    const paymentOptions = document.querySelectorAll('.payment-option');
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            paymentOptions.forEach(opt => opt.style.borderColor = 'var(--gray-300)');
            this.style.borderColor = 'var(--primary-color)';
        });
    });
    
    // Validation du formulaire en temps réel
    const form = document.querySelector('form[data-validate]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<span class="loading"></span> Traitement en cours...';
            button.disabled = true;
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 