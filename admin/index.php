<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();

// Fetch Key Stats
// 1. Total Orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$total_orders = $stmt->fetchColumn();

// 2. Total Sales
$stmt = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'Paid'");
$total_sales = $stmt->fetchColumn() ?: 0;

// 3. Total Products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt->fetchColumn();

// 4. Low Stock Alerts (Variants < 5 or Products < 5 if no variants)
// Simplified: Just count variants with low stock for now
$stmt = $pdo->query("SELECT COUNT(*) FROM product_variants WHERE stock_quantity < 5");
$low_stock = $stmt->fetchColumn();

// Recents
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <h3>₹<?php echo number_format($total_sales); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_orders; ?></h3>
            <p>Total Orders</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-box-open"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_products; ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="color:#e74c3c;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <h3><?php echo $low_stock; ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Recent Orders</h3>
        <a href="orders.php" style="font-size:13px; color:#000;">View All</a>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['order_number']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td>₹<?php echo number_format($order['total_amount']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td><span class="badge badge-<?php echo $order['order_status'] == 'Pending' ? 'warning' : 'success'; ?>"><?php echo $order['order_status']; ?></span></td>
                    <td><a href="order_details.php?id=<?php echo $order['id']; ?>" style="color:#000;">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($recent_orders) === 0): ?>
                    <tr><td colspan="6" style="text-align:center; color:#999;">No orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
