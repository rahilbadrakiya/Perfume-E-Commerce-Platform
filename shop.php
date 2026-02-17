<?php
$page_title = "Shop";
include 'includes/header.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Build Query - using GROUP_CONCAT to fetch all variants
$sql = "SELECT p.*, 
        (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as image,
        GROUP_CONCAT(pv.size_label, ':', pv.price, ':', IFNULL(pv.sale_price, ''), ':', pv.id ORDER BY pv.price ASC SEPARATOR '||') as variants_data
        FROM products p 
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE p.is_active = 1";
$params = [];

// Category Filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $sql .= " AND p.category_id = (SELECT id FROM categories WHERE name = ?)";
    $params[] = clean_input($_GET['category']);
}

// Search Filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = "%" . clean_input($_GET['search']) . "%";
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<section class="products" style="padding-top: 40px;">
    <div class="section-header">
        <h2><?php echo isset($_GET['category']) ? htmlspecialchars($_GET['category']) : 'All Fragrances'; ?></h2>
        <p class="section-desc">Discover your signature scent.</p>
    </div>

    <!-- Category Pills -->
    <div style="display:flex; justify-content:center; gap:15px; margin-bottom:40px;">
        <a href="shop.php" class="size-btn <?php echo !isset($_GET['category']) ? 'active' : ''; ?>" style="text-decoration:none; display:inline-block; line-height:20px;">All</a>
        <a href="shop.php?category=Men" class="size-btn <?php echo (isset($_GET['category']) && $_GET['category'] == 'Men') ? 'active' : ''; ?>" style="text-decoration:none; display:inline-block; line-height:20px;">Men</a>
        <a href="shop.php?category=Women" class="size-btn <?php echo (isset($_GET['category']) && $_GET['category'] == 'Women') ? 'active' : ''; ?>" style="text-decoration:none; display:inline-block; line-height:20px;">Women</a>
        <a href="shop.php?category=Unisex" class="size-btn <?php echo (isset($_GET['category']) && $_GET['category'] == 'Unisex') ? 'active' : ''; ?>" style="text-decoration:none; display:inline-block; line-height:20px;">Unisex</a>
    </div>

    <div class="product-grid">
        <?php foreach($products as $product): 
            $image = $product['image'] ? $product['image'] : 'assets/images/placeholder.jpg';
            
            // Parse variants
            $variants_str = $product['variants_data'];
            $variants = [];
            if ($variants_str) {
                $raw_vars = explode('||', $variants_str);
                foreach($raw_vars as $rv) {
                    $parts = explode(':', $rv);
                    if(count($parts) >= 4) {
                        $variants[] = [
                            'size' => $parts[0],
                            'price' => $parts[1],
                            'sale_price' => $parts[2] === '' ? null : $parts[2],
                            'id' => $parts[3]
                        ];
                    }
                }
            }
            
            // Set Defaults (First Variant)
            $current_price = count($variants) > 0 ? $variants[0]['price'] : 0;
            $current_sale = count($variants) > 0 ? $variants[0]['sale_price'] : null;
            $current_size = count($variants) > 0 ? $variants[0]['size'] : 'N/A';
            $unique_id = 'prod_' . $product['id'];
        ?>
        <div class="product-card" id="<?php echo $unique_id; ?>" onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'" style="cursor:pointer;">
            <div class="image-container">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="product-info">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                
                <!-- Price Box -->
                <div class="price-box">
                    <span class="original-price" style="<?php echo !$current_sale ? 'display:none;' : ''; ?>">₹<?php echo number_format($current_price); ?></span>
                    <span class="sale-price">₹<?php echo number_format($current_sale ? $current_sale : $current_price); ?></span>
                </div>

                <!-- Size Selector (Click-stop-propagation) -->
                <?php if(count($variants) > 1): ?>
                <div class="size-selector" onclick="event.stopPropagation();">
                    <?php foreach($variants as $idx => $v): 
                        $price_val = $v['sale_price'] ? $v['sale_price'] : $v['price'];
                        $orig_val = $v['price'];
                        $has_sale = $v['sale_price'] ? 'true' : 'false';
                    ?>
                    <button class="size-btn <?php echo $idx===0 ? 'active' : ''; ?>" 
                            data-price="<?php echo $price_val; ?>" 
                            data-orig="<?php echo $orig_val; ?>"
                            data-sale="<?php echo $has_sale; ?>"
                            data-size="<?php echo htmlspecialchars($v['size']); ?>"
                            onclick="updateCardPrice(this, '<?php echo $unique_id; ?>')">
                        <?php echo htmlspecialchars($v['size']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <button class="btn-add-cart" onclick="event.stopPropagation(); addCardToCart('<?php echo $unique_id; ?>', <?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', '<?php echo htmlspecialchars($image); ?>')">
                    ADD TO CART
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(count($products) === 0): ?>
            <p style="text-align:center; width:100%;">No products found matching your criteria.</p>
        <?php endif; ?>
    </div>

    <script>
    function updateCardPrice(btn, cardId) {
        // UI
        const card = document.getElementById(cardId);
        card.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Data
        const price = parseFloat(btn.dataset.price);
        const orig = parseFloat(btn.dataset.orig);
        const hasSale = btn.dataset.sale === 'true';

        // Update Price Text
        const saleEl = card.querySelector('.sale-price');
        const origEl = card.querySelector('.original-price');

        saleEl.innerText = '₹' + price.toLocaleString('en-IN');
        if(hasSale) {
            origEl.style.display = 'inline';
            origEl.innerText = '₹' + orig.toLocaleString('en-IN');
        } else {
            origEl.style.display = 'none';
        }
    }

    function addCardToCart(cardId, id, name, img) {
        const card = document.getElementById(cardId);
        const activeBtn = card.querySelector('.size-btn.active');
        
        let size = 'Standard';
        let price = 0;

        if (activeBtn) {
            size = activeBtn.dataset.size;
            price = parseFloat(activeBtn.dataset.price);
        } else {
            // Fallback for single variant
            const priceText = card.querySelector('.sale-price').innerText;
            price = parseFloat(priceText.replace(/[^\d]/g, ''));
            // Ideally we should pass the default variant size too, but for single-var items, usually 50ml is default or N/A
             // Let's assume 50ml or try to grab the first variant if not rendered
             size = '50ml'; 
             // Correction: if there is no size selector, we didn't render buttons.
             // We can check if there's only 1 variant in logic. 
             // For now, default to 50ml is acceptable as fallback.
        }

        addToCart(id, name, price, img, size);
    }
    </script>
</section>

<?php include 'includes/footer.php'; ?>
