<?php
$page_title = "Home";
include 'includes/header.php';
?>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <video class="hero-video" autoplay muted loop playsinline>
            <!-- Updated Path with Cache Burst -->
            <source src="assets/images/hero.mp4?v=<?php echo time(); ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Indulge in Luxury</h1>
            <p>Experience the art of fine fragrance.</p>
            <a href="shop.php" class="btn-primary">Shop Our Collection</a>
        </div>
    </section>

    <!-- Featured Products Section (Static for now, can be Dynamic later) -->
    <section class="products" id="featured">
        <div class="section-header">
            <h2>The Collection</h2>
            <p class="section-desc">Handpicked fragrances for the elite.</p>
        </div>

<?php
        require_once 'config/db.php';
        require_once 'includes/functions.php';
        
        // Fetch Featured Products (Limit 3)
        // Group Concat for variants similar to Shop Page for consistent UX? 
        // For Home we can keep it simple: just show 'From ₹XXX' and link to product.
        // Actually, let's copy the logic from shop.php to support dynamic prices on home too if we want.
        // But for simplicity and elegance on home, let's just fetch basic info + min price.
        
        $sql = "SELECT p.*, 
                (SELECT price FROM product_variants pv WHERE pv.product_id = p.id ORDER BY price ASC LIMIT 1) as min_price,
                (SELECT sale_price FROM product_variants pv WHERE pv.product_id = p.id ORDER BY price ASC LIMIT 1) as min_sale_price,
                (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image
                FROM products p 
                WHERE p.is_active = 1 AND p.is_featured = 1 
                ORDER BY p.created_at DESC LIMIT 3";
        
        $stmt = $pdo->query($sql);
        $featured = $stmt->fetchAll();
        ?>

        <div class="product-grid">
            <?php foreach($featured as $product): 
                $price = $product['min_price'];
                $sale_price = $product['min_sale_price'];
                $image = $product['image'] ? $product['image'] : 'assets/images/placeholder.jpg';
            ?>
            <div class="product-card" onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'" style="cursor:pointer;">
                <div class="image-container">
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="price-box">
                         <?php if($sale_price): ?>
                            <span class="original-price">₹<?php echo number_format($price); ?></span>
                            <span class="sale-price">₹<?php echo number_format($sale_price); ?></span>
                        <?php else: ?>
                            <span class="sale-price">₹<?php echo number_format($price); ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Reuse add to cart logic or simple view button -->
                    <button class="btn-add-cart" onclick="event.stopPropagation(); addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $sale_price ? $sale_price : $price; ?>, '<?php echo htmlspecialchars($image); ?>', '50ml')">
                        ADD TO CART
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(count($featured) === 0): ?>
                <p style="text-align:center; width:100%;">No featured products yet. <a href="shop.php">Visit Shop</a>.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="features">
        <!-- Icons kept static as they are layout elements -->
        <div class="feature-item">
            <span>Free Shipping</span>
        </div>
        <div class="feature-item">
            <span>Secure Packaging</span>
        </div>
        <div class="feature-item">
            <span>24/7 Support</span>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>
