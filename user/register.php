<?php
$page_title = "Inscription";
require_once '../includes/header.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: /ecommerce/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Tous les champs sont requis.';
    } elseif (!validateEmail($email)) {
        $error = 'Format d\'email invalide.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $existing_user = fetchOne("SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username]);
            
            if ($existing_user) {
                $error = 'Un compte avec cet email ou nom d\'utilisateur existe déjà.';
            } else {
                // Créer le compte
                $hashed_password = hashPassword($password);
                
                executeQuery("
                    INSERT INTO users (username, email, password, first_name, last_name, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ", [$username, $email, $hashed_password, $first_name, $last_name]);
                
                // Connecter automatiquement l'utilisateur après inscription
                $user_id = getLastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                
                // Redirection vers la page d'accueil
                redirectWithMessage('/ecommerce/index.php', 
                                   'Bienvenue ' . $first_name . ' ! Votre compte a été créé avec succès.', 
                                   'success');
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la création du compte. Veuillez réessayer.';
        }
    }
}
?>

<div class="auth-container" style="max-width: 500px; margin: 4rem auto; padding: 2rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
    <div class="text-center mb-3">
        <h1>Inscription</h1>
        <p style="color: var(--secondary-color);">Créez votre compte E-Shop</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
            <div class="mt-2">
                <a href="/ecommerce/user/login.php" class="btn btn-primary">Se connecter</a>
            </div>
        </div>
    <?php else: ?>

    <form method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="first_name" class="form-label">Prénom *</label>
                <input type="text" 
                       id="first_name" 
                       name="first_name" 
                       class="form-control" 
                       required 
                       value="<?php echo htmlspecialchars($first_name ?? ''); ?>"
                       placeholder="Jean">
            </div>

            <div class="form-group">
                <label for="last_name" class="form-label">Nom *</label>
                <input type="text" 
                       id="last_name" 
                       name="last_name" 
                       class="form-control" 
                       required 
                       value="<?php echo htmlspecialchars($last_name ?? ''); ?>"
                       placeholder="Dupont">
            </div>
        </div>

        <div class="form-group">
            <label for="username" class="form-label">Nom d'utilisateur *</label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   class="form-control" 
                   required 
                   value="<?php echo htmlspecialchars($username ?? ''); ?>"
                   placeholder="jean_dupont">
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email *</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control" 
                   required 
                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                   placeholder="jean.dupont@email.com">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Mot de passe *</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   class="form-control" 
                   required 
                   minlength="6"
                   placeholder="Au moins 6 caractères">
            <small style="color: var(--secondary-color);">Minimum 6 caractères</small>
        </div>

        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
            <input type="password" 
                   id="confirm_password" 
                   name="confirm_password" 
                   class="form-control" 
                   required 
                   placeholder="Répétez votre mot de passe">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: flex-start; gap: 0.5rem;">
                <input type="checkbox" name="terms" required style="margin-top: 0.2rem;">
                <span>J'accepte les <a href="/ecommerce/terms.php" target="_blank" style="color: var(--primary-color);">conditions d'utilisation</a> et la <a href="/ecommerce/privacy.php" target="_blank" style="color: var(--primary-color);">politique de confidentialité</a></span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Créer mon compte</button>
    </form>

    <div class="text-center mt-3">
        <p>Déjà un compte ? <a href="/ecommerce/user/login.php" style="color: var(--primary-color);">Se connecter</a></p>
    </div>

    <?php endif; ?>
</div>

<script>
// Validation du mot de passe en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            showFieldError(confirmPassword, 'Les mots de passe ne correspondent pas');
        } else {
            showFieldError(confirmPassword, '');
        }
    }
    
    password.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Force de mot de passe
    password.addEventListener('input', function() {
        const value = this.value;
        let strength = 0;
        let feedback = [];
        
        if (value.length >= 6) strength++;
        if (value.match(/[a-z]/)) strength++;
        if (value.match(/[A-Z]/)) strength++;
        if (value.match(/[0-9]/)) strength++;
        if (value.match(/[^a-zA-Z0-9]/)) strength++;
        
        const strengthTexts = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
        const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#20c997'];
        
        let strengthIndicator = document.querySelector('.password-strength');
        if (!strengthIndicator && value.length > 0) {
            strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            strengthIndicator.style.marginTop = '0.5rem';
            strengthIndicator.style.fontSize = '0.875rem';
            this.parentNode.appendChild(strengthIndicator);
        }
        
        if (strengthIndicator && value.length > 0) {
            strengthIndicator.innerHTML = `
                <div style="color: ${strengthColors[strength - 1]}">
                    Force: ${strengthTexts[strength - 1] || 'Très faible'}
                </div>
            `;
        } else if (strengthIndicator) {
            strengthIndicator.remove();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 