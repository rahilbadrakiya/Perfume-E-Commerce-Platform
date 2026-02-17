<?php
$page_title = "Product Details";
include 'includes/header.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    echo "<p style='text-align:center; padding:50px;'>Invalid Product.</p>";
    include 'includes/footer.php';
    exit;
}

$id = (int)$_GET['id'];

// 1. Fetch Product
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<p style='text-align:center; padding:50px;'>Product not found.</p>";
    include 'includes/footer.php';
    exit;
}

// 2. Fetch Variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$stmt->execute([$id]);
$variants = $stmt->fetchAll();

// 3. Fetch Images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();
?>

<div class="product-detail-container" style="max-width:1200px; margin:40px auto; padding:0 20px; display:grid; grid-template-columns: 1fr 1fr; gap:50px;">
    
    <!-- Left: Gallery -->
    <div class="product-gallery">
        <?php if(count($images) > 0): ?>
            <div class="main-image" style="background:#f9f9f9; padding:20px; margin-bottom:20px; text-align:center;">
                <img id="mainImg" src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width:100%; height:auto; max-height:500px;">
            </div>
            <div class="thumbnail-list" style="display:flex; gap:10px; overflow-x:auto;">
                <?php foreach($images as $img): ?>
                <div onclick="document.getElementById('mainImg').src='<?php echo htmlspecialchars($img['image_path']); ?>'" style="cursor:pointer; border:1px solid #eee; padding:5px; width:80px; height:80px; flex-shrink:0;">
                    <img src="<?php echo htmlspecialchars($img['image_path']); ?>" style="width:100%; height:100%; object-fit:contain;">
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <img src="assets/images/placeholder.jpg" alt="No Image">
        <?php endif; ?>
    </div>

    <!-- Right: Info -->
    <div class="product-info-detail">
        <span style="font-size:14px; color:#888; text-transform:uppercase; letter-spacing:1px;"><?php echo htmlspecialchars($product['brand']); ?></span>
        <h1 style="font-family:'Marcellus', serif; font-size:36px; margin:10px 0;"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <!-- Dynamic Price -->
        <div class="price-area" style="font-size:24px; font-weight:500; margin-bottom:20px;">
            <span id="displayPrice">₹<?php echo number_format($variants[0]['price']); ?></span>
        </div>

        <div style="margin-bottom:30px; line-height:1.6; color:#555;">
            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>

        <!-- Variant Selector -->
        <?php if(count($variants) > 0): ?>
        <div style="margin-bottom:30px;">
            <label style="display:block; margin-bottom:10px; font-weight:600;">SELECT SIZE</label>
            <div style="display:flex; gap:10px;">
                <?php foreach($variants as $index => $var): ?>
                <button class="variant-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                        onclick="selectVariant(this, <?php echo $var['id']; ?>, <?php echo $var['price']; ?>)"
                        style="padding:10px 20px; border:1px solid #000; background:<?php echo $index===0?'#000':'#fff'; ?>; color:<?php echo $index===0?'#fff':'#000'; ?>; cursor:pointer; font-family:'Jost', sans-serif;">
                    <?php echo htmlspecialchars($var['size_label']); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="selectedVariantId" value="<?php echo $variants[0]['id']; ?>">
        </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div style="display:flex; gap:15px;">
            <button onclick="addToCart()" style="flex:1; padding:15px; background:#000; color:#fff; border:none; text-transform:uppercase; letter-spacing:1px; cursor:pointer;">
                Add to Cart
            </button>
            <a id="whatsappLink" href="#" target="_blank" style="flex:1; padding:15px; background:#25D366; color:#fff; text-align:center; text-decoration:none; text-transform:uppercase; letter-spacing:1px; display:flex; align-items:center; justify-content:center;">
                <i class="fab fa-whatsapp" style="margin-right:10px;"></i> Order via WA
            </a>
        </div>
    </div>
</div>

<script>
function selectVariant(btn, id, price) {
    // UI Update
    document.querySelectorAll('.variant-btn').forEach(b => {
        b.style.background = '#fff';
        b.style.color = '#000';
        b.classList.remove('active');
    });
    btn.style.background = '#000';
    btn.style.color = '#fff';
    btn.classList.add('active');

    // Data Update
    document.getElementById('displayPrice').innerText = '₹' + price.toLocaleString();
    document.getElementById('selectedVariantId').value = id;
    
    updateWhatsAppLink();
}

function updateWhatsAppLink() {
    const name = "<?php echo htmlspecialchars($product['name']); ?>";
    const size = document.querySelector('.variant-btn.active').innerText;
    const price = document.getElementById('displayPrice').innerText;
    const phone = "<?php echo '919876543210'; // TODO: Fetch from Settings ?>"; 
    
    const msg = `Hi, I would like to order: ${name} (${size}) - ${price}. link: <?php echo "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>`;
    const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
    document.getElementById('whatsappLink').href = url;
}

// Init
updateWhatsAppLink();

function addToCart() {
    const id = <?php echo $product['id']; ?>;
    const name = "<?php echo htmlspecialchars($product['name']); ?>";
    const img = "<?php echo htmlspecialchars($images[0]['image_path'] ?? 'assets/images/placeholder.jpg'); ?>";
    
    // Get selected variant details
    const activeBtn = document.querySelector('.variant-btn.active');
    const size = activeBtn ? activeBtn.innerText : 'Standard';
    
    // Get price from the display, removing '₹' and ','
    const priceText = document.getElementById('displayPrice').innerText;
    const price = parseInt(priceText.replace(/[^\d]/g, ''));

    // Call global function from script.js
    if (typeof window.addToCart === 'function') {
        window.addToCart(id, name, price, img, size);
    } else {
        alert("Cart system not loaded. Please refresh.");
    }
}
</script>

<?php include 'includes/footer.php'; ?>
