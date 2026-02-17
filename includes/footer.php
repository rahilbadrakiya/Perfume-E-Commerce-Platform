    <!-- Footer -->
    <footer>
        <span class="brand">K.M. FRAGRANCES.</span>
        <p>Rajkot, Gujarat</p>
        <div class="footer-links" style="margin: 20px 0; display:flex; gap:15px; justify-content:center; flex-wrap:wrap;">
            <a href="about.php" style="color:#777; text-decoration:none;">About Us</a>
            <a href="contact.php" style="color:#777; text-decoration:none;">Contact</a>
            <a href="policies.php" style="color:#777; text-decoration:none;">Privacy Policy</a>
            <a href="policies.php" style="color:#777; text-decoration:none;">Terms & Conditions</a>
        </div>
        <p class="copyright">&copy; <?php echo date('Y'); ?> K.M. Fragrances. Use with Discretion.</p>
    </footer>

    <!-- Cart Modal (Structure Only, populated via JS/AJAX) -->
    <div class="cart-overlay" id="cartOverlay">
        <div class="cart-modal">
            <div class="cart-header">
                <h3>Your Selection</h3>
                <button class="close-cart" onclick="toggleCart()">&times;</button>
            </div>
            <div class="cart-items" id="cartItems">
                <!-- Cart items will be injected here via JS or PHP reload -->
                <p style="text-align:center; margin-top:20px; color:#888;">Loading cart...</p>
            </div>
            <div class="cart-footer">
                <div class="total-row">
                    <span>Total</span>
                    <span id="cartTotal">₹0</span>
                </div>
                <a href="checkout.php" class="btn-checkout" style="text-decoration:none; text-align:center;">
                    PROCEED TO CHECKOUT
                </a>
            </div>
        </div>
    </div>

    <!-- Updated Script Path -->
    <!-- Updated Script Path with Cache Busting -->
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
        function toggleSearch() {
            const el = document.getElementById('searchOverlay');
            el.style.display = el.style.display === 'none' ? 'flex' : 'none';
        }
    </script>
</body>
</html>
