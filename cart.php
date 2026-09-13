<?php
session_start();
require 'db.php';

if(!isset($_SESSION['admin']) || $_SESSION['admin'] === true){
    header('Location: login.php');
    exit;
}

$message = '';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// All cart operations keep products.stock in sync with order_items.quantity
// (order_id = 0 rows are "in cart, not yet ordered").
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    if(isset($_POST['remove'])){
        $product_id = (int)$_POST['product_id'];
        $conn->begin_transaction();
        try{
            $stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_id = 0 AND user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $stmt->bind_result($qty);
            if($stmt->fetch()){
                $stmt->close();

                $stmt2 = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt2->bind_param("ii", $qty, $product_id);
                $stmt2->execute();
                $stmt2->close();

                $stmt3 = $conn->prepare("DELETE FROM order_items WHERE order_id = 0 AND user_id = ? AND product_id = ?");
                $stmt3->bind_param("ii", $user_id, $product_id);
                $stmt3->execute();
                $stmt3->close();
            }else{
                $stmt->close();
            }
            $conn->commit();
            $message = "Product removed from cart";
        }catch(Exception $e){
            $conn->rollback();
            $message = "Error removing product";
        }
        echo "<script>alert('Product removed from the cart..!'); window.location = 'cart.php'</script>";
        exit;
    }

    if(isset($_POST['update_qty'])){
        $product_id = (int)$_POST['product_id'];
        $new_qty = max(0, (int)$_POST['quantity']);

        $conn->begin_transaction();
        try{
            $stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_id = 0 AND user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $stmt->bind_result($old_qty);
            if(!$stmt->fetch()){
                $stmt->close();
                throw new Exception("Item not found in cart");
            }
            $stmt->close();

            $diff = $new_qty - $old_qty; // >0 needs more stock reserved, <0 gives stock back

            if($diff > 0){
                $stmtS = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $stmtS->bind_param("iii", $diff, $product_id, $diff);
                $stmtS->execute();
                $ok = $stmtS->affected_rows > 0;
                $stmtS->close();
                if(!$ok){
                    throw new Exception("Not enough stock available");
                }
            }elseif($diff < 0){
                $restore = abs($diff);
                $stmtS = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmtS->bind_param("ii", $restore, $product_id);
                $stmtS->execute();
                $stmtS->close();
            }

            if($new_qty <= 0){
                $stmtD = $conn->prepare("DELETE FROM order_items WHERE order_id = 0 AND user_id = ? AND product_id = ?");
                $stmtD->bind_param("ii", $user_id, $product_id);
                $stmtD->execute();
                $stmtD->close();
            }else{
                $stmtU = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_id = 0 AND user_id = ? AND product_id = ?");
                $stmtU->bind_param("iii", $new_qty, $user_id, $product_id);
                $stmtU->execute();
                $stmtU->close();
            }

            $conn->commit();
            $message = "Cart updated";
        }catch(Exception $e){
            $conn->rollback();
            $message = $e->getMessage();
        }
        echo "<script>window.location = 'cart.php'</script>";
        exit;
    }

    if(isset($_POST['empty_table_btn'])){
        $conn->begin_transaction();
        try{
            $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = 0 AND user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()){
                $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->bind_param("ii", $row['quantity'], $row['product_id']);
                $stmt->execute();
                $stmt->close();
            }
           // $stmt->close();
            $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = 0 AND user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            $message = "Cart emptied";
        }catch(Exception $e){
            $conn->rollback();
            $message = "Error emptying cart";
        }
        echo "<script>alert('Cart emptied..!'); window.location = 'cart.php'</script>";
        exit;
    }
}

$query = $conn->prepare(
    "SELECT o.id AS cart_id, o.product_id, o.quantity, p.name, p.price, p.stock
     FROM order_items o INNER JOIN products p ON o.product_id = p.id
    WHERE o.order_id = 0 AND o.user_id = ?"
);
$query->bind_param("i", $user_id);
$query->execute();
$cart_result = $query->get_result();
$cart_items = $cart_result->fetch_all(MYSQLI_ASSOC);
$query->close();
$cart_total = 0;
foreach($cart_items as $cart_item){
    $cart_total += (float)$cart_item['price'] * (int)$cart_item['quantity'];
}
$cart_count = count($cart_items);
?>
<?php include 'user_header.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.qty-btn.plus').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.quantity-selector').querySelector('.qty-input');
      const max = parseInt(input.dataset.max, 10);
      let currentVal = parseInt(input.value, 10) || 0;
      if (currentVal < max) {
        input.value = currentVal + 1;
      }
    });
  });

  document.querySelectorAll('.qty-btn.minus').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.quantity-selector').querySelector('.qty-input');
      let currentVal = parseInt(input.value, 10) || 0;
      if (currentVal > 0) {
        input.value = currentVal - 1;
      }
    });
  });
});
</script>

<main class="store-page-shell cart-page">
    <div class="store-page-heading">
        <div><span class="eyebrow">Your selection</span><h1>Shopping cart</h1><p>Review your products before checkout.</p></div>
        <a class="text-link" href="user_page.php"><i class="fa fa-arrow-left"></i> Continue shopping</a>
    </div>
    <div class="cart-layout">
        <section class="cart-products-panel">
            <?php if($message){ ?><div class="store-message"><?php echo htmlspecialchars($message); ?></div><?php } ?>
            <?php if(empty($cart_items)){ ?>
                <div class="empty-store-state"><i class="fa fa-shopping-bag"></i><h2>Your cart is empty</h2><p>Add something you love from the collection.</p><a class="primary-store-button" href="user_page.php">Browse products</a></div>
            <?php } else { ?>
                <div class="cart-panel-heading"><h2>Products list</h2><span><?php echo $cart_count; ?> item<?php echo $cart_count === 1 ? '' : 's'; ?></span></div>
                <?php foreach($cart_items as $r) { ?>
                    <form method="post" class="cart-product-row">
                        <input type="hidden" name="product_id" value="<?php echo (int)$r['product_id']; ?>">
                        <img src="IMAGE/<?php echo htmlspecialchars($r['name']); ?>.jpg" alt="<?php echo htmlspecialchars($r['name']); ?>">
                        <div class="cart-product-info"><span>Everyday essential</span><h3><?php echo htmlspecialchars($r['name']); ?></h3><p>In stock and ready to ship</p><strong>₹<?php echo number_format((float)$r['price'], 2); ?></strong></div>
                        <div class="cart-product-actions"><div class="quantity-selector"><button type="button" class="qty-btn minus" aria-label="Decrease quantity">-</button><input type="number" class="qty-input" name="quantity" value="<?php echo (int)$r['quantity']; ?>" min="0" data-max="<?php echo (int)$r['quantity'] + (int)$r['stock']; ?>"><button type="button" class="qty-btn plus" aria-label="Increase quantity">+</button></div><small><?php echo (int)$r['stock']; ?> more in stock</small><button type="submit" class="quiet-button" name="update_qty">Update</button><button type="submit" class="remove-button" name="remove">Remove</button></div>
                    </form>
                <?php } ?>
                <form method="post" class="empty-cart-form"><button type="submit" name="empty_table_btn" class="text-link"><i class="fa fa-trash"></i> Empty cart</button></form>
            <?php } ?>
        </section>
        <aside class="price-details-panel"><span class="eyebrow">Summary</span><h2>Price details</h2><div class="price-line"><span>Price (<?php echo $cart_count; ?> items)</span><strong>₹<?php echo number_format($cart_total, 2); ?></strong></div><div class="price-line"><span>Delivery charges</span><strong class="free-label">FREE</strong></div><div class="price-total"><span>Amount payable</span><strong>₹<?php echo number_format($cart_total, 2); ?></strong></div><?php if(!empty($cart_items)){ ?><a href="checkout.php" class="primary-store-button">Proceed to checkout <i class="fa fa-arrow-right"></i></a><?php } ?></aside>
    </div>
</main>


<?php include 'footer.php'; ?>
