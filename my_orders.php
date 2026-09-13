<?php
session_start();
require 'db.php';

if(!isset($_SESSION['admin']) || $_SESSION['admin'] === true){
    header('Location: login.php');
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$orders_query = $conn->prepare(
    "SELECT o.id AS order_id, o.total_amount, o.status, o.created_at,
            oi.quantity, oi.price, p.name
     FROM orders o
     INNER JOIN order_items oi ON oi.order_id = o.id AND oi.user_id = o.user_id
     INNER JOIN products p ON p.id = oi.product_id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC, o.id DESC, oi.id ASC"
);
$orders_query->bind_param("i", $user_id);
$orders_query->execute();
$order_rows = $orders_query->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_query->close();

$orders = [];
foreach($order_rows as $row){
    $order_id = (int)$row['order_id'];
    if(!isset($orders[$order_id])){
        $orders[$order_id] = [
            'order_id' => $order_id,
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
    }
    $orders[$order_id]['items'][] = $row;
}
?>
<?php include 'user_header.php'; ?>

<main class="store-page-shell orders-page">
    <div class="store-page-heading">
        <div><span class="eyebrow">Your account</span><h1>My orders</h1><p>Track your purchases and revisit what you ordered.</p></div>
        <a class="text-link" href="user_page.php"><i class="fa fa-arrow-left"></i> Continue shopping</a>
    </div>
    <?php if(empty($orders)){ ?>
        <div class="empty-store-state orders-empty"><i class="fa fa-receipt"></i><h2>No orders yet</h2><p>Your completed purchases will appear here.</p><a class="primary-store-button" href="user_page.php">Browse products</a></div>
    <?php } else { ?>
        <div class="orders-list">
            <?php foreach($orders as $order){ ?>
                <article class="order-card">
                    <header class="order-card-header">
                        <div><span class="eyebrow">Order #<?php echo (int)$order['order_id']; ?></span><p>Placed <?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($order['created_at']))); ?></p></div>
                        <div class="order-card-total"><span class="order-status"><?php echo htmlspecialchars($order['status']); ?></span><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></div>
                    </header>
                    <div class="order-items-list">
                        <?php foreach($order['items'] as $item){ ?>
                            <div class="order-item-row">
                                <img src="IMAGE/<?php echo htmlspecialchars($item['name']); ?>.jpg" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="order-item-info"><h3><?php echo htmlspecialchars($item['name']); ?></h3><span>Quantity: <?php echo (int)$item['quantity']; ?></span></div>
                                <strong>₹<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></strong>
                            </div>
                        <?php } ?>
                    </div>
                    <footer class="order-card-footer"><span><i class="fa fa-check-circle"></i> Order confirmed</span><a href="user_page.php">Shop again <i class="fa fa-arrow-right"></i></a></footer>
                </article>
            <?php } ?>
        </div>
    <?php } ?>
</main>

<?php include 'footer.php'; ?>
