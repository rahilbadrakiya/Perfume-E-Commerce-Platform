<?php
require_once 'includes/session.php'; // Updated path
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token']);
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];

        // Update Last Login
        $update = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $update->execute([$admin['id']]);

        // Log Activity
        log_activity($pdo, 'Login', 'Admin logged in successfully');

        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid Username or Password";
        // Log Failed Attempt
        // Note: Can't log admin_id if unknown, maybe log by IP if we want advanced security later
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login | K.M. Fragrances</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f4f4f4; display:flex; justify-content:center; align-items:center; height:100vh; }
        .login-box { background: #fff; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
        .login-box h2 { font-family: 'Marcellus', serif; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 14px; color: #555; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; }
        .btn-submit { width: 100%; padding: 12px; background: #000; color: #fff; border: none; cursor: pointer; text-transform: uppercase; }
        .error { color: red; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
        <p style="margin-top:15px; font-size:12px; color:#888;">Default: admin / admin123</p>
    </div>
</body>
</html>
