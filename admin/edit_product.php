<?php
$page_title = "Modifier un produit - Administration";
require_once '../includes/header.php';

// Vérifier les droits admin
requireAdmin();

// Récupérer l'ID du produit
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    redirectWithMessage('/ecommerce/admin/manage_products.php', 
                       'ID de produit invalide.', 
                       'error');
}

// Récupérer les données du produit
$product = fetchOne("SELECT * FROM products WHERE id = ?", [$product_id]);

if (!$product) {
    redirectWithMessage('/ecommerce/admin/manage_products.php', 
                       'Produit introuvable.', 
                       'error');
}

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
    $image_filename = $product['image']; // Garder l'image actuelle par défaut
    $delete_old_image = false;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            $new_image = uploadImage($_FILES['image'], '../assets/images/');
            if ($new_image) {
                $delete_old_image = !empty($product['image']); // Marquer l'ancienne image pour suppression
                $image_filename = $new_image;
            } else {
                $errors[] = "Erreur lors de l'upload de la nouvelle image.";
            }
        } catch (Exception $e) {
            $errors[] = "Erreur image : " . $e->getMessage();
        }
    }
    
    // Supprimer l'image si demandé
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        $delete_old_image = !empty($product['image']);
        $image_filename = null;
    }
    
    // Si pas d'erreurs, mettre à jour le produit
    if (empty($errors)) {
        try {
            $query = "UPDATE products SET 
                        name = ?, 
                        description = ?, 
                        price = ?, 
                        stock_quantity = ?, 
                        category_id = ?, 
                        image = ?, 
                        weight = ?, 
                        dimensions = ?, 
                        status = ?,
                        updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?";
            
            $params = [
                $name,
                $description,
                $price,
                $stock_quantity,
                $category_id > 0 ? $category_id : null,
                $image_filename,
                $weight > 0 ? $weight : null,
                !empty($dimensions) ? $dimensions : null,
                $status,
                $product_id
            ];
            
            executeQuery($query, $params);
            
            // Supprimer l'ancienne image si nécessaire
            if ($delete_old_image && file_exists('../assets/images/' . $product['image'])) {
                unlink('../assets/images/' . $product['image']);
            }
            
            redirectWithMessage('/ecommerce/admin/manage_products.php', 
                               'Produit modifié avec succès !', 
                               'success');
        } catch (Exception $e) {
            $errors[] = "Erreur lors de la modification du produit : " . $e->getMessage();
        }
    } else {
        // Si erreur, recharger les données du produit pour l'affichage
        $product = array_merge($product, $_POST);
    }
}
?>

<div class="admin-page">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>✏️ Modifier le produit</h1>
            <p style="color: var(--secondary-color);">
                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
            </p>
        </div>
        <div>
            <a href="/ecommerce/products/details.php?id=<?php echo $product_id; ?>" class="btn btn-outline" target="_blank">
                👁️ Voir le produit
            </a>
            <a href="/ecommerce/admin/manage_products.php" class="btn btn-outline">← Retour à la liste</a>
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
                                   value="<?php echo htmlspecialchars($product['name']); ?>"
                                   placeholder="Ex: Smartphone Samsung Galaxy S24">
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id" class="form-label">Catégorie</label>
                            <select id="category_id" name="category_id" class="form-control">
                                <option value="">-- Sélectionner une catégorie --</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                                  placeholder="Décrivez les caractéristiques et avantages du produit..."><?php echo htmlspecialchars($product['description']); ?></textarea>
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
                                   value="<?php echo $product['price']; ?>"
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
                                   value="<?php echo $product['stock_quantity']; ?>"
                                   placeholder="0">
                            <?php if ($product['stock_quantity'] <= 5): ?>
                                <small class="form-text" style="color: var(--danger-color);">
                                    ⚠️ Stock faible ! Pensez à réapprovisionner.
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Statut</label>
                            <select id="status" name="status" class="form-control">
                                <option value="active" <?php echo ($product['status'] === 'active') ? 'selected' : ''; ?>>
                                    ✅ Actif
                                </option>
                                <option value="inactive" <?php echo ($product['status'] === 'inactive') ? 'selected' : ''; ?>>
                                    ❌ Inactif
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Image et spécifications -->
                <div class="form-section" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">🖼️ Image et spécifications</h3>
                    
                    <!-- Image actuelle -->
                    <?php if (!empty($product['image'])): ?>
                        <div class="current-image" style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: var(--border-radius); border: 1px solid #ddd;">
                            <h4 style="margin-bottom: 1rem;">Image actuelle :</h4>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <img src="/ecommerce/assets/images/<?php echo $product['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd;"
                                     onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                                <div>
                                    <p style="margin: 0; font-weight: 500;"><?php echo $product['image']; ?></p>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="remove_image" value="1" id="remove_image">
                                        <span style="color: var(--danger-color);">🗑️ Supprimer cette image</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="image" class="form-label">
                                <?php echo !empty($product['image']) ? 'Nouvelle image (optionnelle)' : 'Image du produit'; ?>
                            </label>
                            <input type="file" 
                                   id="image" 
                                   name="image" 
                                   class="form-control" 
                                   accept="image/*">
                            <small class="form-text">
                                Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)
                                <?php if (!empty($product['image'])): ?>
                                    <br>Laissez vide pour conserver l'image actuelle
                                <?php endif; ?>
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
                                   value="<?php echo $product['weight']; ?>"
                                   placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="dimensions" class="form-label">Dimensions</label>
                            <input type="text" 
                                   id="dimensions" 
                                   name="dimensions" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($product['dimensions']); ?>"
                                   placeholder="Ex: 15 x 8 x 1 cm">
                        </div>
                    </div>
                </div>

                <!-- Informations de suivi -->
                <div class="form-section" style="margin-bottom: 2rem; background: #e9ecef;">
                    <h3 style="margin-bottom: 1rem; color: var(--secondary-color);">📊 Informations de suivi</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem; color: var(--secondary-color);">
                        <div>
                            <strong>Créé le :</strong> <?php echo date('d/m/Y à H:i', strtotime($product['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Dernière modification :</strong> <?php echo date('d/m/Y à H:i', strtotime($product['updated_at'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                    <a href="/ecommerce/admin/manage_products.php" class="btn btn-outline">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        ✏️ Sauvegarder les modifications
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

.current-image {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#remove_image:checked + span {
    font-weight: bold;
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
    
    .current-image > div {
        flex-direction: column !important;
        text-align: center;
    }
}
</style>

<script>
// Preview de la nouvelle image sélectionnée
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Créer un aperçu si il n'existe pas déjà
            let preview = document.getElementById('new-image-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.id = 'new-image-preview';
                preview.style.cssText = 'margin-top: 1rem; padding: 1rem; background: white; border-radius: 8px; border: 2px dashed var(--primary-color);';
                e.target.parentNode.appendChild(preview);
            }
            
            preview.innerHTML = `
                <h5 style="margin-bottom: 0.5rem; color: var(--primary-color);">📎 Nouvelle image sélectionnée :</h5>
                <img src="${e.target.result}" alt="Aperçu" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: var(--secondary-color);">${file.name}</p>
            `;
        };
        reader.readAsDataURL(file);
    }
});

// Gestion de la suppression d'image
document.getElementById('remove_image')?.addEventListener('change', function(e) {
    const currentImageDiv = document.querySelector('.current-image');
    if (e.target.checked) {
        currentImageDiv.style.opacity = '0.5';
        currentImageDiv.style.filter = 'grayscale(1)';
    } else {
        currentImageDiv.style.opacity = '1';
        currentImageDiv.style.filter = 'none';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 