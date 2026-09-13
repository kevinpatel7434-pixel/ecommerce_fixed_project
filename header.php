<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css">
<link rel="stylesheet" href="style.css">
<aside class="admin-sidebar">
    <a href="admin_page.php" class="admin-brand"><span class="admin-brand-mark">N</span><span>nivo Admin</span></a>
    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="admin_page.php"><i class="fa fa-chart-pie"></i><span>Overview</span></a>
        <a href="products.php"><i class="fa fa-box"></i><span>Products</span></a>
        <a href="admin_orders.php"><i class="fa fa-receipt"></i><span>Orders</span></a>
        <a href="customers.php"><i class="fa fa-users"></i><span>Customers</span></a>
        <a href="reports.php"><i class="fa fa-chart-bar"></i><span>Reports</span></a>
    </nav>
    <div class="admin-sidebar-footer">
        <span class="admin-user-dot"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></span>
        <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
        <a href="logout.php" aria-label="Logout" title="Logout"><i class="fa fa-sign-out-alt"></i></a>
    </div>
</aside>
