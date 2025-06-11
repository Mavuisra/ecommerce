<?php
$page_title = "Administration - Connexion";
require_once '../includes/header.php';

// Rediriger si déjà connecté en tant qu'admin
if (isAdmin()) {
    header('Location: /ecommerce/admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Tous les champs sont requis.';
    } else {
        try {
            $user = fetchOne("SELECT * FROM users WHERE email = ? AND role = 'admin'", [$email]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                redirectWithMessage('/ecommerce/admin/dashboard.php', 'Connexion administrative réussie !', 'success');
            } else {
                $error = 'Identifiants administrateur incorrects.';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la connexion.';
        }
    }
}
?>

<div class="admin-login" style="max-width: 400px; margin: 4rem auto; padding: 2rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow);">
    <div class="text-center mb-3">
        <div style="font-size: 3rem; margin-bottom: 1rem;">⚙️</div>
        <h1>Administration</h1>
        <p style="color: var(--secondary-color);">Accès réservé aux administrateurs</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" data-validate>
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="email" class="form-label">Email administrateur *</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control" 
                   required 
                   value="<?php echo htmlspecialchars($email ?? ''); ?>"
                   placeholder="admin@example.com">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Mot de passe *</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   class="form-control" 
                   required 
                   placeholder="Mot de passe administrateur">
        </div>

        <button type="submit" class="btn btn-primary w-100">Accéder à l'administration</button>
    </form>

    <div class="text-center mt-3">
        <a href="/ecommerce/index.php" style="color: var(--secondary-color);">← Retour au site</a>
    </div>

    <!-- Informations de test -->
    <div class="demo-info" style="margin-top: 2rem; padding: 1rem; background: var(--light-color); border-radius: var(--border-radius); font-size: 0.9rem;">
        <strong>Compte administrateur de test :</strong><br>
        Email: admin@ecommerce.com<br>
        Mot de passe: password
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 