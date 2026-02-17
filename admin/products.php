<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Products';

// Fetch Products with Category Name and Total Stock
$query = "
    SELECT p.*, c.name as category_name, 
           (SELECT SUM(stock_quantity) FROM product_variants pv WHERE pv.product_id = p.id) as total_stock,
           (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
";
$stmt = $pdo->query($query);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>All Products</h1>
    <a href="add_product.php" class="btn-new">+ Add New Product</a>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock (All Variants)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $product): ?>
                <tr>
                    <td>
                        <?php if(!empty($product['primary_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($product['primary_image']); ?>" class="product-img-thumb" style="width:50px; height:50px; object-fit:contain;">
                        <?php else: ?>
                            <span style="color:#ccc; font-size:12px;">No Image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                        <span style="font-size:12px; color:#888;">Brand: <?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td>
                        <?php 
                        $stock = $product['total_stock'] ?? 0; 
                        if ($stock == 0) echo '<span class="badge badge-danger">Out of Stock</span>';
                        elseif ($stock < 10) echo '<span class="badge badge-warning">Low: '.$stock.'</span>';
                        else echo '<span class="badge badge-success">'.$stock.'</span>';
                        ?>
                    </td>
                    <td>
                        <?php echo $product['is_active'] ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Hidden</span>'; ?>
                    </td>
                    <td class="actions">
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="edit-btn">Edit</a>
                        <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($products) === 0): ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px;">No products found in the catalog.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
