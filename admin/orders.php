<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Orders';

// Filters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$where_sql = "";
$params = [];

if ($status_filter) {
    $where_sql = "WHERE order_status = ?";
    $params[] = $status_filter;
}

// Fetch Orders
$sql = "SELECT * FROM orders $where_sql ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Orders</h1>
    <div class="filter-group">
        <a href="orders.php" class="btn-filter <?php echo $status_filter == '' ? 'active' : ''; ?>">All</a>
        <a href="orders.php?status=Pending" class="btn-filter <?php echo $status_filter == 'Pending' ? 'active' : ''; ?>" data-status="Pending"><i class="fas fa-clock"></i> Pending</a>
        <a href="orders.php?status=Confirmed" class="btn-filter <?php echo $status_filter == 'Confirmed' ? 'active' : ''; ?>" data-status="Confirmed"><i class="fas fa-check-circle"></i> Confirmed</a>
        <a href="orders.php?status=Delivered" class="btn-filter <?php echo $status_filter == 'Delivered' ? 'active' : ''; ?>" data-status="Delivered"><i class="fas fa-truck"></i> Delivered</a>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['order_number']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <span style="font-size:12px; color:#888;"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    </td>
                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                    <td>₹<?php echo number_format($order['total_amount']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $order['payment_status'] == 'Paid' ? 'success' : ($order['payment_status'] == 'Failed' ? 'danger' : 'warning'); ?>">
                            <?php echo $order['payment_status']; ?> (<?php echo $order['payment_method']; ?>)
                        </span>
                    </td>
                    <td>
                        <?php
                            $status_color = 'warning';
                            if($order['order_status'] == 'Confirmed') $status_color = 'primary'; // blueish
                            if($order['order_status'] == 'Shipped') $status_color = 'info';
                            if($order['order_status'] == 'Delivered') $status_color = 'success';
                            if($order['order_status'] == 'Cancelled') $status_color = 'danger';
                        ?>
                        <span class="badge badge-<?php echo $status_color; ?>"><?php echo $order['order_status']; ?></span>
                    </td>
                    <td class="actions">
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="edit-btn">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($orders) === 0): ?>
                <tr><td colspan="7" style="text-align:center;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .badge-primary { background: #e3f2fd; color: #1e88e5; }
    .badge-info { background: #e0f7fa; color: #00acc1; }
</style>

<?php include 'includes/footer.php'; ?>
