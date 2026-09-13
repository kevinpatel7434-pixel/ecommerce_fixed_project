<?php
//echo "hello";
require 'db.php';?>



<?php
if(isset($_POST['input'])){
		$input = $_POST['input'];
		$query = "SELECT * FROM products WHERE name LIKE '{$input}%' OR price LIKE '{$input}%' ";
		$result = mysqli_query($conn, $query);
	if(mysqli_num_rows($result) > 0){?>
		<div class="search-results-grid">
			<?php
				while($row = mysqli_fetch_assoc($result)){
				$id = $row['id'];
				$name = $row['name'];
				$price = $row['price'];
				$stock = $row['stock'];
				$categories = $row['categories'];
				?>
				<article class="search-result-card">
			<form method="post" action="user_page.php">
				<a href="user_page.php?product_id=<?php echo (int)$row['id']; ?>" aria-label="View <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
					<img src="IMAGE/<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>.jpg" alt="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
				</a>
				<h4><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h4>
				<input type='hidden' name='name' value='<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>'>
				<input type='hidden' name='quantity' value='1'>
				
				<p>Premium laptop with Gaming Softwer.</p>
				
				<h5>₹<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></h5>
				<input type='hidden' name='price' value='<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>'>
				
				<input type='hidden' name='product_id' value='<?php echo $row['id']; ?>'>
				<button type="submit" name="add" class="btn btn-warning ">Add To Cart </button>
	</form>
				</article>
				<?php
					
				}
			?>
		</div>

<?php
}else{
echo "<h6 class='text-danger text-center mt-3'>No data Found</h6>";
}
} 
?>