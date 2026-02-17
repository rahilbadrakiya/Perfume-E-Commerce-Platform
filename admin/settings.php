<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Settings';

$success = '';

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $key = clean_input($key);
        // Dont clean value too much as it might be HTML or URL
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    $success = "Settings saved successfully.";
}

// Fetch Settings
$stmt = $pdo->query("SELECT * FROM settings");
$all_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['store_name' => 'Value', ...]

function get_setting($key, $settings) {
    return isset($settings[$key]) ? htmlspecialchars($settings[$key]) : '';
}

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Store Settings</h1>
</div>

<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>

<form method="POST">
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
        <!-- General -->
        <div class="table-card" style="padding:20px;">
            <h3 style="margin-top:0;">General Information</h3>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Store Name</label>
                <input type="text" name="store_name" value="<?php echo get_setting('store_name', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Contact Email</label>
                <input type="email" name="contact_email" value="<?php echo get_setting('contact_email', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Contact Phone</label>
                <input type="text" name="contact_phone" value="<?php echo get_setting('contact_phone', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Currency Symbol</label>
                <input type="text" name="currency_symbol" value="<?php echo get_setting('currency_symbol', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
        </div>

        <!-- Social Media -->
        <div class="table-card" style="padding:20px;">
            <h3 style="margin-top:0;">Social Media Links</h3>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Instagram URL</label>
                <input type="text" name="social_instagram" value="<?php echo get_setting('social_instagram', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Facebook URL</label>
                <input type="text" name="social_facebook" value="<?php echo get_setting('social_facebook', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Shipping Fee (Standard)</label>
                <input type="number" name="shipping_fee" value="<?php echo get_setting('shipping_fee', $all_settings); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
        </div>
    </div>
    
    <div style="margin-top:30px;">
        <button type="submit" style="background:#000; color:#fff; padding:15px 30px; border:none; cursor:pointer; font-size:16px;">Save Settings</button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
