<?php



if(!isset($_SESSION['admin'])){
    die('Login required');
}

require 'db.php';

$message = '';

$error = '';


if($_SERVER['REQUEST_METHOD'] == 'POST'){
	?>
<script>
		$(document).ready(function(){
				//$("#product").hide();
			$("#delete").click(function(){
				//$("#product").show("slow");			

<?php


    $name = trim($_POST['name']);
    $id = $_REQUEST['id'];

	if($id <= 0){
        $error = 'Invalid ID';
    }
    else if(isset($_REQUEST["id_d"])){
		
		$id = $_REQUEST['id'];
		$stmt3 = $conn->prepare("DELETE FROM products WHERE  name = ? OR id = ?");

		//$stmt = $conn->prepare(
        //"INSERT INTO products(name, price, stock, categories) VALUES (?, ?, ?, ?)"
    //);

    $stmt3->bind_param("si", $name, $id);
   // $stmt->bind_param("sdis", $name, $price, $stock, $categories);

    if($stmt3->execute()){
        $message = "Product Delete successfully";
		
    }else{
        $message = "Error Delete product";
    }
		
	}
	
    ?>
	
			});
		});

</script>
	
    <?php
}
?>


<html>
<head>
<title>Delete Product</title>
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
#deleted{
    padding:12px;
    width:100%;
    background:;
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
<h2>Delete Product</h2>

<?php if($message){ ?>
<p class="msg"><?php echo $message; ?></p>
<?php } ?>


<?php if($error){ ?>
<div class="error"><?php echo $error; ?></div>
<?php } ?>

<form method="post">
<?php echo $id;?>
<label>Product ID</label>
<input type="number" pattern = "[0-9]*" value="" name="id" required>

<label>Product Name</label>
<input type="text" pattern = "[A-Za-z\s]+" name="name">


<button type="submit" class="btn btn-danger" id="deleted"> Delete Product</button>

</form>

</div>

</body>
</html>
