<?php
session_start();

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
	header('Location: login.php');
	exit;
}

require 'db.php';

$allowed_statuses = ['Placed', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
$message = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])){
	$order_id = (int)($_POST['order_id'] ?? 0);
	$status = trim($_POST['status'] ?? '');
	if($order_id > 0 && in_array($status, $allowed_statuses, true)){
		$status_query = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
		$status_query->bind_param('si', $status, $order_id);
		$status_query->execute();
		$status_query->close();
		$message = 'Order status updated.';
	} else {
		$message = 'Invalid order status.';
	}
}

$orders_query = $conn->query(
	"SELECT o.id AS order_id, o.total_amount, o.status, o.created_at,
			COALESCE(NULLIF(CONCAT_WS(' ', c.first_name, c.last_name), ''), u.name, 'Guest') AS customer_name,
			COALESCE(NULLIF(c.email, ''), u.email, '') AS customer_email,
			c.mobile, c.address_line1, c.address_line2, c.city, c.state, c.country, c.zip_code,
			oi.id AS item_id, oi.quantity, oi.price, p.name AS product_name
	 FROM orders o
	 LEFT JOIN customers c ON c.id = o.customer_id
	 LEFT JOIN users u ON u.id = o.user_id
	 LEFT JOIN order_items oi ON oi.order_id = o.id
	 LEFT JOIN products p ON p.id = oi.product_id
	 ORDER BY o.created_at DESC, o.id DESC, oi.id ASC"
);
$order_rows = $orders_query ? $orders_query->fetch_all(MYSQLI_ASSOC) : [];
$orders = [];
foreach($order_rows as $row){
	$order_id = (int)$row['order_id'];
	if(!isset($orders[$order_id])){
		$orders[$order_id] = [
			'order_id' => $order_id,
			'total_amount' => (float)$row['total_amount'],
			'status' => $row['status'],
			'created_at' => $row['created_at'],
			'customer_name' => $row['customer_name'],
			'customer_email' => $row['customer_email'],
			'mobile' => $row['mobile'] ?? '',
			'address' => implode(', ', array_filter([
				$row['address_line1'] ?? '', $row['address_line2'] ?? '',
				$row['city'] ?? '', $row['state'] ?? '', $row['country'] ?? '', $row['zip_code'] ?? ''
			])),
			'items' => []
		];
	}
	if($row['item_id'] !== null){
		$orders[$order_id]['items'][] = [
			'name' => $row['product_name'] ?: 'Product #' . $row['item_id'],
			'quantity' => (int)$row['quantity'],
			'price' => (float)$row['price']
		];
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Manage Orders | Ecommerce Admin</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
	<style>
		.admin-orders { margin-left: 250px; padding: 2.5rem; background: #fbf8f2; min-height: 100vh; }
		.admin-order-card { border: 1px solid #e8e1d5; border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(16, 42, 67, .07); }
		.admin-order-card + .admin-order-card { margin-top: 1.25rem; }
		.admin-order-header { border-bottom: 1px solid #e8e1d5; padding: 1.25rem 1.5rem; }
		.admin-order-body { padding: 1.5rem; }
		.admin-order-meta { color: #718096; font-size: .9rem; }
		.admin-customer { border-right: 1px solid #e8e1d5; }
		.status-form { min-width: 220px; }
		@media (max-width: 767.98px) {
			.admin-orders { margin-left: 0; padding: 1rem; }
			.admin-customer { border-right: 0; border-bottom: 1px solid #e8e1d5; padding-bottom: 1rem; margin-bottom: 1rem; }
			.status-form { min-width: 0; width: 100%; margin-top: 1rem; }
		}
	</style>
</head>
<body class="admin-body">
<?php include 'header.php'; ?>
<main class="admin-orders">
	<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
		<div>
			<p class="text-uppercase text-muted small mb-1">Admin workspace</p>
			<h1 class="mb-1">Manage Orders</h1>
			<p class="text-muted mb-0">Review customer details, products, totals, and delivery progress.</p>
		</div>
		<a href="admin_page.php" class="btn btn-outline-secondary">Back to dashboard</a>
	</div>
	<?php if($message !== ''){ ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php } ?>
	<?php if(empty($orders)){ ?>
		<div class="admin-order-card p-5 text-center"><h3>No orders yet</h3><p class="text-muted mb-0">Placed customer orders will appear here.</p></div>
	<?php } else { foreach($orders as $order){ ?>
		<article class="admin-order-card">
			<div class="admin-order-header d-flex flex-wrap justify-content-between align-items-center gap-3">
				<div><h3 class="h5 mb-1">Order #<?php echo $order['order_id']; ?></h3><div class="admin-order-meta">Placed <?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($order['created_at']))); ?></div></div>
				<form method="post" class="status-form d-flex gap-2">
					<input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
					<select name="status" class="form-select" aria-label="Order status">
						<?php foreach($allowed_statuses as $status){ ?><option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo $status; ?></option><?php } ?>
					</select>
					<button type="submit" name="update_status" class="admin-save-button"><i class="fa fa-check"></i><span>Save</span></button>
				</form>
			</div>
			<div class="admin-order-body"><div class="row g-4">
				<div class="col-lg-4 admin-customer"><h4 class="h6 text-uppercase">Customer information</h4><p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p><p class="admin-order-meta mb-1"><?php echo htmlspecialchars($order['customer_email']); ?></p><p class="admin-order-meta mb-1"><?php echo htmlspecialchars($order['mobile'] ?: 'Phone not provided'); ?></p><p class="admin-order-meta mb-0"><?php echo htmlspecialchars($order['address'] ?: 'Address not provided'); ?></p></div>
				<div class="col-lg-5"><h4 class="h6 text-uppercase">Ordered products</h4><?php foreach($order['items'] as $item){ ?><div class="d-flex justify-content-between border-bottom py-2 gap-3"><span><?php echo htmlspecialchars($item['name']); ?> <small class="text-muted">x<?php echo $item['quantity']; ?></small></span><strong>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></div><?php } ?></div>
				<div class="col-lg-3"><h4 class="h6 text-uppercase">Order total</h4><div class="display-6">₹<?php echo number_format($order['total_amount'], 2); ?></div><span class="badge bg-secondary mt-2"><?php echo htmlspecialchars($order['status']); ?></span></div>
			</div></div>
		</article>
	<?php } } ?>
</main>
</body>
</html>