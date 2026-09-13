<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$cart_badge_count = 0;
if (!isset($conn)) {
    require_once 'db.php';
}
$cart_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$cart_count_query = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = 0 AND user_id = ?');
if ($cart_count_query) {
    $cart_count_query->bind_param('i', $cart_user_id);
    $cart_count_query->execute();
    $cart_count_query->bind_result($cart_badge_count);
    $cart_count_query->fetch();
    $cart_count_query->close();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ecommerce Store</title>
    <link rel="icon" href="img/favicon.ico">
    <link rel="stylesheet" href="js/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
</head>
<body class="storefront-body">
<header class="store-header">
    <div class="container-fluid store-header-inner">
        <a class="store-logo" href="user_page.php"><span class="logo-mark">N</span><span>nivo Store.</span></a>
        <form class="store-search" action="livesearch.php" method="get">
            
            <input id="live_search" type="search" name="q" placeholder="Search products..." autocomplete="off">
            <span class="search-shortcut">⌘ K</span>
            <div id="searchresult"></div>
        </form>
        <nav class="store-actions" aria-label="Store navigation">
            <div class="store-nav-links">
                <a href="user_page.php" class="store-nav-link"><i class="fa fa-home" aria-hidden="true"></i><span>Home</span></a>
                <a href="cart.php" class="store-nav-link"><i class="fa fa-shopping-bag" aria-hidden="true"></i><span>Cart</span><b aria-label="<?php echo (int)$cart_badge_count; ?> items in cart"><?php echo (int)$cart_badge_count; ?></b></a>
                <a href="checkout.php" class="store-nav-link"><i class="fa fa-credit-card" aria-hidden="true"></i><span>Checkout</span></a>
                <a href="my_orders.php" class="store-nav-link"><i class="fa fa-receipt" aria-hidden="true"></i><span>My Orders</span></a>
            </div>
            <div class="account-menu">
                <button type="button" class="user-chip account-toggle" aria-expanded="false" aria-controls="account-dropdown">
                    <span class="avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></span>
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Account'); ?></span>
                    <i class="fa fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div id="account-dropdown" class="account-dropdown" hidden>
                    <a href="register.php"><i class="fa fa-user-plus" aria-hidden="true"></i> New user registration</a>
                    <a href="logout.php"><i class="fa fa-sign-out-alt" aria-hidden="true"></i> Logout</a>
                </div>
            </div>
        </nav>
    </div>
    
</header>
<script>
$(function () {
    $('#live_search').on('input', function () {
        var value = $(this).val().trim();
        if (!value) { $('#searchresult').empty().hide(); return; }
        $.post('livesearch.php', { input: value }, function (data) {
            $('#searchresult').html(data).show();
        });
    });

    $('.account-toggle').on('click', function (event) {
        event.stopPropagation();
        var menu = $('#account-dropdown');
        var isOpen = !menu.prop('hidden');
        menu.prop('hidden', isOpen);
        $(this).attr('aria-expanded', String(!isOpen));
    });

    $(document).on('click', function () {
        $('#account-dropdown').prop('hidden', true);
        $('.account-toggle').attr('aria-expanded', 'false');
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            $('#account-dropdown').prop('hidden', true);
            $('.account-toggle').attr('aria-expanded', 'false').trigger('focus');
        }
    });
});
</script>
