<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'CMS Pages';

// Auto-Migration: Check if table exists
try {
    $result = $pdo->query("SELECT 1 FROM pages LIMIT 1");
} catch (Exception $e) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        content LONGTEXT,
        meta_title VARCHAR(255),
        meta_description VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    // Seed Default Pages
    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content) VALUES (:title, :slug, :content)");
    $defaults = [
        ['title' => 'About Us', 'slug' => 'about', 'content' => '<p>Welcome to K.M. Fragrances...</p>'],
        ['title' => 'Contact Us', 'slug' => 'contact', 'content' => '<p>Get in touch...</p>'],
        ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'content' => '<p>Privacy...</p>'],
        ['title' => 'Terms & Conditions', 'slug' => 'terms', 'content' => '<p>T&C...</p>'],
        ['title' => 'Refund Policy', 'slug' => 'refund-policy', 'content' => '<p>Refunds...</p>']
    ];
    foreach ($defaults as $p) {
        $stmt->execute($p);
    }
}

// Fetch Pages
$stmt = $pdo->query("SELECT * FROM pages");
$pages = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Content Pages</h1>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pages as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                    <td>/<?php echo htmlspecialchars($p['slug']); ?>.php</td>
                    <td><?php echo date('M d, Y', strtotime($p['updated_at'])); ?></td>
                    <td class="actions">
                        <a href="edit_page.php?id=<?php echo $p['id']; ?>" class="edit-btn">Edit Content</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
