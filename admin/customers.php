<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Customers';

$stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
$customers = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Customers</h1>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($customers as $c): ?>
                <tr>
                    <td>#<?php echo $c['id']; ?></td>
                    <td><?php echo htmlspecialchars($c['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $c['status'] == 'Active' ? 'success' : 'danger'; ?>">
                            <?php echo $c['status']; ?>
                        </span>
                    </td>
                    <td class="actions">
                        <a href="customer_details.php?id=<?php echo $c['id']; ?>" class="edit-btn">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($customers) === 0): ?>
                <tr><td colspan="6" style="text-align:center;">No registered customers yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
