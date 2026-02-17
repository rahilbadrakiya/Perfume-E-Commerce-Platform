<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();

if (!isset($_GET['id'])) {
    die("Invalid Order ID");
}

$id = (int)$_GET['id'];

// Fetch Order
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo $order['order_number']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Jost', sans-serif; padding: 40px; color: #333; max-width: 800px; margin: 0 auto; }
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #ddd; }
        .company-info h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px; }
        .company-info p { margin: 5px 0 0; color: #777; font-size: 14px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { margin: 0; font-size: 18px; color: #000; }
        .invoice-meta p { margin: 5px 0 0; color: #666; font-size: 14px; }
        
        .bill-to { margin-bottom: 30px; }
        .bill-to h3 { font-size: 14px; text-transform: uppercase; color: #999; margin-bottom: 10px; }
        .bill-to p { margin: 0; font-size: 15px; line-height: 1.5; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; padding: 10px 0; border-bottom: 2px solid #000; font-size: 12px; text-transform: uppercase; }
        td { padding: 15px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .text-right { text-align: right; }
        
        .totals { float: right; width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .total-row.final { font-weight: 600; font-size: 18px; border-top: 2px solid #000; margin-top: 10px; padding-top: 15px; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #000; color: #fff; border: none; cursor: pointer;">Print Invoice</button>
    </div>

    <div class="invoice-header">
        <div class="company-info">
            <h1>K.M. FRAGRANCES</h1>
            <p>123 Luxury Lane, Fashion District<br>Mumbai, India - 400001</p>
            <p>support@kmfragrances.com</p>
        </div>
        <div class="invoice-meta">
            <h2>INVOICE #<?php echo $order['order_number']; ?></h2>
            <p>Date: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
            <p>Status: <?php echo $order['payment_status']; ?></p>
        </div>
    </div>
    
    <div class="bill-to">
        <h3>Bill To:</h3>
        <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
        <p><?php echo htmlspecialchars($order['customer_email']); ?></p>
        <p><?php echo htmlspecialchars($order['customer_phone']); ?></p>
        <p style="margin-top:5px; white-space:pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Size</th>
                <th class="text-right">Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo htmlspecialchars($item['size_label']); ?></td>
                <td class="text-right">₹<?php echo number_format($item['unit_price']); ?></td>
                <td class="text-right"><?php echo $item['quantity']; ?></td>
                <td class="text-right">₹<?php echo number_format($item['total_price']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span>₹<?php echo number_format($order['subtotal_amount']); ?></span>
        </div>
        <div class="total-row">
            <span>Shipping</span>
            <span>₹<?php echo number_format($order['shipping_cost']); ?></span>
        </div>
        <div class="total-row final">
            <span>Total</span>
            <span>₹<?php echo number_format($order['total_amount']); ?></span>
        </div>
    </div>
</body>
</html>
