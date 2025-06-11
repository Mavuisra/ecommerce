<?php
$page_title = "Mon Profil - KinshaMarket";
$page_description = "Gérez vos informations personnelles et paramètres de compte sur KinshaMarket.";
require_once '../includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$user_id = $_SESSION['user_id'];
$user = getCurrentUser();

// Messages de feedback
$success_message = '';
$error_message = '';

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        $city = sanitize($_POST['city']);
        $commune = sanitize($_POST['commune']);
        
        // Validation
        $errors = [];
        
        if (empty($first_name)) $errors[] = "Le prénom est obligatoire";
        if (empty($last_name)) $errors[] = "Le nom est obligatoire";
        if (empty($email) || !validateEmail($email)) $errors[] = "Email valide requis";
        
        // Vérifier si l'email n'est pas déjà utilisé par un autre utilisateur
        if (!empty($email) && $email !== $user['email']) {
            $existing_user = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing_user) {
                $errors[] = "Cet email est déjà utilisé par un autre compte";
            }
        }
        
        if (empty($errors)) {
            try {
                executeQuery("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, commune = ?
                    WHERE id = ?
                ", [$first_name, $last_name, $email, $phone, $address, $city, $commune, $user_id]);
                
                $success_message = "Profil mis à jour avec succès !";
                $user = getCurrentUser(); // Recharger les données utilisateur
            } catch (Exception $e) {
                $error_message = "Erreur lors de la mise à jour du profil";
            }
        } else {
            $error_message = implode(", ", $errors);
        }
    }
    
    // Traitement du changement de mot de passe
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $errors = [];
        
        if (empty($current_password)) $errors[] = "Mot de passe actuel requis";
        if (empty($new_password)) $errors[] = "Nouveau mot de passe requis";
        if (strlen($new_password) < 6) $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
        if ($new_password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";
        
        // Vérifier le mot de passe actuel
        if (!empty($current_password) && !verifyPassword($current_password, $user['password'])) {
            $errors[] = "Mot de passe actuel incorrect";
        }
        
        if (empty($errors)) {
            try {
                $hashed_password = hashPassword($new_password);
                executeQuery("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $user_id]);
                $success_message = "Mot de passe modifié avec succès !";
            } catch (Exception $e) {
                $error_message = "Erreur lors du changement de mot de passe";
            }
        } else {
            $error_message = implode(", ", $errors);
        }
    }
}

// Statistiques utilisateur
$user_stats = [
    'total_orders' => fetchOne("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user_id])['count'] ?? 0,
    'total_spent' => fetchOne("
        SELECT SUM(oi.quantity * oi.price) as total 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ?
    ", [$user_id])['total'] ?? 0,
    'member_since' => $user['created_at']
];
?>

<div class="container">
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="/ecommerce/index.php">Accueil</a>
            <span>›</span>
            <span>Mon Profil</span>
        </nav>
        
        <div class="page-title-section">
            <h1 class="page-title">👤 Mon Profil</h1>
            <p class="page-subtitle">Gérez vos informations personnelles et paramètres de compte</p>
        </div>
    </div>

    <!-- Messages de feedback -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="profile-layout">
        <!-- Sidebar avec statistiques -->
        <div class="profile-sidebar">
            <div class="user-card">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="user-member-since">
                        Membre depuis <?php echo date('F Y', strtotime($user_stats['member_since'])); ?>
                    </p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $user_stats['total_orders']; ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value"><?php echo formatPrice($user_stats['total_spent']); ?></div>
                    <div class="stat-label">Total dépensé</div>
                </div>
            </div>

            <div class="quick-actions">
                <h4>Actions rapides</h4>
                <a href="/ecommerce/orders/my_orders.php" class="quick-action-btn">
                    <span class="icon">📦</span>
                    Mes commandes
                </a>
                <a href="/ecommerce/cart/view_cart.php" class="quick-action-btn">
                    <span class="icon">🛒</span>
                    Mon panier
                </a>
                <a href="/ecommerce/products/index.php" class="quick-action-btn">
                    <span class="icon">🛍️</span>
                    Continuer mes achats
                </a>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="profile-content">
            <!-- Onglets -->
            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" onclick="showTab('profile-info')">
                        👤 Informations personnelles
                    </button>
                    <button class="tab-btn" onclick="showTab('password-change')">
                        🔒 Mot de passe
                    </button>
                    <button class="tab-btn" onclick="showTab('preferences')">
                        ⚙️ Préférences
                    </button>
                </div>
            </div>

            <!-- Onglet Informations personnelles -->
            <div id="profile-info" class="tab-content active">
                <div class="form-section">
                    <h3>📝 Informations personnelles</h3>
                    <p class="section-description">
                        Mettez à jour vos informations personnelles pour améliorer votre expérience d'achat.
                    </p>

                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">Prénom *</label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">Nom *</label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="form-control" 
                                       placeholder="+243 XXX XXX XXX"
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">Adresse</label>
                            <input type="text" 
                                   id="address" 
                                   name="address" 
                                   class="form-control" 
                                   placeholder="Avenue, numéro..."
                                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="commune" class="form-label">Commune</label>
                                <select id="commune" name="commune" class="form-control">
                                    <option value="">Sélectionner une commune</option>
                                    <?php 
                                    $communes = [
                                        'Kalamu', 'Kasa-Vubu', 'Lingwala', 'Gombe', 'Kinshasa',
                                        'Barumbu', 'Masina', 'N\'Djili', 'Kimbanseke', 'Lemba',
                                        'Matete', 'Ngaba', 'Ngiri-Ngiri', 'Bandalungwa', 'Bumbu',
                                        'Makala', 'Ngaliema', 'Mont-Ngafula', 'Kisenso', 'Maluku',
                                        'Nsele', 'N\'Sele', 'Kintambo', 'Limete'
                                    ];
                                    foreach ($communes as $commune): 
                                        $selected = ($user['commune'] ?? '') === $commune ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $commune; ?>" <?php echo $selected; ?>>
                                            <?php echo $commune; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="city" class="form-label">Ville</label>
                                <input type="text" 
                                       id="city" 
                                       name="city" 
                                       class="form-control" 
                                       value="Kinshasa" 
                                       readonly
                                       style="background: var(--gray-100);">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary btn-lg">
                                💾 Sauvegarder les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Onglet Mot de passe -->
            <div id="password-change" class="tab-content">
                <div class="form-section">
                    <h3>🔒 Changer le mot de passe</h3>
                    <p class="section-description">
                        Assurez-vous d'utiliser un mot de passe fort pour protéger votre compte.
                    </p>

                    <form method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Mot de passe actuel *</label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="form-control" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">Nouveau mot de passe *</label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="form-control" 
                                   minlength="6"
                                   required>
                            <div class="form-help">Minimum 6 caractères</div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe *</label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary btn-lg">
                                🔐 Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Onglet Préférences -->
            <div id="preferences" class="tab-content">
                <div class="form-section">
                    <h3>⚙️ Préférences</h3>
                    <p class="section-description">
                        Personnalisez votre expérience d'achat sur KinshaMarket.
                    </p>

                    <div class="preferences-grid">
                        <div class="preference-item">
                            <div class="preference-header">
                                <h4>📧 Notifications par email</h4>
                                <p>Recevez des notifications sur vos commandes et offres spéciales</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="preference-item">
                            <div class="preference-header">
                                <h4>📱 Notifications SMS</h4>
                                <p>Recevez des SMS pour les mises à jour importantes</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="preference-item">
                            <div class="preference-header">
                                <h4>🛍️ Offres promotionnelles</h4>
                                <p>Soyez informé des promotions et réductions</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="preference-item">
                            <div class="preference-header">
                                <h4>📦 Suivi de commandes</h4>
                                <p>Notifications de statut de livraison</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-primary btn-lg" onclick="savePreferences()">
                            💾 Sauvegarder les préférences
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de suppression de compte -->
    <div class="danger-zone">
        <h3>⚠️ Zone de danger</h3>
        <p>Les actions suivantes sont irréversibles. Procédez avec prudence.</p>
        <button class="btn btn-danger" onclick="confirmDeleteAccount()">
            🗑️ Supprimer mon compte
        </button>
    </div>
</div>

<style>
/* Layout de profil */
.profile-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    margin-bottom: 3rem;
}

@media (max-width: 1024px) {
    .profile-layout {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

/* Sidebar */
.profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.user-card {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    text-align: center;
}

.user-avatar {
    width: 5rem;
    height: 5rem;
    background: var(--gradient-primary);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    margin: 0 auto 1rem;
}

.user-info h3 {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.user-email {
    color: var(--gray-600);
    font-size: var(--text-sm);
    margin-bottom: 0.5rem;
}

.user-member-since {
    color: var(--gray-500);
    font-size: var(--text-xs);
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 1.5rem 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
    text-align: center;
}

.stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: var(--text-lg);
    font-weight: var(--font-bold);
    color: var(--primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: var(--text-xs);
    color: var(--gray-600);
}

.quick-actions {
    background: var(--white);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

.quick-actions h4 {
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--gray-900);
    margin-bottom: 1rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    text-decoration: none;
    color: var(--gray-700);
    border-radius: var(--radius-md);
    transition: var(--transition-fast);
    font-size: var(--text-sm);
}

.quick-action-btn:hover {
    background: var(--gray-100);
    color: var(--primary);
}

.quick-action-btn:last-child {
    margin-bottom: 0;
}

/* Contenu principal */
.profile-content {
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--gray-200);
}

/* Onglets */
.tabs-container {
    border-bottom: 1px solid var(--gray-200);
}

.tabs {
    display: flex;
    overflow-x: auto;
}

.tab-btn {
    background: none;
    border: none;
    padding: 1rem 1.5rem;
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--gray-600);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: var(--transition-fast);
    white-space: nowrap;
}

.tab-btn:hover {
    color: var(--primary);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

/* Sections de formulaire */
.form-section h3 {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    color: var(--gray-900);
    margin-bottom: 0.5rem;
}

.section-description {
    color: var(--gray-600);
    margin-bottom: 2rem;
    line-height: 1.5;
}

.profile-form {
    max-width: 600px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-help {
    font-size: var(--text-xs);
    color: var(--gray-500);
    margin-top: 0.25rem;
}

/* Préférences */
.preferences-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.preference-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    border: 1px solid var(--gray-200);
}

.preference-header h4 {
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    color: var(--gray-900);
    margin-bottom: 0.25rem;
}

.preference-header p {
    font-size: var(--text-sm);
    color: var(--gray-600);
}

/* Toggle Switch */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    flex-shrink: 0;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--gray-300);
    transition: var(--transition-fast);
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    top: 3px;
    background: var(--white);
    transition: var(--transition-fast);
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background: var(--primary);
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

/* Zone de danger */
.danger-zone {
    background: var(--white);
    border: 1px solid var(--error);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-top: 3rem;
}

.danger-zone h3 {
    color: var(--error);
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    margin-bottom: 0.5rem;
}

.danger-zone p {
    color: var(--gray-600);
    margin-bottom: 1.5rem;
}

/* Alertes */
.alert {
    padding: 1rem 1.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: var(--text-sm);
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.alert-icon {
    font-size: var(--text-lg);
}

/* Responsive */
@media (max-width: 768px) {
    .tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        text-align: left;
        border-bottom: 1px solid var(--gray-200);
        border-radius: 0;
    }
    
    .tab-btn:last-child {
        border-bottom: none;
    }
    
    .tab-content {
        padding: 1.5rem;
    }
    
    .preference-item {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
function showTab(tabId) {
    // Masquer tous les contenus d'onglets
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Désactiver tous les boutons d'onglets
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Afficher le contenu de l'onglet sélectionné
    document.getElementById(tabId).classList.add('active');
    
    // Activer le bouton de l'onglet correspondant
    event.target.classList.add('active');
}

function savePreferences() {
    // Collecter les préférences
    const preferences = {};
    document.querySelectorAll('.preference-item input[type="checkbox"]').forEach((checkbox, index) => {
        const preferenceNames = ['email_notifications', 'sms_notifications', 'promotional_offers', 'order_tracking'];
        preferences[preferenceNames[index]] = checkbox.checked;
    });
    
    // Simuler la sauvegarde
    showNotification('Préférences sauvegardées avec succès !', 'success');
}

function confirmDeleteAccount() {
    if (confirm('⚠️ ATTENTION !\n\nÊtes-vous vraiment sûr de vouloir supprimer votre compte ?\n\nCette action est IRRÉVERSIBLE et supprimera :\n- Toutes vos commandes\n- Votre historique d\'achats\n- Vos informations personnelles\n\nTapez "SUPPRIMER" pour confirmer :')) {
        const confirmation = prompt('Tapez "SUPPRIMER" en majuscules pour confirmer :');
        if (confirmation === 'SUPPRIMER') {
            alert('Fonctionnalité de suppression de compte en cours de développement.\n\nPour supprimer votre compte, veuillez nous contacter :\n📱 WhatsApp: +243 97 765 4321\n✉️ Email: info@kinshamarket.cd');
        }
    }
}

// Validation des mots de passe en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== newPassword.value) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 