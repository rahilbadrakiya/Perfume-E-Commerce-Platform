<?php
require_once 'includes/session.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

check_admin_login();
$page_title = 'Reports';

// Filter Date Range
$range = isset($_GET['range']) ? $_GET['range'] : '30days';
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

if ($range == '7days') $start_date = date('Y-m-d', strtotime('-7 days'));
if ($range == 'year') $start_date = date('Y-m-d', strtotime('-1 year'));

// 1. Sales Over Time (Chart Data)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, SUM(total_amount) as total 
    FROM orders 
    WHERE payment_status = 'Paid' AND created_at BETWEEN ? AND ? 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$stmt->execute([$start_date, $end_date . ' 23:59:59']);
$sales_data = $stmt->fetchAll();

$chart_labels = [];
$chart_values = [];
foreach($sales_data as $d) {
    $chart_labels[] = date('M d', strtotime($d['date']));
    $chart_values[] = $d['total'];
}

// 2. Sales by Category
$stmt = $pdo->query("
    SELECT c.name, COUNT(oi.id) as count, SUM(oi.total_price) as revenue 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.payment_status = 'Paid'
    GROUP BY c.id
");
$cat_sales = $stmt->fetchAll();

// 3. Top Products
$stmt = $pdo->query("
    SELECT product_name, SUM(quantity) as sold 
    FROM order_items 
    GROUP BY product_id 
    ORDER BY sold DESC 
    LIMIT 5
");
$top_products = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-header">
    <h1>Reports & Analytics</h1>
    <div class="filter-group">
        <a href="reports.php?range=7days" class="btn-filter <?php echo $range=='7days'?'active':''; ?>" data-status="<?php echo $range=='7days'?'Confirmed':''; ?>">Last 7 Days</a>
        <a href="reports.php?range=30days" class="btn-filter <?php echo $range=='30days'?'active':''; ?>" data-status="<?php echo $range=='30days'?'Confirmed':''; ?>">Last 30 Days</a>
    </div>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:30px; margin-bottom:30px;">
    <!-- Sales Chart -->
    <div class="table-card" style="padding:20px;">
        <h3 style="margin-top:0;">Sales Overview</h3>
        <canvas id="salesChart" style="width:100%; height:300px;"></canvas>
    </div>

    <!-- Top Products -->
    <div class="table-card">
        <div class="table-header"><h3>Best Sellers</h3></div>
        <table>
            <?php foreach($top_products as $tp): ?>
            <tr>
                <td style="border-bottom:1px solid #eee; padding:10px;"><?php echo htmlspecialchars($tp['product_name']); ?></td>
                <td style="border-bottom:1px solid #eee; padding:10px; text-align:right; font-weight:bold;"><?php echo $tp['sold']; ?> sold</td>
            </tr>
            <?php endforeach; ?>
             <?php if(count($top_products) == 0) echo "<tr><td colspan='2' style='padding:15px; text-align:center;'>No data available</td></tr>"; ?>
        </table>
    </div>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Sales by Category</h3>
         <!-- Export Button Placeholder -->
        <button onclick="alert('Export functionality coming soon or can be implemented via PHP CSV headers.')" style="background:#fff; border:1px solid #ddd; padding:5px 10px; cursor:pointer;">Export CSV</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Units Sold</th>
                <th>Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cat_sales as $cs): ?>
            <tr>
                <td><?php echo htmlspecialchars($cs['name']); ?></td>
                <td><?php echo $cs['count']; ?></td>
                <td>₹<?php echo number_format($cs['revenue']); ?></td>
            </tr>
            <?php endforeach; ?>
             <?php if(count($cat_sales) == 0) echo "<tr><td colspan='3' style='padding:15px; text-align:center;'>No sales data yet.</td></tr>"; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?php echo json_encode($chart_values); ?>,
            borderColor: '#000',
            backgroundColor: 'rgba(0,0,0,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
