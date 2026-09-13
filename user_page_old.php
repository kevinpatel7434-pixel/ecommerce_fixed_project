<?php
session_start();

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit;
}
require 'db.php';
$current_page = basename($_SERVER['PHP_SELF']);

$query_products = $conn->query("SELECT * FROM products");

$query_order_items = $conn->query("SELECT * FROM order_items");

$sql = 'SELECT * FROM order_items where order_id =! 0';
$result = mysqli_query($conn, $sql);
$message = '';
if(isset($_REQUEST ['add'])){
							$product_id = $_POST['product_id'];
							$order_id = $_POST['product_id'];
							$name = trim($_POST['name']);
							$price = $_POST['price'];
							$quantity = $_POST['quantity'];
			while($o = $query_order_items->fetch_assoc()) {$pr = $o['product_id'];
			}
//		echo $_POST['product_id'];
//		echo $quantity;

	
						if($order_id>0 && $pr === $order_id ){
							echo "Error";
							$stmt = $conn->prepare(
													"update order_items set  quantity =2  where id = 1 "
												);
										if($stmt->execute())
											{
												$message = "Product added to cart successfully";
											}
											else 
											{
												$message = "Error adding product";
											}
										
			
										}else if($order_id>0 ){ 
										$stmt = $conn->prepare(
														"INSERT INTO order_items(id, order_id, product_id, quantity,price) VALUES (?, ?, ?, ? ,?)"
															);
										$stmt->bind_param("iiiid",$order_id, $order_id, $product_id, $quantity, $price);
										if($stmt->execute())
											{
												$message = "Product added to cart successfully";
											}
											else 
											{
												$message = "Error adding product";
											}
										}
}
?>

<?php include 'user_header.php'; ?>
<html>
<head>
<title>Ecommerce Store</title>

</head>

<div class="hero">
	<h4>This is a shoping website.</h4>
</div>

<!-- Carousel Start -->
    <div class="container-fluid mb-3">
        <div class="row px-xl-5">
            <div class="col-lg-8">
                <div id="header-carousel" class="carousel slide carousel-fade mb-30 mb-lg-0" data-ride="carousel">
                    <ol class="carousel-indicators">
                        <li data-target="#header-carousel" data-slide-to="0" class="active"></li>
                        <li data-target="#header-carousel" data-slide-to="1"></li>
                        <li data-target="#header-carousel" data-slide-to="2"></li>
                    </ol>
                    <div class="carousel-inner">
                        <div class="carousel-item position-relative active" style="height: 430px;">
                            <img class="position-absolute w-100 h-100" src="img/carousel-1.jpg" style="object-fit: cover;">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">Men Fashion</h1>
                                    <p class="mx-md-5 px-5 animate__animated animate__bounceIn">Lorem rebum magna amet lorem magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum diam</p>
                                    <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="#">Shop Now</a>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item position-relative" style="height: 430px;">
                            <img class="position-absolute w-100 h-100" src="img/carousel-2.jpg" style="object-fit: cover;">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">Women Fashion</h1>
                                    <p class="mx-md-5 px-5 animate__animated animate__bounceIn">Lorem rebum magna amet lorem magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum diam</p>
                                    <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="#">Shop Now</a>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item position-relative" style="height: 430px;">
                            <img class="position-absolute w-100 h-100" src="img/carousel-3.jpg" style="object-fit: cover;">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h1 class="display-4 text-white mb-3 animate__animated animate__fadeInDown">Kids Fashion</h1>
                                    <p class="mx-md-5 px-5 animate__animated animate__bounceIn">Lorem rebum magna amet lorem magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum diam</p>
                                    <a class="btn btn-outline-light py-2 px-4 mt-3 animate__animated animate__fadeInUp" href="#">Shop Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="product-offer mb-30 " style="height: 200px;">
                    <img class="img-fluid position-absolute"  src="img/offer-1.jpg" alt="image not allow to display">
                    <div class="offer-text position-relative">
						<center>
                        <h6 class="display-4 text-white text-uppercase">Save 20%</h6>
                        <h3 class="text-white  mb-3">Special Offer</h3>
                        <a href="" class="btn btn-warning">Shop Now</a>
						</center>
                    </div>
                </div>
                <div class="product-offer mb-30" style="height: 200px;">
                    <img class="img-fluid position-absolute" src="img/offer-2.jpg" alt="image not allow to display">
                    <div class="offer-text position-relative ">
						<center>
                        <h1 class="display-4 text-white  text-uppercase">Save 20%</h1>
                        <h3 class="text-white mb-3">Special Offer</h3>
                        <a href="" class="btn btn-warning">Shop Now</a>
						</center>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->

<div class="container mt-4">


<h2 id="product">Featured Products</h2>
	<div class="row">
			<?php while($r = $query_products->fetch_assoc()) { ?>
		<div class="col-md-4 ">

			<div class="product-card" >
			<form method="post">
				<img src="image/<?php echo $r['name']; ?>.jpg">
				<h4 class="mt-3"><?php echo $r['name']; ?></h4>
				<input type='hidden' name='name' value='<?php echo $r['name']; ?>'>
				<input type='hidden' name='quantity' value='1'></button>
				
				<p>Premium laptop with Gaming Softwer.</p>
				
				<h5>₹<?php echo $r['price']; ?></h5>
				<input type='hidden' name='price' value='<?php echo $r['price']; ?>'>
				
				<input type='hidden' name='product_id' value='<?php echo $r['id']; ?>'>
				<button type="submit" name="add" class="btn btn-warning ">Add To Cart </button>
	</form>
			</div>

	</div>
		<?php } ?>
</div>

</div>


<?php include 'footer.php'; ?>


