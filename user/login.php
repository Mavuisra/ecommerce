<?php
$page_title = "Connexion";
require_once '../includes/header.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: /ecommerce/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Tous les champs sont requis.';
    } elseif (!validateEmail($email)) {
        $error = 'Format d\'email invalide.';
    } else {
        try {
            $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Redirection
                $redirect = $_GET['redirect'] ?? '/index.php';
                redirectWithMessage($redirect, 'Connexion réussie ! Bienvenue ' . $user['first_name'], 'success');
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la connexion. Veuillez réessayer.';
        }
    }
}
?>

<div class="auth-container" style="max-width: 400px; margin: 4rem auto; padding: 2rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
    <div class="text-center mb-3">
        <h1>Connexion</h1>
        <p style="color: var(--secondary-color);">Connectez-vous à votre compte</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="email" class="form-label">Email *</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control" 
                   required 
                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                   placeholder="votre@email.com">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Mot de passe *</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   class="form-control" 
                   required 
                   placeholder="Votre mot de passe">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="remember" value="1">
                Se souvenir de moi
            </label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
    </form>

    <div class="text-center mt-3">
        <p>Pas encore de compte ? <a href="/ecommerce/user/register.php" style="color: var(--primary-color);">Créer un compte</a></p>
        <p><a href="/ecommerce/user/forgot_password.php" style="color: var(--secondary-color);">Mot de passe oublié ?</a></p>
    </div>
</div>

<!-- Demo credentials info -->

<script>
// Auto-focus sur le premier champ
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email').focus();
});

// Afficher/masquer le mot de passe
function togglePassword() {
    const passwordField = document.getElementById('password');
    const type = passwordField.type === 'password' ? 'text' : 'password';
    passwordField.type = type;
}
</script>

<?php require_once '../includes/footer.php'; ?> 