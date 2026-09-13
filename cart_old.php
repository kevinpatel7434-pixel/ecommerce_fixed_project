<?php session_start(); require 'db.php'; 

$current_page = basename($_SERVER['PHP_SELF']);
$sql = "SELECT * FROM order_items";
$result = mysqli_query($conn, $sql);

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit;
}
require 'db.php';

$message = '';
$query = $conn->query("SELECT * FROM order_items o INNER JOIN products p ON o.product_id = p.id;");
$product_ids = "";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare(
        "DELETE FROM order_items WHERE product_id = ?"
    );
    $stmt->bind_param("i", $product_id);
    if($stmt->execute()){
        $message = "Product Remove successfully";
		echo "<script>alert('Product Remove in the cart..!')</script>";
        echo "<script>window.location = 'cart.php'</script>";
		
    }else{
        $message = "Error Remove product";
    }
}
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
  document.getElementById('increase-qty').addEventListener('click', () => {
    const qtyInput = document.getElementById('product-qty');
    let currentVal = parseInt(qtyInput.value) || 0;
    qtyInput.value = currentVal + 1;
  });

  document.getElementById('decrease-qty').addEventListener('click', () => {
    const qtyInput = document.getElementById('product-qty');
    let currentVal = parseInt(qtyInput.value) || 0; // Added fallback to 0
    if (currentVal > 0) {
      qtyInput.value = currentVal - 1;
    }
  });
});

</script>

<body class="">




<div class="bg-light">

<nav class="">
	<div class="container-fluid">
	<h3>Products List</h3>
	</div>
</nav>
		<div class="container-fluid">
			<div class="row px-xl-5">
				<div class="col-md-6">
					<div class="shopping-cart">
						<h4>My Cart</h4>
							<?php while($r = $query->fetch_assoc()) { ?>
										 <form method="post" class="cart-items" >
											<?php if($message){ ?>
												<p class="msg"><?php echo $message; ?></p>
											<?php } ?>

											<div class="border rounded" >
												<div class="pt-4"  >
													<div class="col-sm-12 ">
														<div class="col-md-4 pl-0 ">
															<img src="image/<?php echo $r['name']; ?>.jpg">
															<!-- Font Awesome <img src=$productimg alt="Image1" class="img-fluid">-->
													</div>
													<div class="col-md-8 offset-md-4">
														<h5 class="pt-2"><?php echo $r['name']; ?></h5>
														<small class="text-secondary">Seller: dailytuition</small>
														<h5 class="pt-2"><?php echo $r['price']; ?></h5>
														<input type='hidden' name='product_id' value='<?php echo $r['id']; ?>'>

														<div class="col-md-3 py-3 ">
															<div class="quantity-selector">
															<button type="button" class="qty-btn minus" id="decrease-qty" aria-label="Decrease quantity" >−	</button>
															<input type="number" class="qty-input" id="product-qty" value='<?php echo $r['quantity']; ?>' min="0" >
															<button type="button" class="qty-btn plus" id="increase-qty" aria-label="Increase quantity" >+</button>
															</div>
														</div>
														</div>
													<div class="container-fluid">
														<button type="submit" class="btn btn-warning">Save for Later</button>
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
<?php?>
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
							if ($current_page == 'cart.php'){
								//$product_ids = "";
								if(mysqli_num_rows($result) > 0){
									while ($row = mysqli_fetch_assoc($result))
										{
											//Display your cart items here
										
										foreach ($row as $id)
										$id;
										$price = $row['price'] * $row['quantity'];
										//echo $price;
												{												
													//$total = $total + (int)$row['price'];
													$total = $total + (int)$price;
												}
										}
							
									$count = mysqli_num_rows($result) ;
									echo "<h6>Price ($count items)</h6>";
						
							//		while ($row = mysqli_fetch_assoc($result))
							//		{
							//			$product_id = $row['product_id'];
							//			echo $row['product_id'];
							//			foreach ($product_id as $id)
							//				{
							//					if ($row['id'] == $id)
							//						{ 
							//							$total = $total + (int)$row['product_price'];
							//						}
							//				}
							//		}
					}					else
									{
										echo "<h5>Cart is Empty</h5>";
									}
							}
                            if ($current_page == 'cart.php'){
								while ($row = mysqli_fetch_assoc($result)){
								echo 'hiii';
								echo $row;
								echo "hiii";
                               $count  = count($sql);
                               echo "<h6>Price ($count items)</h6>";
								}
                            }else{
                                echo "<h6>Price (0  items)</h6>";
                            }
                        ?>
                        <h6>Delivery Charges</h6>
                        <hr>
                        <h6>Amount Payable</h6>
                    </div>
                    <div class="col-md-6" align="center">
                        <h6>$<?php echo $total; ?></h6>
                        <h6 class="text-success" >FREE</h6>
                        <hr>
                        <h6>
                            $<?php echo $total; ?>
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
