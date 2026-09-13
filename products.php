<?php
session_start();

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
	header('Location: login.php');
	exit;
}

require 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])){
	$delete_id = (int)$_POST['delete_product_id'];
	if($delete_id > 0){
		$delete_query = $conn->prepare('DELETE FROM products WHERE id = ?');
		$delete_query->bind_param('i', $delete_id);
		$delete_query->execute();
		$delete_query->close();
	}
	header('Location: products.php');
	exit;
}

if (isset($_REQUEST["u_id"])){
	$up_id=$_REQUEST["u_id"];
	$_SESSION['up_id'] = $up_id;
	$up_name=$_REQUEST["name"];
	$_SESSION['up_name'] = $up_name;
	$up_price=$_REQUEST["price"];
	$_SESSION['up_price'] = $up_price;
	$up_stock=$_REQUEST["stock"];
	$_SESSION['up_stock'] = $up_stock;
	$up_categories=$_REQUEST["categories"];
	$_SESSION['up_categories'] = $up_categories;
	//echo $up_id;
	
}
?>


<html>
<head>
	<title>Products</title>
	<script src="jquery-1.12.1.js"></script>
</head>
<body class="admin-body">
	<script>
		$(document).ready(function(){
				$("#product").hide();
				
			$("#add_btn").click(function(){
				$("#product").show("slow");
				$("#update_product").hide();
				$("#delete_product").hide();
			});
		$("#back").click(function(){
				$("#product").hide("slow");			
			});		
		});
		
		$(document).ready(function(){
				$("#update_product").hide();
			$("#pr_update").click(function(){
				$("#product").hide();
				$("#update_product").show("slow");
				$("#delete_product").hide();				
			});
		$("#back").click(function(){
				$("#update_product").hide("slow");			
			});		
		});

		$(document).on('click', '.update-product-btn', function(){
			$("#product").hide();
			$("#delete_product").hide();
			$("#update_product").show("slow");

			var form = $("#update_product form");
			form.find("input[name='id']").val($(this).data('id'));
			form.find("input[name='name']").val($(this).data('name'));
			form.find("input[name='price']").val($(this).data('price'));
			form.find("input[name='stock']").val($(this).data('stock'));
			form.find("input[name='categories']").val($(this).data('categories'));
		});
		
		$(document).ready(function(){
				$("#delete_product").hide();
				
			$("#pr_delete").click(function(){
				$("#delete_product").show("slow");
				$("#update_product").hide();

				
			});
		$("#back").click(function(){
				$("#delete_product").hide("slow");			
			});		
		});
	</script>
<?php include 'header.php'; ?>
	<style>
		body{
			font-family:Arial;
			background:;
			padding:;
		}
		table{
			width:100%;
			border-collapse:collapse;
			background:white;
			margin-top: 50px; 
		}
		th,td{
			border:1px solid lightgray;
			padding:12px;
			
		}
		th,td{
			text-align: center;
			
		}
		th{
			background:blue;
			color:white;
		}
		#add_btn,#back{
			display:inline-block;
			margin-bottom:1px;
			margin-left:10px;
			background:;
			color:black;
			padding:8px 15px;
			text-decoration:none;
			width:10%;
		}
		#add_btn{
			background:green;
			color:white;
		}

		#pr_update{
			display:inline-block;
			
			background:;
			color:black;
			padding:8px 15px;
			text-decoration:none;
			
		}

		#update{
			display:inline-block;
			margin-bottom:1px;
			background:;
			color:black;
			padding:8px 15px;
			text-decoration:none;
			width:70%;
		}
		#delete{
			display:inline-block;
			margin-left:10px;
			margin-bottom:1px;
			background: red;
			color:white;
			padding:8px 15px;
			text-decoration:none;
		}
		#pr_delete{
			display:inline-block;
			margin-left:10px;
			margin-bottom:1px;
			background: red;
			color:white;
			padding:8px 15px;
			text-decoration:none;
		}


		.low{
			color:orange;
			font-weight:bold;
		}
		.out{
			color:red;
			font-weight:bold;
		}
		.ok{
			color:green;
			font-weight:bold;
		}

		body{
			background:Tech White;
			font-family:Arial;
			}
		.sidebar{
			width:250px;
			height:100vh;
			position:fixed;
			background:Black ;
			color:white;padding:20px;
			}
		.sidebar a{
					color:white;
					display:block;
					text-decoration:none;
					margin:15px 0;
					padding:10px;
					border-radius:5px;
					}
		.sidebar a:hover{
			background:Blue;
			}
		.content{
				margin-left:270px;
				padding:20px;
				}
		.card{border:none;border-radius:12px;}

		.products-toolbar {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			margin: 1.5rem 0 1rem;
		}
		.products-toolbar button { min-width: 145px; }
		.products-toolbar #add_btn,
		.products-toolbar #pr_update,
		.products-toolbar #back {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 46px;
			border: 0;
			border-radius: 10px;
			box-shadow: 0 5px 14px rgba(16, 42, 67, .12);
			font-size: .86rem;
			font-weight: 700;
			letter-spacing: .01em;
			transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
		}
		.products-toolbar #add_btn {
			background: #176b68;
			color: #fff;
		}
		.products-toolbar #pr_update {
			background: #d69e2e;
			color: #102a43;
		}
		.products-toolbar #back {
			border: 1px solid #cbd5e0;
			background: #fff;
			color: #102a43;
		}
		.products-toolbar #add_btn:hover,
		.products-toolbar #pr_update:hover,
		.products-toolbar #back:hover {
			box-shadow: 0 9px 20px rgba(16, 42, 67, .18);
			transform: translateY(-2px);
		}
		.products-toolbar #add_btn:hover { background: #105653; color: #fff; }
		.products-toolbar #pr_update:hover { background: #b98216; color: #fff; }
		.products-toolbar #back:hover { background: #f5eddf; }
		.products-toolbar #add_btn:focus-visible,
		.products-toolbar #pr_update:focus-visible,
		.products-toolbar #back:focus-visible {
			box-shadow: 0 0 0 3px rgba(214, 158, 46, .35), 0 5px 14px rgba(16, 42, 67, .12);
			outline: 0;
		}
		.products-toolbar button i { margin-right: .45rem; }
		.products-table-wrap {
			overflow-x: auto;
			border: 1px solid #e8e1d5;
			border-radius: 14px;
			background: #fff;
			box-shadow: 0 12px 28px rgba(16, 42, 67, .08);
		}
		table.products-table {
			width: 100%;
			min-width: 900px;
			margin: 0;
			border-collapse: collapse;
			background: #fff;
		}
		table.products-table th {
			border: 0;
			background: #102a43;
			color: #fff;
			font-size: .76rem;
			font-weight: 700;
			letter-spacing: .06em;
			padding: 1rem .9rem;
			text-align: left;
			text-transform: uppercase;
		}
		table.products-table td {
			border: 0;
			border-bottom: 1px solid #eee8df;
			color: #102a43;
			padding: .95rem .9rem;
			vertical-align: middle;
		}
		table.products-table tbody tr:nth-child(even) { background: #fbf8f2; }
		table.products-table tbody tr:hover { background: #f5eddf; }
		table.products-table td:first-child { color: #718096; font-weight: 700; }
		table.products-table td:nth-child(2) { font-weight: 700; }
		table.products-table td:nth-child(3) { color: #176b68; font-weight: 700; }
		table.products-table form { margin: 0; }
		.product-action { white-space: nowrap; }
		.product-action .btn { border-radius: 7px; font-size: .78rem; font-weight: 700; padding: .5rem .7rem; }
		.product-status { display: inline-block; border-radius: 999px; font-size: .72rem; font-weight: 700; padding: .35rem .65rem; white-space: nowrap; }
		.product-status.out { background: #fee2e2; color: #b42318; }
		.product-status.low { background: #fff3cd; color: #946200; }
		.product-status.ok { background: #dcfce7; color: #166534; }
		@media (max-width: 767.98px) {
			.content { margin-left: 0; padding: 1rem; }
			.products-toolbar button { flex: 1 1 140px; }
			.products-toolbar #add_btn,
			.products-toolbar #pr_update,
			.products-toolbar #back { min-height: 48px; }
		}

	</style>
	<div class="content admin-main">
		<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3" style="margin-top: 22px;">
		<div>
			<p class="text-uppercase text-muted small mb-1">Admin workspace</p>
			<h1 class="mb-1">Manage Products</h1>
			<p class="text-muted mb-0">Review Products details, Price, Stock, Categories, Status, and Manage Products.</p>
		</div>
		<a href="admin_page.php" class="btn btn-outline-secondary">Back to dashboard</a>
	</div>
	<div id = "product">
		<?php include 'product_add.php'; ?>
	</div>
	<div id = "update_product">
		<?php include 'product_update.php'; ?>
	</div>
	<div id = "delete_product">
		<?php include 'product_delete.php'; ?>
	</div>
	<div class="products-toolbar">
		<button type="button" class="btn btn-primary" id="add_btn"> <i class="fas fa-plus me-1"></i>Add Product </button>
		<button type="button" class="btn btn-warning" id="pr_update"> <i class="fas fa-pen me-1"></i>Update Product </button>
		<button type="button" class="btn btn-outline-secondary" id="back"> Back </button>
	</div>
	<?php $query = $conn->query("SELECT id, name, price, stock, categories FROM products ORDER BY id DESC"); ?>
		<div class="products-table-wrap">
		<table class="products-table">
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Price</th>
				<th>Stock</th>
				<th>Categories</th>
				<th>Status</th>
				<th>Edit</th>
				<th>Delete</th>
			</tr>
			<?php while($r = $query->fetch_assoc()) {
				
				$id = $r['id']; 
				//$_SESSION['up_id'] = $id;
				$name = $r['name'];
				//$_SESSION['up_name'] = $name;
				$price = $r['price']; 
				//$_SESSION['up_price'] = $price;
				$stock = $r['stock']; 
				//$_SESSION['up_stock'] = $stock;
				$categories = $r['categories']; 
				//$_SESSION['up_categories'] = $categories;
				
				?>
			<tr>
				<td><?php echo $id; ?></td>
				<td><?php echo htmlspecialchars($name); ?></td>
				<td>₹<?php echo number_format((float)$price, 2); ?></td>
				<td><?php echo (int)$stock; ?></td>
				<td><?php echo htmlspecialchars($categories); ?></td>
				<td>
					<?php
					if($r['stock'] == 0){
						 echo '<span class="product-status out">Out Of Stock</span>';
					}elseif($r['stock'] < 10){
						 echo '<span class="product-status low">Low Stock</span>';
					}else{
						 echo '<span class="product-status ok">Available</span>';
					}
					?>
				</td>
				<td class="product-action">
					<button type="button" class="btn btn-warning update-product-btn" data-id="<?php echo htmlspecialchars($r['id']); ?>" data-name="<?php echo htmlspecialchars($r['name']); ?>" data-price="<?php echo htmlspecialchars($r['price']); ?>" data-stock="<?php echo htmlspecialchars($r['stock']); ?>" data-categories="<?php echo htmlspecialchars($r['categories']); ?>"><i class="fas fa-pen"></i> Edit</button>
				</td>
				<td class="product-action">
					<form method="post" onsubmit="return confirm('Delete this product?');">
						<input type="hidden" name="delete_product_id" value="<?php echo (int)$r['id']; ?>">
						<button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
					</form>
				</td>
			</tr>
		<?php } ?>
		</table>
		</div>
	</div>
</body>
</html>
