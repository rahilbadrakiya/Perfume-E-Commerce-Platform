<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = isset($input['code']) ? strtoupper(clean_input($input['code'])) : '';
$cartTotal = isset($input['total']) ? (float)$input['total'] : 0;

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
    exit;
}

// Check Coupon
$stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
    exit;
}

// 1. Check Expiry
if ($coupon['expiry_date'] < date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Coupon has expired']);
    exit;
}

// 2. Check Usage Limit
if ($coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'Coupon usage limit reached']);
    exit;
}

// 3. Check Minimum Order
if ($cartTotal < $coupon['min_order_value']) {
    echo json_encode(['success' => false, 'message' => 'Minimum order value of ₹' . $coupon['min_order_value'] . ' required']);
    exit;
}

// Calculate Discount
$discountDetails = '';
$discountAmount = 0;

if ($coupon['discount_type'] === 'Percentage') {
    $discountAmount = ($cartTotal * $coupon['discount_value']) / 100;
    $discountDetails = $coupon['discount_value'] . '% Off';
} else {
    $discountAmount = $coupon['discount_value'];
    $discountDetails = '₹' . $coupon['discount_value'] . ' Flat Off';
}

// Ensure discount doesn't exceed total
if ($discountAmount > $cartTotal) {
    $discountAmount = $cartTotal;
}

$newTotal = $cartTotal - $discountAmount;

echo json_encode([
    'success' => true,
    'message' => 'Coupon Applied Successfully!',
    'discount_amount' => $discountAmount,
    'new_total' => $newTotal,
    'coupon_code' => $code,
    'discount_details' => $discountDetails
]);
?>
