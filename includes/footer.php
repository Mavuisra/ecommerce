        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>À propos de KinshaMarket</h3>
                    <p>KinshaMarket est la première plateforme d'achat en ligne en République Démocratique du Congo. Nous connectons les acheteurs et vendeurs de Kinshasa pour faciliter le commerce électronique local.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook">📘</a>
                        <a href="#" aria-label="WhatsApp">📱</a>
                        <a href="#" aria-label="Instagram">📷</a>
                        <a href="#" aria-label="Twitter">🐦</a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Navigation</h3>
                    <a href="/ecommerce/index.php">Accueil</a>
                    <a href="/ecommerce/products/index.php">Tous les produits</a>
                    <a href="/ecommerce/about.php">À propos</a>
                    <a href="/ecommerce/contact.php">Nous contacter</a>
                    <a href="/ecommerce/terms.php">Conditions d'utilisation</a>
                    <a href="/ecommerce/privacy.php">Politique de confidentialité</a>
                </div>
                
                <div class="footer-section">
                    <h3>Nos Catégories</h3>
                    <?php
                    $categories = fetchAll("SELECT * FROM categories ORDER BY name LIMIT 6");
                    foreach ($categories as $category):
                    ?>
                        <a href="/ecommerce/products/index.php?category=<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a>
                    <?php endforeach; ?>
                </div>
                
                <div class="footer-section">
                    <h3>Contact & Livraison</h3>
                    <p>📞 +243 81 234 5678</p>
                    <p>📱 WhatsApp: +243 97 765 4321</p>
                    <p>✉️ info@kinshamarket.cd</p>
                    <p>🕒 Lun-Sam: 8h-18h</p>
                    <p>📍 Avenue Kasa-Vubu, Commune de Kalamu<br>Kinshasa, République Démocratique du Congo</p>
                    <p style="margin-top: 1rem; font-weight: bold; color: var(--primary);">🚚 Livraison dans toutes les communes de Kinshasa</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #555;">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> KinshaMarket. Tous droits réservés. - Marketplace N°1 en RDC</p>
                    <div class="payment-methods">
                        <span>💳 Moyens de paiement:</span>
                        <span>📱 Orange Money</span>
                        <span>📱 Airtel Money</span>
                        <span>💵 Cash à la livraison</span>
                        <span>🏦 Virement bancaire</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/ecommerce/assets/js/script.js"></script>
    
    <!-- Scripts additionnels selon la page -->
    <?php if (isset($additional_scripts)): ?>
        <?php foreach ($additional_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inline_scripts)): ?>
        <script>
            <?php echo $inline_scripts; ?>
        </script>
    <?php endif; ?>
</body>
</html> 