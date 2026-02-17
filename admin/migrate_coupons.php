<?php
require_once '../config/db.php';

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER discount_amount");
    echo "Migration Successful: Added coupon_code column to orders table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists.";
    } else {
        die("Migration Failed: " . $e->getMessage());
    }
}
?>
