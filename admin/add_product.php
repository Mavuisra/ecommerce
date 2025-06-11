<?php
$page_title = "Ajouter un produit - Administration";
require_once '../includes/header.php';

// Vérifier les droits admin
requireAdmin();

// Récupérer les catégories pour le formulaire
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

// Traitement du formulaire
if ($_POST) {
    $errors = [];
    
    // Vérifier le token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token de sécurité invalide.";
    }
    
    // Valider les données
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $dimensions = sanitize($_POST['dimensions'] ?? '');
    $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
    
    // Validation des champs obligatoires
    if (empty($name)) {
        $errors[] = "Le nom du produit est obligatoire.";
    }
    if (empty($description)) {
        $errors[] = "La description est obligatoire.";
    }
    if ($price <= 0) {
        $errors[] = "Le prix doit être supérieur à 0.";
    }
    if ($stock_quantity < 0) {
        $errors[] = "La quantité en stock ne peut pas être négative.";
    }
    
    // Gestion de l'upload d'image
    $image_filename = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            $image_filename = uploadImage($_FILES['image'], '../assets/images/');
            if (!$image_filename) {
                $errors[] = "Erreur lors de l'upload de l'image.";
            }
        } catch (Exception $e) {
            $errors[] = "Erreur image : " . $e->getMessage();
        }
    }
    
    // Si pas d'erreurs, insérer le produit
    if (empty($errors)) {
        try {
            $query = "INSERT INTO products (name, description, price, stock_quantity, category_id, image, weight, dimensions, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $name,
                $description,
                $price,
                $stock_quantity,
                $category_id > 0 ? $category_id : null,
                $image_filename,
                $weight > 0 ? $weight : null,
                !empty($dimensions) ? $dimensions : null,
                $status
            ];
            
            executeQuery($query, $params);
            
            redirectWithMessage('/ecommerce/admin/dashboard.php', 
                               'Produit ajouté avec succès !', 
                               'success');
        } catch (Exception $e) {
            $errors[] = "Erreur lors de l'ajout du produit : " . $e->getMessage();
        }
    }
}
?>

<div class="admin-page">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>📦 Ajouter un nouveau produit</h1>
            <p style="color: var(--secondary-color);">Remplissez les informations du produit</p>
        </div>
        <div>
            <a href="/ecommerce/admin/dashboard.php" class="btn btn-outline">← Retour au tableau de bord</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" style="margin-bottom: 2rem;">
            <h4>Erreurs détectées :</h4>
            <ul style="margin: 0; padding-left: 1.5rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Informations de base -->
                <div class="form-section" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">ℹ️ Informations de base</h3>
                    
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="name" class="form-label">
                                Nom du produit <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-control" 
                                   required
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   placeholder="Ex: Smartphone Samsung Galaxy S24">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">Catégorie</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">-- Sélectionner une catégorie --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            <?php echo (($_POST['category_id'] ?? 0) == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">
                            Description <span style="color: var(--danger-color);">*</span>
                        </label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="4" 
                                  required
                                  placeholder="Décrivez les caractéristiques et avantages du produit..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Pricing et stock -->
                <div class="form-section" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">💰 Prix et stock</h3>
                    
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="price" class="form-label">
                                Prix (FC) <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   class="form-control" 
                                   step="0.01" 
                                   min="0" 
                                   required
                                   value="<?php echo $_POST['price'] ?? ''; ?>"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity" class="form-label">
                                Quantité en stock <span style="color: var(--danger-color);">*</span>
                            </label>
                            <input type="number" 
                                   id="stock_quantity" 
                                   name="stock_quantity" 
                                   class="form-control" 
                                   min="0" 
                                   required
                                   value="<?php echo $_POST['stock_quantity'] ?? ''; ?>"
                                   placeholder="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Statut</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?php echo (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>
                                    ✅ Actif
                                </option>
                                <option value="inactive" <?php echo (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>
                                    ❌ Inactif
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Image et spécifications -->
                <div class="form-section" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">🖼️ Image et spécifications</h3>
                    
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="image" class="form-label">Image du produit</label>
                            <input type="file" 
                                   id="image" 
                                   name="image" 
                                   class="form-control" 
                                   accept="image/*">
                            <small class="form-text">
                                Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="weight" class="form-label">Poids (kg)</label>
                            <input type="number" 
                                   id="weight" 
                                   name="weight" 
                                   class="form-control" 
                                   step="0.01" 
                                   min="0"
                                   value="<?php echo $_POST['weight'] ?? ''; ?>"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="dimensions" class="form-label">Dimensions</label>
                            <input type="text" 
                                   id="dimensions" 
                                   name="dimensions" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['dimensions'] ?? ''); ?>"
                                   placeholder="Ex: 15 x 8 x 1 cm">
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                    <a href="/ecommerce/admin/dashboard.php" class="btn btn-outline">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        📦 Ajouter le produit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.product-form .form-section {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    border-left: 4px solid var(--primary-color);
}

.product-form .form-group {
    display: flex;
    flex-direction: column;
}

.product-form .form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

.product-form .form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
}

.product-form .form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(66, 139, 202, 0.1);
}

.product-form .form-text {
    color: var(--secondary-color);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr !important;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .page-header {
        flex-direction: column !important;
        text-align: center;
        gap: 1rem;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?> 