<?php

if(!isset($_SESSION['admin'])){
    die('Login required');
}

require 'db.php';

$message = '';
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$price = isset($_GET['price']) ? $_GET['price'] : 0;
$stock = isset($_GET['stock']) ? $_GET['stock'] : 0;
$categories = isset($_GET['categories']) ? trim($_GET['categories']) : '';

if ($id > 0 && $name === '' && !isset($_POST['name'])) {
    $stmt = $conn->prepare("SELECT name, price, stock, categories FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($db_name, $db_price, $db_stock, $db_categories);
    if ($stmt->fetch()) {
        $name = $db_name;
        $price = $db_price;
        $stock = $db_stock;
        $categories = $db_categories;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $categories = trim($_POST['categories']);

    if ($id <= 0) {
        $error = 'Invalid product ID.';
    } elseif ($name === '') {
        $error = 'Product name is required.';
    } elseif ($price <= 0) {
        $error = 'Invalid price.';
    } elseif ($stock < 0) {
        $error = 'Stock cannot be negative.';
    } elseif ($categories === '') {
        $error = 'Category is required.';
    } else {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, stock = ?, categories = ? WHERE id = ?");
        $stmt->bind_param("sdisi", $name, $price, $stock, $categories, $id);

        if ($stmt->execute()) {
            $message = 'Product updated successfully.';
        } else {
            $error = 'Error updating product.';
        }
    }
}
?>

<html>
<head>
<title>Update Product</title>
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
#update{
    padding:12px;
    width:100%;
    background:blue;
    color:white;
    border:none;
    border-radius:4px;
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
</style>
</head>
<body>

<div class="box" >
<h2>Update Product</h2>

<?php if($message){ ?>
<p class="msg"><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<?php if($error){ ?>
<div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php } ?>

<form method="post">

<label>ID</label>
<input type="number" name="id" value="<?php echo htmlspecialchars($id); ?>" required>

<label>Product Name</label>
<input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

<label>Price</label>
<input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($price); ?>" required>

<label>Stock</label>
<input type="number" name="stock" value="<?php echo htmlspecialchars($stock); ?>" required>

<label>Categories</label>
<input type="text" name="categories" value="<?php echo htmlspecialchars($categories); ?>" required>

<button type="submit" id="update" name="update_product">Update Product</button>
</form>

</div>

</body>
</html>
