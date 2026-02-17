<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Assume $page_title is set in the parent file
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($page_title) ? $page_title . ' | Admin' : 'Admin Panel'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Marcellus&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Main Site Styles -->
    <style>
        :root {
            --admin-sidebar-width: 260px;
            --admin-header-height: 60px;
            --admin-bg: #f4f6f9;
            --admin-sidebar-bg: #1a1a1a;
            --admin-text: #333;
        }
        body { background: var(--admin-bg); display: flex; min-height: 100vh; overflow-x: hidden; font-family: 'Jost', sans-serif; }
        
        /* Sidebar */
        .sidebar {
            width: var(--admin-sidebar-width);
            background: var(--admin-sidebar-bg);
            color: #fff;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar-header {
            height: var(--admin-header-height);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-family: 'Marcellus', serif;
            font-size: 20px;
            letter-spacing: 1px;
        }
        .nav-links { list-style: none; padding: 20px 0; }
        .nav-item { margin-bottom: 5px; }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #b0b0b0;
            text-decoration: none;
            transition: 0.3s;
            font-size: 15px;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left: 3px solid #fff;
        }
        .nav-link i { width: 25px; margin-right: 10px; text-align: center; }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--admin-sidebar-width);
            display: flex;
            flex-direction: column;
        }
        
        /* Top Header */
        .top-header {
            height: var(--admin-header-height);
            background: #fff;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }
        .user-menu span { font-weight: 500; margin-right: 15px; }
        .logout-link { color: #d32f2f; text-decoration: none; font-size: 14px; }
        
        /* Content Area */
        .content-wrapper { padding: 30px; }
        
        /* Dashboard Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; align-items: center; }
        .stat-icon { width: 50px; height: 50px; background: #f8f9fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px; color: #333; }
        .stat-info h3 { margin: 0 0 5px 0; font-size: 24px; font-weight: 600; }
        .stat-info p { margin: 0; color: #777; font-size: 13px; text-transform: uppercase; }

        /* Tables */
        .table-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .table-header { padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 25px; text-align: left; border-bottom: 1px solid #f5f5f5; font-size: 14px; }
        th { background: #fcfcfc; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        
        /* Admin Header */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        /* Badges */
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .badge-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .badge-warning { background: #fff8e1; color: #f57f17; border: 1px solid #ffe0b2; }
        .badge-danger { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .badge-primary { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
        .badge-info { background: #e0f7fa; color: #00838f; border: 1px solid #b2ebf2; }

        /* Modern Buttons */
        .btn-new {
            background: #000;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px; /* Slightly rounded */
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-new:hover { background: #333; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        /* Filter Pills */
        .filter-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-filter {
            padding: 8px 18px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #eee;
            background: #fff;
            color: #555;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-filter:hover {
            background: #fafafa;
            border-color: #ddd;
            transform: translateY(-1px);
        }
        .btn-filter.active {
            background: #000;
            color: #fff;
            border-color: #000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        /* Specific Active Colors */
        .btn-filter[data-status="Pending"].active { background: #fff8e1; color: #f57f17; border-color: #ffe0b2; }
        .btn-filter[data-status="Confirmed"].active { background: #e3f2fd; color: #1565c0; border-color: #bbdefb; }
        .btn-filter[data-status="Delivered"].active { background: #e8f5e9; color: #2e7d32; border-color: #c8e6c9; }

        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            background: #fff;
            color: #444;
            border: 1px solid #e0e0e0;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 14px;
        }
        .btn-back:hover {
            background: #f9f9f9;
            color: #000;
            border-color: #ccc;
            transform: translateX(-3px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        /* Action Buttons */
        .edit-btn {
            background: #f5f5f5;
            color: #333;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: 0.2s;
        }
        .edit-btn:hover { background: #e0e0e0; color: #000; }
        
        .delete-btn {
             background: #ffebee;
             color: #c62828;
             padding: 6px 12px;
             border-radius: 4px;
             text-decoration: none;
             font-size: 12px;
             font-weight: 500;
             transition: 0.2s;
        }
        .delete-btn:hover { background: #ffcdd2; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        K.M. ADMIN
    </div>
    <ul class="nav-links">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="products.php" class="nav-link <?php echo $current_page == 'products.php' || $current_page == 'add_product.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
        </li>
        <li class="nav-item">
            <a href="orders.php" class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
        </li>
        <li class="nav-item">
            <a href="customers.php" class="nav-link <?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Customers
            </a>
        </li>
        <li class="nav-item">
            <a href="coupons.php" class="nav-link <?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i> Marketing
            </a>
        </li>
         <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-header">
        <div class="header-left">
            <!-- Search or Breadcrumbs could go here -->
        </div>
        <div class="header-right user-menu">
            <a href="../index.php" target="_blank" style="margin-right:20px; color:#555; text-decoration:none; font-size:14px;"><i class="fas fa-external-link-alt"></i> View Website</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?> (<?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Staff'); ?>)</span>
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="content-wrapper">
