<?php
$page_title = "Order Confirmed";
include 'includes/header.php';

$order_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';
?>

<div style="max-width: 600px; margin: 80px auto; text-align: center; padding: 20px;">
    <div style="font-size: 80px; color: #28a745; margin-bottom: 20px;">
        <i class="fas fa-check-circle"></i> #000000;
    </div>
    <h1 style="font-family: 'Marcellus', serif; margin-bottom: 10px;">Thank You!</h1>
    <p style="font-size: 1.2rem; margin-bottom: 30px; color: #555;">Your order has been placed successfully.</p>
    
    <?php if($order_id): ?>
        <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; display: inline-block; margin-bottom: 30px;">
            <p style="margin: 0; font-weight: bold;">ORDER ID: #<?php echo $order_id; ?></p>
        </div>
    <?php endif; ?>

    <p style="margin-bottom: 40px;">We have received your order details and will process it shortly. You will receive an email confirmation soon.</p>

    <a href="index.php" class="btn-shop" style="display: inline-block; padding: 15px 40px; background: #000; color: #fff; text-decoration: none; text-transform: uppercase; letter-spacing: 1px;">Continue Shopping</a>
</div>

<?php include 'includes/footer.php'; ?>
