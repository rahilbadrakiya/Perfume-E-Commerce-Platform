<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_admin_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: login.php");
        exit;
    }
}

function check_admin_role($required_role) {
    if ($_SESSION['admin_role'] !== $required_role && $_SESSION['admin_role'] !== 'Owner') {
        // Owner checks pass everything. If role is Staff and required is Owner, fail.
        die("Access Denied: You do not have permission to view this page.");
    }
}

function log_activity($pdo, $action, $details = '') {
    if (isset($_SESSION['admin_id'])) {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['admin_id'], $action, $details, $_SERVER['REMOTE_ADDR']]);
    }
}
?>
