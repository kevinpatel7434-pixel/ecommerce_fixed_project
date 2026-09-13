<?php
session_start();

if(!isset($_SESSION['admin']) || $_SESSION['admin'] === true){
    header('Location: login.php');
    exit;
}
require 'db.php';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$current_page = basename($_SERVER['PHP_SELF']);
$message = '';
$selected_category = trim($_GET['category'] ?? '');
$selected_product_id = (int)($_GET['product_id'] ?? 0);
$show_all_products = $selected_category === '' && $selected_product_id === 0;

// order_id = 0 marks an order_items row as "still in the cart" (not yet placed as a real order)
if(isset($_REQUEST['add'])){
    $product_id = (int)$_POST['product_id'];
    $price      = (float)$_POST['price'];
    $quantity   = (int)$_POST['quantity'];
    if($quantity < 1){
        $quantity = 1;
    }

    $conn->begin_transaction();
    try{
        // Lock the product row so concurrent purchases can't oversell the stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->bind_result($stock);
        $found = $stmt->fetch();
        $stmt->close();

        if(!$found){
            throw new Exception("Product not found");
        }
        if($stock < $quantity){
            throw new Exception("Only $stock item(s) left in stock");
        }

        // If this product is already in the cart, increase its quantity instead of adding a duplicate row
        $stmt = $conn->prepare("SELECT id, quantity FROM order_items WHERE order_id = 0 AND user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->bind_result($cart_item_id, $existing_qty);
        $already_in_cart = $stmt->fetch();
        $stmt->close();

        if($already_in_cart){
            $stmt = $conn->prepare("UPDATE order_items SET quantity = quantity + ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity, $cart_item_id);
            $stmt->execute();
            $stmt->close();
        }else{
            $stmt = $conn->prepare("INSERT INTO order_items(order_id, user_id, product_id, quantity, price) VALUES (0, ?, ?, ?, ?)");
            $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $price);
            $stmt->execute();
            $stmt->close();
        }

        // Decrease stock by the quantity just purchased/added
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt->bind_param("iii", $quantity, $product_id, $quantity);
        $stmt->execute();
        $stock_updated = $stmt->affected_rows > 0;
        $stmt->close();

        if(!$stock_updated){
            throw new Exception("Not enough stock available");
        }

        $conn->commit();
        $message = "Product added to cart successfully";
        echo "<script>alert('Product added to cart!'); window.location = 'user_page.php';</script>";
    }catch(Exception $e){
        $conn->rollback();
        $message = $e->getMessage();
        echo "<script>alert('" . addslashes($message) . "');</script>";
    }
}

if($selected_product_id > 0){
    $query = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $query->bind_param("i", $selected_product_id);
    $query->execute();
    $products_result = $query->get_result();
}elseif($selected_category !== ''){
    $query = $conn->prepare("SELECT * FROM products WHERE categories = ? ORDER BY id DESC");
    $query->bind_param("s", $selected_category);
    $query->execute();
    $products_result = $query->get_result();
}else{
    $products_result = $conn->query("SELECT * FROM products ORDER BY id DESC");
}
$sql = 'SELECT * FROM order_items WHERE order_id != 0';
$result = mysqli_query($conn, $sql);
$category_result = $conn->query("SELECT DISTINCT categories FROM products ORDER BY categories");
?>

<?php include 'user_header.php'; ?>
<main class="store-main">
    <div class="container-fluid store-layout">
        <aside class="category-panel">
            <div class="eyebrow">Browse collection</div>
            <h1>Find your<br><em>next favorite.</em></h1>
            <p>Curated essentials and clever tech for a better everyday.</p>
            <a class="category-link category-all-link <?php echo $show_all_products ? 'active' : ''; ?>" href="user_page.php#products" <?php echo $show_all_products ? 'aria-current="page"' : ''; ?>>All products <i class="fa fa-arrow-right"></i></a>
            <?php
            if ($category_result) {
                while ($category_row = $category_result->fetch_assoc()) {
                    $category = trim($category_row['categories']);
                    if ($category !== '') {
                        $active = $selected_category === $category && $selected_product_id === 0 ? 'active' : '';
                        $current = $active !== '' ? ' aria-current="page"' : '';
                        echo '<a class="category-link ' . $active . '" href="user_page.php?category=' . urlencode($category) . '#products"' . $current . '>' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '<i class="fa fa-arrow-right"></i></a>';
                    }
                }
            }
            ?>
            <div class="category-note"><i class="fa fa-sparkles"></i><span>New drops<br><strong>every Friday</strong></span></div>
        </aside>
       
        <section class="store-content">
            <div class="feature-card" data-feature-carousel>
                <div class="feature-copy">
                    <span class="pill">Featured collection</span>
                    <h2>Sequoia<br>inspiring music.</h2>
                    <p>Clear sounds, bold design.<br>Meet your new daily essential.</p>
                    <a class="store-nav-link" href="user_page.php#products">View all products <i class="fa fa-arrow-right"></i></a>
                </div>
                <div class="feature-media">
                    <button type="button" class="feature-arrow feature-prev" aria-label="Previous featured product"><i class="fa fa-chevron-left"></i></button>
                    <img class="feature-image" src="img/product-1.jpg" alt="Featured headphones">
                    <button type="button" class="feature-arrow feature-next" aria-label="Next featured product"><i class="fa fa-chevron-right"></i></button>
                </div>
                <div class="feature-dots" aria-label="Featured products">
                    <button type="button" class="selected" aria-label="Show featured product 1" aria-current="true"></button>
                    <button type="button" aria-label="Show featured product 2"></button>
                    <button type="button" aria-label="Show featured product 3"></button>
                </div>
            </div>
            <div class="content-heading">
                <div><span class="eyebrow">Handpicked for you</span><h2 id="products"><?php echo $selected_category !== '' ? htmlspecialchars($selected_category) : 'More products'; ?></h2></div>
                <a class="view-all-link" href="user_page.php#products">View all products <i class="fa fa-arrow-right"></i></a>
            </div>
            <div class="product-grid">
                <?php while($r = $products_result->fetch_assoc()) { ?>
                    <article class="product-card" id="product-<?php echo (int)$r['id']; ?>">
                        <form method="post">
                            <div class="product-image"><a href="user_page.php?product_id=<?php echo (int)$r['id']; ?>" aria-label="View <?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?>"><img src="IMAGE/<?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?>.jpg" alt="<?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?>"></a><button type="button" class="wishlist" data-product-id="<?php echo (int)$r['id']; ?>" aria-label="Add <?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?> to wishlist" aria-pressed="false"><i class="far fa-heart" aria-hidden="true"></i></button></div>
                            <div class="product-meta"><span><?php echo htmlspecialchars($r['categories'] ?? 'Essentials'); ?></span><h3><?php echo htmlspecialchars($r['name']); ?></h3><p>Designed for your everyday.</p><strong>₹<?php echo htmlspecialchars($r['price']); ?></strong><input type="hidden" name="quantity" value="1"><input type="hidden" name="price" value="<?php echo htmlspecialchars($r['price']); ?>"><input type="hidden" name="product_id" value="<?php echo (int)$r['id']; ?>"><button type="submit" name="add" class="add-product">Add to cart <i class="fa fa-plus"></i></button></div>
                        </form>
                    </article>
                <?php } ?>
            </div>
        </section>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wishlistKey = 'nivoWishlist';
    let savedWishlist = [];
    try {
        savedWishlist = JSON.parse(localStorage.getItem(wishlistKey) || '[]').map(String);
    } catch (error) {
        savedWishlist = [];
    }

    document.querySelectorAll('.wishlist').forEach(function (button) {
        const productId = String(button.dataset.productId);
        const icon = button.querySelector('i');
        const productName = button.getAttribute('aria-label').replace(/^Add |Remove /, '').replace(/ to wishlist$/, '');

        function updateWishlistState(isSaved) {
            button.classList.toggle('active', isSaved);
            button.setAttribute('aria-pressed', isSaved ? 'true' : 'false');
            button.setAttribute('aria-label', (isSaved ? 'Remove ' : 'Add ') + productName + (isSaved ? ' from wishlist' : ' to wishlist'));
            icon.classList.toggle('far', !isSaved);
            icon.classList.toggle('fas', isSaved);
        }

        updateWishlistState(savedWishlist.includes(productId));
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const saved = savedWishlist.includes(productId);
            savedWishlist = saved ? savedWishlist.filter(function (id) { return id !== productId; }) : savedWishlist.concat(productId);
            localStorage.setItem(wishlistKey, JSON.stringify(savedWishlist));
            updateWishlistState(!saved);
        });
    });

    const carousel = document.querySelector('[data-feature-carousel]');
    if (!carousel) return;

    const image = carousel.querySelector('.feature-image');
    const dots = Array.from(carousel.querySelectorAll('.feature-dots button'));
    const slides = [
        { src: 'img/product-1.jpg', alt: 'Featured headphones' },
        { src: 'img/product-2.jpg', alt: 'Featured product' },
        { src: 'img/product-3.jpg', alt: 'Featured product' }
    ];
    let currentSlide = 0;
    let timer;

    function showSlide(index) {
        currentSlide = (index + slides.length) % slides.length;
        image.src = slides[currentSlide].src;
        image.alt = slides[currentSlide].alt;
        dots.forEach(function (dot, dotIndex) {
            const selected = dotIndex === currentSlide;
            dot.classList.toggle('selected', selected);
            dot.setAttribute('aria-current', selected ? 'true' : 'false');
        });
    }

    function restartTimer() {
        window.clearInterval(timer);
        timer = window.setInterval(function () { showSlide(currentSlide + 1); }, 4000);
    }

    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
            showSlide(index);
            restartTimer();
        });
    });
    carousel.querySelector('.feature-prev').addEventListener('click', function () {
        showSlide(currentSlide - 1);
        restartTimer();
    });
    carousel.querySelector('.feature-next').addEventListener('click', function () {
        showSlide(currentSlide + 1);
        restartTimer();
    });
    carousel.addEventListener('mouseenter', function () { window.clearInterval(timer); });
    carousel.addEventListener('mouseleave', restartTimer);
    restartTimer();
});
</script>
<?php include 'footer.php'; ?>
