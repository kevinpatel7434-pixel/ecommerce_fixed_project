<?php
if(!isset($_SESSION['admin'])){
    die('Login required');
}

require 'db.php';

$message = '';

$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $categories = $_POST['categories'];

    if ($price <= 0) {
        $error = 'Invalid PRICE';
    } elseif ($stock <= 0) {
        $error = 'Invalid STOCK';
    } else {
        $stmt = $conn->prepare("INSERT INTO products(name, price, stock, categories) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdis", $name, $price, $stock, $categories);

        if ($stmt->execute()) {
            $message = "Product added successfully";
        } else {
            $message = "Error adding product";
        }
    }
}
?>

<html>
<head>
<title>Add Product</title>
<style>

.box{
    width:400px;
    margin:20px auto;
    background:#fff;
    padding:60px;
    border-radius:10px;
}
input{
    width:100%;
    padding:10px;
    margin-top:10px;
    margin-bottom:15px;
}
#add{
    padding:12px;
    width:100%;
    background:blue;
    color:white;
    border:none;
}
.msg{
    color:green;
}

.error{
    color:red;
}

body{
	background:WhiteSmoke;
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

</style>
</head>
<body>

<div class="box" >
<h2>Add Product</h2>

<?php if($message){ ?>
<p class="msg"><?php echo $message; ?></p>
<?php } ?>


<?php if($error){ ?>
<div class="error"><?php echo $error; ?></div>
<?php } ?>

<form method="post">

<label>Product Name</label>
<input type="text" pattern = "[A-Za-z\s]+" name="name" required>

<label>Price</label>
<input type="number" pattern = "[0-9]*" step="0.01" name="price" required>

<label>Stock</label>
<input type="number" pattern = "[0-9]*" name="stock" required>

<label>Categories</label>
<input type="text" pattern = "[A-Za-z\s]+" name="categories" required>

<button type="submit" id="add" name="add_product">Add Product</button>

</form>

</div>

</body>
</html>
