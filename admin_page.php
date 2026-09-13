<?php
session_start();

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit;
}

require 'db.php';

$total_products_query = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$total_products = $total_products_query ? ($total_products_query->fetch_assoc()['total_products'] ?? 0) : 0;

$total_orders_query = $conn->query("SELECT COUNT(DISTINCT order_id) AS total_orders FROM order_items WHERE order_id != 0");
$total_orders = $total_orders_query ? ($total_orders_query->fetch_assoc()['total_orders'] ?? 0) : 0;

$total_revenue_query = $conn->query("SELECT COALESCE(SUM(quantity * price), 0) AS total_revenue FROM order_items WHERE order_id != 0");
$total_revenue = $total_revenue_query ? ($total_revenue_query->fetch_assoc()['total_revenue'] ?? 0) : 0;

$low_stock_query = $conn->query("SELECT COUNT(*) AS low_stock FROM products WHERE stock < 10");
$low_stock = $low_stock_query ? ($low_stock_query->fetch_assoc()['low_stock'] ?? 0) : 0;

$recent_orders_query = $conn->query(
    "SELECT order_id, SUM(quantity) AS total_items, SUM(quantity * price) AS total_amount
     FROM order_items
     WHERE order_id != 0
     GROUP BY order_id
     ORDER BY MAX(id) DESC"
);
$recent_orders = $recent_orders_query ? $recent_orders_query->fetch_all(MYSQLI_ASSOC) : [];
?>

<html>
<head>
<title>Admin</title>
<link rel="stylesheet" type="text/css" href="style.css">

</head>
<body class="admin-body">
<?php include 'header.php'; ?>

<main class="admin-main">
    <div class="admin-page-heading"><div><span class="admin-eyebrow">Admin workspace</span><h1>Overview</h1><p>Keep your catalog, orders, and stock moving smoothly.</p></div><a class="admin-outline-button" href="products.php"><i class="fa fa-plus"></i> Add product</a></div>
    <div class="admin-stat-grid">
        <div class="admin-stat-card"><span class="admin-stat-icon mint"><i class="fa fa-box"></i></span><div><span>Total products</span><strong><?php echo number_format($total_products); ?></strong></div></div>
        <div class="admin-stat-card"><span class="admin-stat-icon gold"><i class="fa fa-receipt"></i></span><div><span>Total orders</span><strong><?php echo number_format($total_orders); ?></strong></div></div>
        <div class="admin-stat-card"><span class="admin-stat-icon blue"><i class="fa fa-wallet"></i></span><div><span>Total revenue</span><strong>₹<?php echo number_format($total_revenue, 2); ?></strong></div></div>
        <div class="admin-stat-card"><span class="admin-stat-icon coral"><i class="fa fa-exclamation-triangle"></i></span><div><span>Low stock</span><strong><?php echo number_format($low_stock); ?></strong></div></div>
    </div>
    <section class="admin-panel"><div class="admin-panel-heading"><div><span class="admin-eyebrow">Activity</span><h2>Recent orders</h2></div><a href="admin_orders.php" class="admin-text-link">View all <i class="fa fa-arrow-right"></i></a></div>
        <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order ID</th><th>Items</th><th>Status</th><th>Total</th></tr></thead><tbody>
        <?php if (empty($recent_orders)): ?><tr><td colspan="4" class="admin-empty-cell">No orders found.</td></tr><?php else: foreach ($recent_orders as $order): ?><tr><td><strong>#<?php echo htmlspecialchars($order['order_id']); ?></strong></td><td><?php echo (int)$order['total_items']; ?> items</td><td><span class="admin-status pending">Pending</span></td><td><strong>₹<?php echo number_format((float)$order['total_amount'], 2); ?></strong></td></tr><?php endforeach; endif; ?>
        </tbody></table></div>
    </section>
</main>

</body>
</html>
