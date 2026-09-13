<?php
session_start();
require 'db.php';

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit;
}

$message = '';

// All cart operations keep products.stock in sync with order_items.quantity
// (order_id = 0 rows are "in cart, not yet ordered").
if($_SERVER['REQUEST_METHOD'] == 'POST'){

    if(isset($_POST['remove'])){
        $product_id = (int)$_POST['product_id'];
        $conn->begin_transaction();
        try{
            $stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_id = 0 AND product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->bind_result($qty);
            if($stmt->fetch()){
                $stmt->close();

                $stmt2 = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt2->bind_param("ii", $qty, $product_id);
                $stmt2->execute();
                $stmt2->close();

                $stmt3 = $conn->prepare("DELETE FROM order_items WHERE order_id = 0 AND product_id = ?");
                $stmt3->bind_param("i", $product_id);
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
            $stmt = $conn->prepare("SELECT quantity FROM order_items WHERE order_id = 0 AND product_id = ?");
            $stmt->bind_param("i", $product_id);
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
                $stmtD = $conn->prepare("DELETE FROM order_items WHERE order_id = 0 AND product_id = ?");
                $stmtD->bind_param("i", $product_id);
                $stmtD->execute();
                $stmtD->close();
            }else{
                $stmtU = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_id = 0 AND product_id = ?");
                $stmtU->bind_param("ii", $new_qty, $product_id);
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
            $res = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = 0");
            while($row = $res->fetch_assoc()){
                $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmt->bind_param("ii", $row['quantity'], $row['product_id']);
                $stmt->execute();
                $stmt->close();
            }
            $conn->query("DELETE FROM order_items WHERE order_id = 0");
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

$query = $conn->query(
    "SELECT o.id AS cart_id, o.product_id, o.quantity, p.name, p.price, p.stock
     FROM order_items o INNER JOIN products p ON o.product_id = p.id
     WHERE o.order_id = 0"
);
$cart_items = $query ? $query->fetch_all(MYSQLI_ASSOC) : [];
?>
<?php include 'user_header.php'; ?>

<html>
<head>
<title>Cart Page</title>
<style>
 <link rel="stylesheet" href="style.css">
</style>
<link rel="stylesheet" href="js/jquery-1.12.1.js" >
<link href="js/bootstrap.min.css" rel="stylesheet">

</head>

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

<body class="">

<div class="bg-light">
<div class="container-fluid">
        <div class="row px-xl-5">
            <div class="col-12">
                <nav class="breadcrumb bg-light mb-30">
                    <a class="breadcrumb-item text-dark" href="user_page.php">Home</a>
                    <a class="breadcrumb-item text-dark" href="user_page.php">Shop</a>
                    <span class="breadcrumb-item active">Shop Cart</span>
                </nav>
            </div>
        </div>
    </div>


	<div class="container-fluid">
	<h3>Products List</h3>
	</div>
		<div class="container-fluid">
			<div class="row px-xl-5">
				<div class="col-md-6">
					<div class="shopping-cart">
						<h4>My Cart</h4>
						<?php if($message){ ?>
							<p class="msg"><?php echo htmlspecialchars($message); ?></p>
						<?php } ?>
						<?php if(empty($cart_items)){ ?>
							<p>Your cart is empty.</p>
						<?php }
						foreach($cart_items as $r) { ?>
									 <form method="post" class="cart-items" >

										<div class="border rounded" >
											<div class="pt-4"  >
												<div class="col-sm-12 ">
													<div class="col-md-4 pl-0 ">
														<img src="image/<?php echo htmlspecialchars($r['name']); ?>.jpg">
												</div>
												<div class="col-md-8 offset-md-4">
													<h5 class="pt-2"><?php echo htmlspecialchars($r['name']); ?></h5>
													<small class="text-secondary">Seller: dailytuition</small>
													<h5 class="pt-2">₹<?php echo $r['price']; ?></h5>
													<input type='hidden' name='product_id' value='<?php echo $r['product_id']; ?>'>

													<div class="col-md-3 py-3 ">
														<div class="quantity-selector">
														<button type="button" class="qty-btn minus" aria-label="Decrease quantity" >−	</button>
														<input type="number" class="qty-input" name="quantity" value='<?php echo $r['quantity']; ?>' min="0" data-max="<?php echo $r['quantity'] + $r['stock']; ?>">
														<button type="button" class="qty-btn plus" aria-label="Increase quantity" >+</button>
														</div>
														<small class="text-muted"><?php echo $r['stock']; ?> more in stock</small>
													</div>
													</div>
												<div class="container-fluid">
													<button type="submit" class="btn btn-warning" name="update_qty">Update Qty</button>
													<button type="submit" class="btn btn-danger mx-2" name="remove">Remove Product</button>
													<button type="submit" class="btn btn-danger mx-2" name="empty_table_btn">Empty All Data</button>
												</div>

												</div>
											</div>
										</div>
									</form>
								<?php } ?>
					</div>
				</div>

        <div class="col-lg-4 offset-md-7 border rounded mt-5 bg-white h-250" >


            <div class="pt-4" align="center">
			<center>
                <h6>PRICE DETAILS</h6>
			</center>
                <hr>
                <div class="row price-details">
                    <div class="col-md-4 offset-md-1">
                        <?php
						$total = 0;
						foreach($cart_items as $r){
							$total += $r['price'] * $r['quantity'];
						}
						$count = count($cart_items);
						echo "<h6>Price ($count items)</h6>";
                        ?>
                        <h6>Delivery Charges</h6>
                        <hr>
                        <h6>Amount Payable</h6>
                    </div>
                    <div class="col-md-6" align="center">
                        <h6>₹<?php echo $total; ?></h6>
                        <h6 class="text-success" >FREE</h6>
                        <hr>
                        <h6>
                            ₹<?php echo $total; ?>
                            </h6>
                    </div>
                </div>
	                        <button class="btn btn-block btn-primary font-weight-bold my-3 py-3">Proceed To Checkout</button>

            </div>
            </div>
		</div>

	</div>
</div>


<?php include 'footer.php'; ?>
