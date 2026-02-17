<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Coupons';

$success = '';
$error = '';

// Handle Add Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(clean_input($_POST['code']));
    $type = $_POST['discount_type'];
    $value = (float)$_POST['discount_value'];
    $min_order = (float)$_POST['min_order_value'];
    $expiry = $_POST['expiry_date']; // Y-m-d
    $limit = (int)$_POST['usage_limit'];
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        $error = "Coupon code already exists!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_value, expiry_date, usage_limit) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$code, $type, $value, $min_order, $expiry, $limit])) {
            $success = "Coupon created successfully!";
        } else {
            $error = "Database Error";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
    header("Location: coupons.php");
    exit;
}

// Fetch Coupons with Analytics
$stmt = $pdo->query("SELECT c.*, 
    (SELECT SUM(o.total_amount) FROM orders o WHERE o.coupon_code = c.code AND o.payment_status = 'Paid') as total_revenue,
    (SELECT COUNT(*) FROM orders o WHERE o.coupon_code = c.code AND o.payment_status = 'Paid') as real_usage 
    FROM coupons c ORDER BY created_at DESC");
$coupons = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Coupons</h1>
    <button onclick="document.getElementById('add-coupon-form').style.display='block'" class="btn-new">+ Create Coupon</button>
</div>

<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>
<?php if($error): ?><div style="background:#ffebee; color:#c62828; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $error; ?></div><?php endif; ?>

<!-- Add Form (Hidden by default) -->
<div id="add-coupon-form" class="table-card" style="padding:20px; margin-bottom:30px; display:none; border:1px solid #ddd;">
    <h3 style="margin-top:0;">Create New Coupon</h3>
    <form method="POST" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
        <div>
            <label style="display:block; margin-bottom:5px;">Coupon Code</label>
            <input type="text" name="code" required placeholder="e.g. WELCOME10" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <label style="display:block; margin-bottom:5px;">Discount Type</label>
            <select name="discount_type" style="width:100%; padding:8px; border:1px solid #ddd;">
                <option value="Percentage">Percentage (%)</option>
                <option value="Fixed">Fixed Amount (₹)</option>
            </select>
        </div>
        <div>
            <label style="display:block; margin-bottom:5px;">Value</label>
            <input type="number" step="0.01" name="discount_value" required placeholder="10" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <label style="display:block; margin-bottom:5px;">Min Order Value</label>
            <input type="number" step="0.01" name="min_order_value" value="0" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <label style="display:block; margin-bottom:5px;">Expiry Date</label>
            <input type="date" name="expiry_date" required style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div>
            <label style="display:block; margin-bottom:5px;">Usage Limit</label>
            <input type="number" name="usage_limit" value="100" style="width:100%; padding:8px; border:1px solid #ddd;">
        </div>
        <div style="grid-column: 1/-1; margin-top:10px;">
            <button type="submit" name="add_coupon" style="background:#000; color:#fff; padding:10px 20px; border:none; cursor:pointer;">Save Coupon</button>
            <button type="button" onclick="document.getElementById('add-coupon-form').style.display='none'" style="background:#eee; color:#333; padding:10px 20px; border:none; cursor:pointer; margin-left:10px;">Cancel</button>
        </div>
    </form>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Discount</th>
                    <th>Min Order</th>
                    <th>Expiry</th>
                    <th>Usage (Limit)</th>
                    <th>Sales</th>
                    <th>Revenue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($coupons as $c): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($c['code']); ?></strong></td>
                    <td>
                        <?php echo $c['discount_type'] == 'Percentage' ? $c['discount_value'].'%' : '₹'.$c['discount_value']; ?>
                    </td>
                    <td>₹<?php echo number_format($c['min_order_value']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($c['expiry_date'])); ?></td>
                    <td><?php echo $c['used_count'] . ' / ' . $c['usage_limit']; ?></td>
                    <td><?php echo $c['real_usage'] ? $c['real_usage'] : 0; ?></td>
                    <td><strong>₹<?php echo number_format($c['total_revenue'] ? $c['total_revenue'] : 0); ?></strong></td>
                    <td>
                        <?php if($c['expiry_date'] < date('Y-m-d')) echo '<span class="badge badge-danger">Expired</span>'; 
                              else echo '<span class="badge badge-success">Active</span>'; ?>
                    </td>
                    <td class="actions">
                        <a href="coupons.php?delete=<?php echo $c['id']; ?>" class="delete-btn" onclick="return confirm('Delete coupon?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($coupons) === 0): ?>
                <tr><td colspan="7" style="text-align:center;">No coupons found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
