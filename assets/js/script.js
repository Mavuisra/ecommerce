// Variables globales
let cart = [];

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeCart();
    initializeForms();
    initializeSearch();
    initializeMobileMenu();
    initializeScrollEffects();
});

// Animations d'entrée
function initializeAnimations() {
    // Observer pour les animations au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // Observer tous les éléments avec la classe animate-on-scroll
    document.querySelectorAll('.card, .hero, .footer-section').forEach(el => {
        observer.observe(el);
    });

    // Animation des compteurs
    animateCounters();
}

// Animation des compteurs
function animateCounters() {
    const counters = document.querySelectorAll('[data-count]');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-count'));
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current);
        }, 16);
    });
}

// Gestion du menu mobile
function initializeMobileMenu() {
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('nav-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menu.classList.toggle('mobile-active');
            toggle.classList.toggle('active');
            toggle.innerHTML = menu.classList.contains('mobile-active') ? '✕' : '☰';
        });
        
        // Fermer le menu en cliquant à l'extérieur
        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                menu.classList.remove('mobile-active');
                toggle.classList.remove('active');
                toggle.innerHTML = '☰';
            }
        });
        
        // Fermer le menu sur les liens
        const menuLinks = menu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                menu.classList.remove('mobile-active');
                toggle.classList.remove('active');
                toggle.innerHTML = '☰';
            });
        });
    }
}

// Effets de scroll
function initializeScrollEffects() {
    const header = document.querySelector('.header');
    let lastScrollY = window.scrollY;

    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        
        // Header qui se cache/montre
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        
        // Effet de transparence du header
        if (currentScrollY > 50) {
            header.style.background = 'rgba(255, 255, 255, 0.98)';
            header.style.backdropFilter = 'blur(25px)';
        } else {
            header.style.background = 'rgba(255, 255, 255, 0.95)';
            header.style.backdropFilter = 'blur(20px)';
        }
        
        lastScrollY = currentScrollY;
    });

    // Bouton retour en haut
    const backToTop = createBackToTopButton();
    document.body.appendChild(backToTop);
}

// Créer le bouton retour en haut
function createBackToTopButton() {
    const button = document.createElement('button');
    button.innerHTML = '↑';
    button.className = 'back-to-top';
    button.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        width: 50px;
        height: 50px;
        background: var(--gradient-primary);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        z-index: 1000;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--box-shadow);
    `;

    // Afficher/masquer selon le scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            button.style.opacity = '1';
            button.style.transform = 'translateY(0)';
        } else {
            button.style.opacity = '0';
            button.style.transform = 'translateY(20px)';
        }
    });

    // Action du clic
    button.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Effet hover
    button.addEventListener('mouseenter', () => {
        button.style.transform = window.scrollY > 300 ? 'translateY(-5px) scale(1.1)' : 'translateY(15px) scale(1.1)';
    });

    button.addEventListener('mouseleave', () => {
        button.style.transform = window.scrollY > 300 ? 'translateY(0)' : 'translateY(20px)';
    });

    return button;
}

// Initialisation des formulaires
function initializeForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        // Validation en temps réel
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', debounce(validateField, 300));
        });

        // Soumission avec animation
        form.addEventListener('submit', handleFormSubmit);
    });
}

// Validation des champs
function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');

    // Supprimer les anciennes erreurs
    removeFieldError(field);

    // Validation selon le type
    let isValid = true;
    let errorMessage = '';

    if (required && !value) {
        isValid = false;
        errorMessage = 'Ce champ est obligatoire';
    } else if (value) {
        switch (type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Format d\'email invalide';
                }
                break;
            case 'tel':
                const phoneRegex = /^[\d\s\-\+\(\)]+$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Format de téléphone invalide';
                }
                break;
            case 'password':
                if (value.length < 6) {
                    isValid = false;
                    errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
                }
                break;
        }
    }

    // Afficher l'erreur ou le succès
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else if (value) {
        showFieldSuccess(field);
    }

    return isValid;
}

// Afficher une erreur sur un champ
function showFieldError(field, message) {
    field.style.borderColor = 'var(--danger-color)';
    field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: var(--danger-color);
        font-size: 0.875rem;
        margin-top: 0.5rem;
        animation: fadeInUp 0.3s ease-out;
    `;
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

// Afficher le succès sur un champ
function showFieldSuccess(field) {
    field.style.borderColor = 'var(--success-color)';
    field.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.1)';
}

// Supprimer l'erreur d'un champ
function removeFieldError(field) {
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Gestion de la soumission des formulaires
function handleFormSubmit(event) {
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    
    if (submitButton) {
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="loading"></span> Traitement...';
        submitButton.disabled = true;
        
        // Restaurer après 3 secondes si pas de redirection
        setTimeout(() => {
            if (submitButton) {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        }, 3000);
    }
}

// Initialisation de la recherche
function initializeSearch() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        // Recherche en temps réel avec debounce
        searchInput.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                performSearch(query);
            }
        }, 500));

        // Suggestions de recherche
        createSearchSuggestions(searchInput);
    }
}

// Créer les suggestions de recherche
function createSearchSuggestions(input) {
    const suggestions = [
        'Smartphone', 'Laptop', 'Chaussures', 'Livre', 'Montre',
        'Outils de jardin', 'iPhone', 'Samsung', 'Nike', 'Adidas'
    ];

    const suggestionsDiv = document.createElement('div');
    suggestionsDiv.className = 'search-suggestions';
    suggestionsDiv.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius-sm);
        box-shadow: var(--box-shadow);
        z-index: 1000;
        display: none;
        max-height: 200px;
        overflow-y: auto;
    `;

    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(suggestionsDiv);

    input.addEventListener('focus', () => {
        if (input.value.length >= 1) {
            showSuggestions(input, suggestions, suggestionsDiv);
        }
    });

    input.addEventListener('input', () => {
        if (input.value.length >= 1) {
            showSuggestions(input, suggestions, suggestionsDiv);
        } else {
            suggestionsDiv.style.display = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (!input.parentNode.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });
}

// Afficher les suggestions
function showSuggestions(input, suggestions, container) {
    const query = input.value.toLowerCase();
    const filtered = suggestions.filter(s => 
        s.toLowerCase().includes(query)
    ).slice(0, 5);

    if (filtered.length > 0) {
        container.innerHTML = filtered.map(suggestion => 
            `<div class="suggestion-item" style="padding: 0.75rem 1rem; cursor: pointer; transition: var(--transition);" 
                  onmouseover="this.style.background='rgba(240, 137, 2, 0.1)'" 
                  onmouseout="this.style.background='transparent'"
                  onclick="selectSuggestion('${suggestion}', '${input.id}')">${suggestion}</div>`
        ).join('');
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

// Sélectionner une suggestion  
function selectSuggestion(suggestion, inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.value = suggestion;
        input.focus();
        document.querySelector('.search-suggestions').style.display = 'none';
    }
}

// Recherche produits
function performSearch(query) {
    // Animation de recherche
    showLoadingIndicator();
    
    // Ici vous pouvez implémenter la recherche AJAX
    console.log('Recherche:', query);
    
    setTimeout(() => {
        hideLoadingIndicator();
    }, 1000);
}

// Initialisation du panier
function initializeCart() {
    updateCartDisplay();
    
    // Gestionnaires d'événements pour le panier
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
            e.preventDefault();
            const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
            addToCart(button);
        }
        
        if (e.target.classList.contains('remove-from-cart')) {
            e.preventDefault();
            removeFromCart(e.target.dataset.productId);
        }
        
        if (e.target.classList.contains('quantity-btn')) {
            e.preventDefault();
            updateQuantity(e.target);
        }
    });
}

// Ajouter au panier avec animation
function addToCart(button) {
    const productId = button.dataset.productId;
    const productName = button.dataset.productName;
    const productPrice = button.dataset.productPrice;
    
    if (!productId) return;
    
    // Animation du bouton
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span>';
    button.disabled = true;
    
    // Animation de l'icône vers le panier
    animateToCart(button);
    
    // Requête AJAX
    fetch('/ecommerce/cart/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body: `product_id=${productId}&quantity=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Succès - Mise à jour de l'affichage
            updateCartDisplay();
            showCartAddedPopup(data.product);
            
            // Animation de succès
            button.innerHTML = '✓ Ajouté';
            button.style.background = 'var(--success-color)';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
            
        } else {
            // Erreur
            showNotification(data.message || 'Erreur lors de l\'ajout', 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Animation vers le panier
function animateToCart(button) {
    const cartIcon = document.querySelector('.cart-icon');
    if (!cartIcon) return;
    
    const productImage = button.closest('.card').querySelector('img');
    if (!productImage) return;
    
    // Créer une copie de l'image pour l'animation
    const flyingImg = productImage.cloneNode();
    flyingImg.style.cssText = `
        position: fixed;
        top: ${productImage.getBoundingClientRect().top}px;
        left: ${productImage.getBoundingClientRect().left}px;
        width: ${productImage.offsetWidth}px;
        height: ${productImage.offsetHeight}px;
        z-index: 9999;
        pointer-events: none;
        transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        border-radius: var(--border-radius-sm);
    `;
    
    document.body.appendChild(flyingImg);
    
    // Animation vers le panier
    setTimeout(() => {
        const cartRect = cartIcon.getBoundingClientRect();
        flyingImg.style.transform = 'scale(0.3)';
        flyingImg.style.top = cartRect.top + 'px';
        flyingImg.style.left = cartRect.left + 'px';
        flyingImg.style.opacity = '0';
    }, 100);
    
    // Nettoyage
    setTimeout(() => {
        document.body.removeChild(flyingImg);
    }, 1000);
}

// Mettre à jour l'affichage du panier
function updateCartDisplay() {
    fetch('/ecommerce/cart/get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = data.count || 0;
            if (data.count > 0) {
                cartCount.style.display = 'flex';
                // Animation de pulse
                cartCount.style.animation = 'pulse 0.5s ease-out';
            } else {
                cartCount.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Erreur lors de la mise à jour du panier:', error));
}

// Mise à jour de la quantité
function updateQuantity(button) {
    const productId = button.dataset.productId;
    const action = button.dataset.action;
    const quantitySpan = button.parentNode.querySelector('.quantity');
    
    let currentQuantity = parseInt(quantitySpan.textContent);
    let newQuantity = action === 'increase' ? currentQuantity + 1 : currentQuantity - 1;
    
    if (newQuantity < 1) return;
    
    // Animation de chargement
    button.disabled = true;
    
    fetch('/ecommerce/cart/update_quantity.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        credentials: 'same-origin',
        body: `product_id=${productId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            quantitySpan.textContent = newQuantity;
            updateCartDisplay();
            
            // Mettre à jour le prix total si présent
            const priceElement = button.closest('.cart-item').querySelector('.item-total');
            if (priceElement && data.price) {
                priceElement.textContent = formatPrice(data.price * newQuantity);
            }
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'error');
        }
        button.disabled = false;
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de connexion', 'error');
        button.disabled = false;
    });
}

// Notifications toast
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        padding: 1rem 1.5rem;
        border-radius: var(--border-radius-sm);
        color: white;
        font-weight: 600;
        z-index: 10000;
        transform: translateX(100%);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--box-shadow);
        backdrop-filter: blur(10px);
        max-width: 350px;
    `;
    
    // Couleurs selon le type
    const colors = {
        success: 'var(--success-color)',
        error: 'var(--danger-color)',
        info: 'var(--primary-color)',
        warning: 'var(--warning-color)'
    };
    
    notification.style.background = colors[type] || colors.info;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Animation de sortie
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Indicateur de chargement
function showLoadingIndicator() {
    const loader = document.createElement('div');
    loader.id = 'global-loader';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;
    
    loader.innerHTML = '<div class="loading" style="width: 40px; height: 40px;"></div>';
    document.body.appendChild(loader);
}

function hideLoadingIndicator() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(loader);
        }, 300);
    }
}

// Utilitaires
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    showNotification('Une erreur inattendue s\'est produite', 'error');
});

// Gestion des erreurs de fetch
window.addEventListener('unhandledrejection', function(e) {
    console.error('Erreur de promesse non gérée:', e.reason);
    showNotification('Erreur de connexion', 'error');
});

// Performance - Lazy loading des images
function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Popup redesigné pour l'ajout au panier
function showCartAddedPopup(product) {
    // Créer le popup principal
    const popup = document.createElement('div');
    popup.className = 'cart-added-popup';
    popup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        color: #333;
        padding: 0;
        border-radius: 20px;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.05);
        z-index: 10001;
        max-width: 450px;
        width: 95%;
        opacity: 0;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow: hidden;
    `;
    
    // Créer l'overlay avec effet flou
    const overlay = document.createElement('div');
    overlay.className = 'cart-popup-overlay';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        z-index: 10000;
        opacity: 0;
        transition: all 0.3s ease;
    `;
    
    // Contenu du popup
    popup.innerHTML = `
        <!-- Header avec animation -->
        <div style="background: linear-gradient(135deg, #28a745, #20c997); padding: 2rem; text-align: center; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: ripple 2s infinite;"></div>
            <div style="position: relative; z-index: 2;">
                <div style="width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 1rem; animation: bounce 0.6s ease-out;">
                    ✅
                </div>
                <h2 style="margin: 0; font-size: 1.5rem; font-weight: 800; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    Ajouté avec succès !
                </h2>
                <p style="margin: 0.5rem 0 0 0; opacity: 0.9; font-size: 1rem; color: white;">
                    Votre article a été ajouté au panier
                </p>
            </div>
        </div>
        
        <!-- Détails du produit -->
        <div style="padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; background: #f8f9fa; border-radius: 15px; padding: 1rem; margin-bottom: 1.5rem; border: 1px solid #e9ecef;">
                <div style="position: relative;">
                    <img src="/ecommerce/assets/images/${product.image || 'default-product.jpg'}" 
                         alt="${product.name}"
                         style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
                         onerror="this.src='/ecommerce/assets/images/default-product.jpg'">
                    <div style="position: absolute; top: -8px; right: -8px; background: #28a745; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; box-shadow: 0 2px 6px rgba(40, 167, 69, 0.3);">
                        ${product.quantity}
                    </div>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; font-weight: 700; color: #333; line-height: 1.3;">
                        ${product.name}
                    </h4>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9rem; color: #666; font-weight: 500;">
                            ${product.quantity} × ${formatPriceFC(product.price)}
                        </span>
                        <span style="font-weight: 800; font-size: 1.2rem; color: #28a745;">
                            ${formatPriceFC(product.price * product.quantity)}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Boutons d'action -->
            <div style="display: flex; gap: 0.75rem;">
                <button onclick="closeCartPopup()" 
                        class="popup-btn-secondary"
                        style="flex: 1; background: #f8f9fa; color: #666; border: 2px solid #e9ecef; padding: 0.875rem 1rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 0.95rem;">
                    Continuer les achats
                </button>
                <button onclick="goToCart()" 
                        class="popup-btn-primary"
                        style="flex: 1; background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; padding: 0.875rem 1rem; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-size: 0.95rem; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);">
                    🛒 Voir le panier
                </button>
            </div>
        </div>
        
        <!-- Barre de progression pour la fermeture automatique -->
        <div style="height: 4px; background: #e9ecef; position: relative; overflow: hidden;">
            <div class="auto-close-bar" style="height: 100%; background: linear-gradient(90deg, #28a745, #20c997); width: 100%; transform: translateX(-100%); animation: progressBar 5s linear forwards;"></div>
        </div>
    `;
    
    // Ajouter au DOM
    document.body.appendChild(overlay);
    document.body.appendChild(popup);
    
    // Animation d'ouverture avec délai pour un effet plus fluide
    requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        setTimeout(() => {
            popup.style.opacity = '1';
            popup.style.transform = 'translate(-50%, -50%) scale(1)';
        }, 100);
    });
    
    // Fermer automatiquement après 5 secondes
    setTimeout(() => {
        closeCartPopup();
    }, 5000);
    
    // Fermer en cliquant sur l'overlay
    overlay.addEventListener('click', closeCartPopup);
    
    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
    
    // Ajouter les animations CSS
    addPopupAnimations();
}

// Fermer le popup du panier avec animation améliorée
function closeCartPopup() {
    const popup = document.querySelector('.cart-added-popup');
    const overlay = document.querySelector('.cart-popup-overlay');
    
    if (popup && overlay) {
        // Animation de fermeture
        popup.style.opacity = '0';
        popup.style.transform = 'translate(-50%, -50%) scale(0.8)';
        overlay.style.opacity = '0';
        
        // Nettoyage après animation
        setTimeout(() => {
            if (document.body.contains(popup)) {
                document.body.removeChild(popup);
            }
            if (document.body.contains(overlay)) {
                document.body.removeChild(overlay);
            }
            document.body.style.overflow = '';
        }, 400);
    }
}

// Aller au panier
function goToCart() {
    closeCartPopup();
    window.location.href = '/ecommerce/cart/view_cart.php';
}

// Formater le prix en francs congolais
function formatPriceFC(price) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price) + ' FC';
}

// Ajouter les animations CSS pour le popup
function addPopupAnimations() {
    // Vérifier si les styles existent déjà
    if (document.getElementById('popup-animations')) return;
    
    const style = document.createElement('style');
    style.id = 'popup-animations';
    style.textContent = `
        @keyframes bounce {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes ripple {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(4); opacity: 0; }
        }
        
        @keyframes progressBar {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(0); }
        }
        
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .cart-added-popup {
            animation: fadeInUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        
        .popup-btn-secondary:hover {
            background: #e9ecef !important;
            border-color: #dee2e6 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .popup-btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #004085) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4) !important;
        }
        
        .popup-btn-secondary:active,
        .popup-btn-primary:active {
            transform: translateY(0);
        }
        
        .cart-popup-overlay {
            animation: fadeIn 0.3s ease forwards;
        }
        
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        
        /* Responsive design */
        @media (max-width: 480px) {
            .cart-added-popup {
                max-width: 95% !important;
                margin: 0 10px;
            }
            
            .cart-added-popup h2 {
                font-size: 1.3rem !important;
            }
            
            .cart-added-popup h4 {
                font-size: 1rem !important;
            }
        }
    `;
    
    document.head.appendChild(style);
}

// Gestionnaires d'événements pour les boutons du popup
document.addEventListener('click', function(e) {
    // Bouton secondaire (continuer)
    if (e.target.classList.contains('popup-btn-secondary')) {
        e.target.style.transform = 'translateY(1px)';
        setTimeout(() => {
            e.target.style.transform = '';
        }, 150);
    }
    
    // Bouton primaire (voir panier)
    if (e.target.classList.contains('popup-btn-primary')) {
        e.target.style.transform = 'translateY(1px)';
        setTimeout(() => {
            e.target.style.transform = '';
        }, 150);
    }
});

// Initialiser le lazy loading
document.addEventListener('DOMContentLoaded', initializeLazyLoading); 