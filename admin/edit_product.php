<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Edit Product';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$id = (int)$_GET['id'];
$error = '';
$success = '';

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch Product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found!");
}

// Fetch Variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->execute([$id]);
$variants = $stmt->fetchAll();

// Fetch Images (Primary)
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1");
$stmt->execute([$id]);
$primary_image = $stmt->fetch();

// Fetch Gallery Images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 0 ORDER BY sort_order ASC, id ASC");
$stmt->execute([$id]);
$gallery_images = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $brand = clean_input($_POST['brand']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Update Slug if name changed significantly? Let's keep slug stable or update. 
    // Usually better to keep slug stable for SEO, but for a demo, let's update it if needed.
    // $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    try {
        $pdo->beginTransaction();

        // 1. Update Product
        $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, category_id=?, brand=?, is_active=?, is_featured=? WHERE id=?");
        $stmt->execute([$name, $description, $category_id, $brand, $is_active, $is_featured, $id]);

        // 2. Handle Variants
        // Existing Variants Update
        if (isset($_POST['variant_id']) && is_array($_POST['variant_id'])) {
            $stmt_update_var = $pdo->prepare("UPDATE product_variants SET size_label=?, price=?, stock_quantity=?, sku=? WHERE id=? AND product_id=?");
            
            for ($i = 0; $i < count($_POST['variant_id']); $i++) {
                $var_id = (int)$_POST['variant_id'][$i];
                $size = clean_input($_POST['variant_size'][$i]);
                $price = (float)$_POST['variant_price'][$i];
                $stock = (int)$_POST['variant_stock'][$i];
                $sku = clean_input($_POST['variant_sku'][$i]);
                if (empty($sku)) $sku = null;
                
                // Check if marked for deletion (not implemented in UI essentially, but handled via separate button usually. 
                // For now, simple update.)
                $stmt_update_var->execute([$size, $price, $stock, $sku, $var_id, $id]);
            }
        }
        
        // New Variants Insert
        if (isset($_POST['new_variant_size']) && is_array($_POST['new_variant_size'])) {
            $stmt_insert_var = $pdo->prepare("INSERT INTO product_variants (product_id, size_label, price, stock_quantity, sku) VALUES (?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($_POST['new_variant_size']); $i++) {
                $size = clean_input($_POST['new_variant_size'][$i]);
                $price = (float)$_POST['new_variant_price'][$i];
                $stock = (int)$_POST['new_variant_stock'][$i];
                $sku = clean_input($_POST['new_variant_sku'][$i]);
                if (empty($sku)) $sku = null;
                
                if(!empty($size) && $price > 0) {
                    $stmt_insert_var->execute([$id, $size, $price, $stock, $sku]);
                }
            }
        }

        // 2. Handle Variants (Existing Loop...)
        // ... (Variants logic remains) ...
        
        // 3. Handle Images
        $upload_dir = '../assets/images/uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

        // A. Primary Image Upload
        if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['primary_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'prod_' . $id . '_primary_' . time() . '.' . $ext;
                
                if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $upload_dir . $new_filename)) {
                    $db_path = 'assets/images/uploads/' . $new_filename;
                    
                    // Check if existing primary image
                    $stmt_check = $pdo->prepare("SELECT id FROM product_images WHERE product_id=? AND is_primary=1");
                    $stmt_check->execute([$id]);
                    $existing = $stmt_check->fetch();
                    
                    if ($existing) {
                        $stmt_img = $pdo->prepare("UPDATE product_images SET image_path=? WHERE id=?");
                        $stmt_img->execute([$db_path, $existing['id']]);
                    } else {
                        $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)");
                        $stmt_img->execute([$id, $db_path]);
                    }
                }
            }
        }

        // B. Delete Selected Gallery Images
        if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            $stmt_del = $pdo->prepare("DELETE FROM product_images WHERE id = ? AND is_primary = 0");
            foreach($_POST['delete_images'] as $del_id) {
                // Ideally, delete file from server too. 
                // For now, just remove DB entry for safety/speed in demo.
                $stmt_del->execute([(int)$del_id]);
            }
        }

        // C. Upload New Gallery Images
        if (isset($_FILES['additional_images'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            foreach($_FILES['additional_images']['name'] as $key => $val) {
                if ($_FILES['additional_images']['error'][$key] == 0) {
                    $filename = $_FILES['additional_images']['name'][$key];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = 'prod_' . $id . '_gallery_' . time() . '_' . $key . '.' . $ext;
                        if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $upload_dir . $new_filename)) {
                             $db_path = 'assets/images/uploads/' . $new_filename;
                             $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, 0)");
                             $stmt_img->execute([$id, $db_path]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
        $success = "Product updated successfully!";
        
        // Refresh Data
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
        $stmt->execute([$id]);
        $variants = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1");
        $stmt->execute([$id]);
        $primary_image = $stmt->fetch();
        
        // Fetch Gallery Images
        $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_primary = 0 ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$id]);
        $gallery_images = $stmt->fetchAll();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating product: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="admin-header">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="products.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <h1 style="margin:0;">Edit: <?php echo htmlspecialchars($product['name']); ?></h1>
    </div>
</div>

<?php if($error): ?><div style="background:#ffebee; color:#c62828; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $error; ?></div><?php endif; ?>
<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>

<div class="table-card" style="padding:30px;">
    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
            <!-- Left Column -->
            <div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Product Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Description</label>
                    <textarea name="description" rows="5" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Brand / Collection</label>
                    <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>

                <!-- Variants Section -->
                <div style="margin-top:30px; background:#f9f9f9; padding:20px; border-radius:8px;">
                    <h3 style="margin-top:0; font-size:16px; border-bottom:1px solid #eee; padding-bottom:10px;">Product Variants</h3>
                    
                    <?php foreach($variants as $var): ?>
                    <div class="variant-row" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:end;">
                        <input type="hidden" name="variant_id[]" value="<?php echo $var['id']; ?>">
                        <div>
                            <label style="font-size:11px; color:#888;">Size</label>
                            <input type="text" name="variant_size[]" value="<?php echo htmlspecialchars($var['size_label']); ?>" required style="width:100%; padding:8px; border:1px solid #ddd;">
                        </div>
                        <div>
                            <label style="font-size:11px; color:#888;">Price</label>
                            <input type="number" step="0.01" name="variant_price[]" value="<?php echo $var['price']; ?>" required style="width:100%; padding:8px; border:1px solid #ddd;">
                        </div>
                        <div>
                            <label style="font-size:11px; color:#888;">Stock</label>
                            <input type="number" name="variant_stock[]" value="<?php echo $var['stock_quantity']; ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
                        </div>
                        <div>
                            <label style="font-size:11px; color:#888;">SKU</label>
                            <input type="text" name="variant_sku[]" value="<?php echo htmlspecialchars($var['sku']); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
                        </div>
                        <!-- Delete Variant Link -->
                        <div style="padding-bottom:10px;">
                             <!-- TODO: Allow deletion (separate script for safety) -->
                             <a href="#" onclick="alert('To delete a variant, set stock to 0 or implement delete logic.'); return false;" style="color:red; font-size:12px;">&times;</a>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div id="new-variants-container"></div>
                    <button type="button" onclick="addNewVariantRow()" style="background:#333; color:#fff; border:none; padding:8px 15px; font-size:12px; cursor:pointer; border-radius:4px; margin-top:10px;">+ Add New Variant</button>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                 <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Category</label>
                    <select name="category_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if($product['category_id'] == $cat['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Primary Image</label>
                    <?php if($primary_image): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../<?php echo htmlspecialchars($primary_image['image_path']); ?>" style="width:100px; height:100px; object-fit:contain; border:1px solid #eee;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="primary_image" accept="image/*" style="width:100%; padding:10px; border:1px solid #ddd; background:#fff;">
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Gallery Images</label>
                    
                    <?php if($gallery_images): ?>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; margin-bottom:10px;">
                        <?php foreach($gallery_images as $img): ?>
                        <div style="position:relative; border:1px solid #eee; padding:5px; text-align:center;">
                            <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" style="width:100%; height:80px; object-fit:contain;">
                            <label style="display:block; font-size:11px; margin-top:5px; cursor:pointer; color:#d32f2f;">
                                <input type="checkbox" name="delete_images[]" value="<?php echo $img['id']; ?>"> Delete
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <input type="file" name="additional_images[]" accept="image/*" multiple style="width:100%; padding:10px; border:1px solid #ddd; background:#fff;">
                    <small style="color:#888;">Hold Ctrl/Cmd to select multiple files</small>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:flex; align-items:center; cursor:pointer; margin-bottom:10px;">
                        <input type="checkbox" name="is_featured" value="1" <?php if($product['is_featured']) echo 'checked'; ?> style="width:auto; margin-right:10px;">
                        Featured Product
                    </label>
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" <?php if($product['is_active']) echo 'checked'; ?> style="width:auto; margin-right:10px;">
                        Active (Visible)
                    </label>
                </div>

                <div style="margin-top:40px;">
                    <button type="submit" style="width:100%; padding:15px; background:#000; color:#fff; border:none; font-size:16px; font-weight:600; cursor:pointer; border-radius:4px;">UPDATE PRODUCT</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function addNewVariantRow() {
    const container = document.getElementById('new-variants-container');
    const div = document.createElement('div');
    div.className = 'variant-row';
    div.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px; margin-top:10px; padding-top:10px; border-top:1px dashed #eee;';
    div.innerHTML = `
        <div>
            <input type="text" name="new_variant_size[]" placeholder="New Size" required style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <input type="number" step="0.01" name="new_variant_price[]" placeholder="Price" required style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <input type="number" name="new_variant_stock[]" value="50" placeholder="Stock" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <input type="text" name="new_variant_sku[]" placeholder="SKU" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
    `;
    container.appendChild(div);
}
</script>

<?php include 'includes/footer.php'; ?>
