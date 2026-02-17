<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Order Details';

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$id = (int)$_GET['id'];
$success = '';
$error = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = clean_input($_POST['order_status']);
    $payment_status = clean_input($_POST['payment_status']);
    $courier = clean_input($_POST['courier_name']);
    $tracking = clean_input($_POST['tracking_id']);
    
    $stmt = $pdo->prepare("UPDATE orders SET order_status=?, payment_status=?, courier_name=?, tracking_id=? WHERE id=?");
    if ($stmt->execute([$new_status, $payment_status, $courier, $tracking, $id])) {
        $success = "Order updated successfully!";
        // TODO: Send Email Notification here
    } else {
        $error = "Failed to update order.";
    }
}

// Fetch Order Info
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// Fetch Items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        <h1 style="margin:0;">Order #<?php echo $order['order_number']; ?></h1>
    </div>
    <div>
        <a href="invoice.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn-new" style="background:#fff; color:#333; border:1px solid #ddd;"><i class="fas fa-print"></i> Print Invoice</a>
    </div>
</div>

<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
    <!-- Left Column: Items -->
    <div class="table-card" style="padding:0;">
         <div class="table-header"><h3>Order Items</h3></div>
         <table>
             <thead>
                 <tr>
                     <th>Product</th>
                     <th>Size</th>
                     <th>Price</th>
                     <th>Qty</th>
                     <th>Total</th>
                 </tr>
             </thead>
             <tbody>
                 <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:20px; color:#999;">
                            No items found for this order. (Data may be incomplete)
                        </td>
                    </tr>
                 <?php else: ?>
                     <?php foreach($items as $item): ?>
                     <tr>
                         <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                         <td><?php echo htmlspecialchars($item['size_label']); ?></td>
                         <td>₹<?php echo number_format($item['unit_price']); ?></td>
                         <td><?php echo $item['quantity']; ?></td>
                         <td>₹<?php echo number_format($item['total_price']); ?></td>
                     </tr>
                     <?php endforeach; ?>
                 <?php endif; ?>
                 <tr style="background:#f9f9f9; font-weight:600;">
                     <td colspan="4" style="text-align:right;">Subtotal</td>
                     <td>₹<?php echo number_format($order['subtotal_amount']); ?></td>
                 </tr>
                  <tr style="background:#f9f9f9;">
                     <td colspan="4" style="text-align:right;">Shipping</td>
                     <td>₹<?php echo number_format($order['shipping_cost']); ?></td>
                 </tr>
                 <?php if($order['discount_amount'] > 0): ?>
                 <tr style="background:#f9f9f9; color:green;">
                     <td colspan="4" style="text-align:right;">Discount (<?php echo htmlspecialchars($order['coupon_code']); ?>)</td>
                     <td>-₹<?php echo number_format($order['discount_amount']); ?></td>
                 </tr>
                 <?php endif; ?>
                  <tr style="background:#f9f9f9; font-size:16px; font-weight:bold;">
                     <td colspan="4" style="text-align:right;">Total</td>
                     <td>₹<?php echo number_format($order['total_amount']); ?></td>
                 </tr>
             </tbody>
         </table>
    </div>
    
    <!-- Right Column: Info & Actions -->
    <div>
        <!-- Customer Info -->
        <div class="table-card" style="padding:20px; margin-bottom:20px;">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Customer Info</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            <h4 style="font-size:14px; margin-top:15px;">Shipping Address</h4>
            <p style="white-space:pre-line; color:#666; font-size:13px;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
        </div>

        <!-- Order Actions -->
        <div class="table-card" style="padding:20px;">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Update Status</h3>
            <form method="POST">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Order Status</label>
                    <select name="order_status" style="width:100%; padding:8px; border:1px solid #ddd;">
                        <?php 
                        $statuses = ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered', 'Cancelled'];
                        foreach($statuses as $s) {
                            $sel = ($order['order_status'] == $s) ? 'selected' : '';
                            echo "<option value='$s' $sel>$s</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Payment Status</label>
                    <select name="payment_status" style="width:100%; padding:8px; border:1px solid #ddd;">
                         <?php 
                        $p_statuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
                        foreach($p_statuses as $ps) {
                            $sel = ($order['payment_status'] == $ps) ? 'selected' : '';
                            echo "<option value='$ps' $sel>$ps</option>";
                        }
                        ?>
                    </select>
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Courier Name</label>
                    <input type="text" name="courier_name" value="<?php echo htmlspecialchars($order['courier_name'] ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Tracking ID</label>
                    <input type="text" name="tracking_id" value="<?php echo htmlspecialchars($order['tracking_id'] ?? ''); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
                </div>

                <button type="submit" name="update_status" style="width:100%; padding:12px; background:#000; color:#fff; border:none; cursor:pointer;">Update Order</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
