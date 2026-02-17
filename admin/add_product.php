<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Add Product';

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Basic Info
    $name = clean_input($_POST['name']);
    $description = $_POST['description']; // Allow HTML if using rich text, or clean if not. Let's assume plain text for now or basic clean.
    $category_id = $_POST['category_id'];
    $brand = clean_input($_POST['brand']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Generate Slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    
    try {
        $pdo->beginTransaction();

        // 2. Insert Product
        $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, category_id, brand, is_active, is_featured) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $stmt->execute([$name, $slug, $description, $category_id, $brand, $is_featured]);
        $product_id = $pdo->lastInsertId();

        // 3. Handle Variants
        if (isset($_POST['variant_size']) && is_array($_POST['variant_size'])) {
            $stmt_variant = $pdo->prepare("INSERT INTO product_variants (product_id, size_label, price, stock_quantity, sku) VALUES (?, ?, ?, ?, ?)");
            
            for ($i = 0; $i < count($_POST['variant_size']); $i++) {
                $size = clean_input($_POST['variant_size'][$i]);
                $price = (float)$_POST['variant_price'][$i];
                $stock = (int)$_POST['variant_stock'][$i];
                $sku = clean_input($_POST['variant_sku'][$i]);
                if (empty($sku)) $sku = null;
                
                if(!empty($size) && $price > 0) {
                    $stmt_variant->execute([$product_id, $size, $price, $stock, $sku]);
                }
            }
        }

        // 4. Handle Image Upload (Primary)
        if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['primary_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'prod_' . $product_id . '_primary_' . time() . '.' . $ext;
                $upload_dir = '../assets/images/uploads/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['primary_image']['tmp_name'], $upload_dir . $new_filename)) {
                    $db_path = 'assets/images/uploads/' . $new_filename;
                    $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 1)");
                    $stmt_img->execute([$product_id, $db_path]);
                }
            }
        }

        // 5. Handle Additional Images (Gallery)
        if (isset($_FILES['additional_images'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $upload_dir = '../assets/images/uploads/';
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

            foreach($_FILES['additional_images']['name'] as $key => $val) {
                if ($_FILES['additional_images']['error'][$key] == 0) {
                    $filename = $_FILES['additional_images']['name'][$key];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = 'prod_' . $product_id . '_gallery_' . $key . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$key], $upload_dir . $new_filename)) {
                             $db_path = 'assets/images/uploads/' . $new_filename;
                             $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, ?)");
                             $stmt_img->execute([$product_id, $db_path, $key+1]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
        $success = "Product added successfully with variants!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error adding product: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Add New Product</h1>
    <a href="products.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if($error): ?><div style="background:#ffebee; color:#c62828; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $error; ?></div><?php endif; ?>
<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>

<div class="table-card" style="padding:30px;">
    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
            <!-- Left Column: Main Info -->
            <div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Product Name</label>
                    <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>
                
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Description</label>
                    <textarea name="description" rows="5" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Brand / Collection</label>
                    <input type="text" name="brand" value="<?php echo isset($_POST['brand']) ? htmlspecialchars($_POST['brand']) : ''; ?>" placeholder="e.g. K.M. Exclusive" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                </div>

                <!-- Variants Section -->
                <div style="margin-top:30px; background:#f9f9f9; padding:20px; border-radius:8px;">
                    <h3 style="margin-top:0; font-size:16px; border-bottom:1px solid #eee; padding-bottom:10px;">Product Variants</h3>
                    <div id="variants-container">
                        <?php 
                        if(isset($_POST['variant_size']) && is_array($_POST['variant_size'])):
                            for($i=0; $i < count($_POST['variant_size']); $i++):
                        ?>
                        <div class="variant-row" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px; <?php if($i>0) echo 'margin-top:10px; padding-top:10px; border-top:1px dashed #eee;'; ?>">
                            <div>
                                <label style="font-size:12px;">Size (e.g. 50ml)</label>
                                <input type="text" name="variant_size[]" value="<?php echo htmlspecialchars($_POST['variant_size'][$i]); ?>" required style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                            <div>
                                <label style="font-size:12px;">Price (₹)</label>
                                <input type="number" step="0.01" name="variant_price[]" value="<?php echo htmlspecialchars($_POST['variant_price'][$i]); ?>" required style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                            <div>
                                <label style="font-size:12px;">Stock</label>
                                <input type="number" name="variant_stock[]" value="<?php echo htmlspecialchars($_POST['variant_stock'][$i]); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                            <div>
                                <label style="font-size:12px;">SKU (Optional)</label>
                                <input type="text" name="variant_sku[]" value="<?php echo htmlspecialchars($_POST['variant_sku'][$i]); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                        </div>
                        <?php 
                            endfor;
                        else:
                        ?>
                        <!-- Initial Row -->
                        <div class="variant-row" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px;">
                            <div>
                                <label style="font-size:12px;">Size (e.g. 50ml)</label>
                                <input type="text" name="variant_size[]" required style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                            <div>
                                <label style="font-size:12px;">Price (₹)</label>
                                <input type="number" step="0.01" name="variant_price[]" required style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                            <div>
                                <label style="font-size:12px;">Stock</label>
                                <input type="number" name="variant_stock[]" value="50" style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                            <div>
                                <label style="font-size:12px;">SKU (Optional)</label>
                                <input type="text" name="variant_sku[]" style="width:100%; padding:8px; border:1px solid #ddd;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="addVariantRow()" style="background:#333; color:#fff; border:none; padding:8px 15px; font-size:12px; cursor:pointer; border-radius:4px;">+ Add Another Variant</button>
                </div>
            </div>

            <!-- Right Column: Meta & Image -->
            <div>
                 <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Category</label>
                    <select name="category_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php if(isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Primary Image</label>
                    <input type="file" name="primary_image" accept="image/*" required style="width:100%; padding:10px; border:1px solid #ddd; background:#fff;">
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Gallery Images</label>
                    <input type="file" name="additional_images[]" accept="image/*" multiple style="width:100%; padding:10px; border:1px solid #ddd; background:#fff;">
                    <small style="color:#888;">Hold Ctrl/Cmd to select multiple files</small>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input type="checkbox" name="is_featured" value="1" <?php if(isset($_POST['is_featured'])) echo 'checked'; ?> style="width:auto; margin-right:10px;">
                        Mark as Featured Product
                    </label>
                </div>

                <div style="margin-top:40px;">
                    <button type="submit" style="width:100%; padding:15px; background:#000; color:#fff; border:none; font-size:16px; font-weight:600; cursor:pointer; border-radius:4px;">SAVE PRODUCT</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function addVariantRow() {
    const container = document.getElementById('variants-container');
    const div = document.createElement('div');
    div.className = 'variant-row';
    div.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:10px; margin-bottom:10px; margin-top:10px; padding-top:10px; border-top:1px dashed #eee;';
    div.innerHTML = `
        <div>
            <input type="text" name="variant_size[]" placeholder="Size" required style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <input type="number" step="0.01" name="variant_price[]" placeholder="Price" required style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <input type="number" name="variant_stock[]" value="50" placeholder="Stock" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <input type="text" name="variant_sku[]" placeholder="SKU" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
    `;
    container.appendChild(div);
}
</script>

<?php include 'includes/footer.php'; ?>
