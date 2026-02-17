<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Edit Page';

if (!isset($_GET['id'])) {
    header("Location: pages.php");
    exit;
}

$id = (int)$_GET['id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title']);
    $content = $_POST['content']; // Allow HTML
    $meta_title = clean_input($_POST['meta_title']);
    $meta_desc = clean_input($_POST['meta_description']);
    
    $stmt = $pdo->prepare("UPDATE pages SET title=?, content=?, meta_title=?, meta_description=? WHERE id=?");
    if ($stmt->execute([$title, $content, $meta_title, $meta_desc, $id])) {
        $success = "Page updated successfully!";
    }
}

$stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) die("Page not found");

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Edit: <?php echo htmlspecialchars($page['title']); ?></h1>
    <a href="pages.php" class="btn-new" style="background:#555;">&larr; Back</a>
</div>

<?php if($success): ?><div style="background:#e8f5e9; color:#2e7d32; padding:15px; margin-bottom:20px; border-radius:4px;"><?php echo $success; ?></div><?php endif; ?>

<form method="POST">
    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px;">
        <!-- Left: Content -->
        <div class="table-card" style="padding:20px;">
             <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:500;">Page Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($page['title']); ?>" required style="width:100%; padding:10px; border:1px solid #ddd; font-size:16px;">
            </div>
            
            <div class="form-group">
                <label style="display:block; margin-bottom:8px; font-weight:500;">Page Content (HTML Allowed)</label>
                <!-- In a real app, integrate TinyMCE or CKEditor here -->
                <textarea name="content" style="width:100%; height:400px; padding:10px; border:1px solid #ddd; font-family:monospace;"><?php echo htmlspecialchars($page['content']); ?></textarea>
                <p style="font-size:12px; color:#888;">Tip: You can use HTML tags like &lt;h1&gt;, &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;.</p>
            </div>
        </div>

        <!-- Right: SEO -->
        <div class="table-card" style="padding:20px; align-self:start;">
             <h3 style="margin-top:0;">SEO Settings</h3>
             <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Meta Title</label>
                <input type="text" name="meta_title" value="<?php echo htmlspecialchars($page['meta_title']); ?>" style="width:100%; padding:8px; border:1px solid #ddd;">
            </div>
            
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Meta Description</label>
                <textarea name="meta_description" rows="5" style="width:100%; padding:8px; border:1px solid #ddd;"><?php echo htmlspecialchars($page['meta_description']); ?></textarea>
            </div>
            
             <div style="margin-top:30px;">
                <button type="submit" style="width:100%; background:#000; color:#fff; padding:15px; border:none; cursor:pointer; font-size:16px;">Update Page</button>
            </div>
        </div>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
