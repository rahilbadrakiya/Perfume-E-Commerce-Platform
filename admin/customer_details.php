<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Customer Details';

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit;
}

$id = (int)$_GET['id'];
$success = '';

// Handle Block/Unblock
if (isset($_POST['toggle_status'])) {
    $new_status = $_POST['new_status'];
    $stmt = $pdo->prepare("UPDATE customers SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    $success = "Customer status updated to $new_status.";
}

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) die("Customer not found");

// Fetch Orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$orders = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1><?php echo htmlspecialchars($customer['full_name']); ?></h1>
    <a href="customers.php" class="btn-new" style="background:#555;">&larr; Back</a>
</div>

<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 2fr; gap:30px;">
    <!-- Left: Profile -->
    <div>
        <div class="table-card" style="padding:20px;">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Profile</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
            <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($customer['created_at'])); ?></p>
            <p><strong>Status:</strong> <?php echo $customer['status']; ?></p>
            
            <form method="POST" style="margin-top:20px;">
                <input type="hidden" name="new_status" value="<?php echo $customer['status'] == 'Active' ? 'Blocked' : 'Active'; ?>">
                <button type="submit" name="toggle_status" style="width:100%; padding:10px; background:<?php echo $customer['status'] == 'Active' ? '#d32f2f' : '#2e7d32'; ?>; color:#fff; border:none; cursor:pointer;">
                    <?php echo $customer['status'] == 'Active' ? 'Block Customer' : 'Unblock Customer'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Right: Orders -->
    <div class="table-card">
        <div class="table-header"><h3>Order History</h3></div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['order_number']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>₹<?php echo number_format($order['total_amount']); ?></td>
                        <td><?php echo $order['order_status']; ?></td>
                        <td><a href="order_details.php?id=<?php echo $order['id']; ?>">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(count($orders) === 0): ?>
                    <tr><td colspan="5" style="text-align:center;">No orders placed yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
