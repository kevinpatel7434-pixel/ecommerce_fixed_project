<?php
session_start();
require 'db.php';

if(!isset($_SESSION['admin']) || $_SESSION['admin'] === true){
    header('Location: login.php');
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_name = $_SESSION['username'] ?? '';
$customer = [
    'first_name' => $user_name,
    'last_name' => '',
    'email' => '',
    'mobile' => '',
    'address_line1' => '',
    'address_line2' => '',
    'country' => '',
    'city' => '',
    'state' => '',
    'zip_code' => ''
];
$user_query = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_account = $user_query->get_result()->fetch_assoc();
$user_query->close();
if($user_account){
    $name_parts = explode(' ', trim($user_account['name']), 2);
    $customer['first_name'] = $name_parts[0];
    $customer['last_name'] = $name_parts[1] ?? '';
    $customer['email'] = $user_account['email'];
}

$checkout_query = $conn->prepare(
    "SELECT oi.product_id, oi.quantity, oi.price, p.name
     FROM order_items oi
     INNER JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = 0 AND oi.user_id = ?"
);
$checkout_query->bind_param("i", $user_id);
$checkout_query->execute();
$checkout_items = $checkout_query->get_result()->fetch_all(MYSQLI_ASSOC);
$checkout_query->close();

$subtotal = 0;
foreach($checkout_items as $item){
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$shipping = 0;
$total = $subtotal + $shipping;
$payment_method = '';

$order_message = '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])){
    $payment_method = trim($_POST['payment'] ?? '');
    $fields = ['first_name', 'last_name', 'email', 'mobile', 'address_line1', 'address_line2', 'country', 'city', 'state', 'zip_code'];
    foreach($fields as $field){
        $customer[$field] = trim($_POST[$field] ?? '');
    }

    if(empty($checkout_items)){
        $order_message = 'Your cart is empty.';
    } elseif($customer['first_name'] === '' || $customer['email'] === '' || $customer['mobile'] === '' || $customer['address_line1'] === '' || $customer['country'] === '' || $customer['city'] === '' || $customer['state'] === '' || $customer['zip_code'] === ''){
        $order_message = 'Please complete the required billing information.';
    } elseif($payment_method === ''){
        $order_message = 'Please choose a payment method.';
    } else {
        $conn->begin_transaction();
        try{
            $customer_query = $conn->prepare(
                "INSERT INTO customers (user_id, first_name, last_name, email, mobile, address_line1, address_line2, country, city, state, zip_code)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), mobile = VALUES(mobile), address_line1 = VALUES(address_line1), address_line2 = VALUES(address_line2), country = VALUES(country), city = VALUES(city), state = VALUES(state), zip_code = VALUES(zip_code)"
            );
            $customer_query->bind_param("issssssssss", $user_id, $customer['first_name'], $customer['last_name'], $customer['email'], $customer['mobile'], $customer['address_line1'], $customer['address_line2'], $customer['country'], $customer['city'], $customer['state'], $customer['zip_code']);
            $customer_query->execute();
            $customer_query->close();

            $customer_id_query = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
            $customer_id_query->bind_param("i", $user_id);
            $customer_id_query->execute();
            $customer_id_query->bind_result($customer_id);
            $customer_id_query->fetch();
            $customer_id_query->close();

            $order_query = $conn->prepare("INSERT INTO orders (user_id, customer_id, total_amount) VALUES (?, ?, ?)");
            $order_query->bind_param("iid", $user_id, $customer_id, $total);
            $order_query->execute();
            $order_id = $conn->insert_id;
            $order_query->close();

            $item_query = $conn->prepare("UPDATE order_items SET order_id = ? WHERE order_id = 0 AND user_id = ?");
            $item_query->bind_param("ii", $order_id, $user_id);
            $item_query->execute();
            $item_query->close();

            $conn->commit();
            header('Location: checkout.php?order_placed=' . $order_id);
            exit;
        }catch(Exception $exception){
            $conn->rollback();
            $order_message = 'Unable to place the order. Please try again.';
        }
    }
}
?>
<?php include 'user_header.php'; ?>

<body class="storefront-body">
    <?php if(isset($_GET['order_placed'])){ ?>
        <div class="alert alert-success mx-xl-5 mt-3">Order #<?php echo (int)$_GET['order_placed']; ?> placed successfully.</div>
    <?php } elseif($order_message){ ?>
        <div class="alert alert-danger mx-xl-5 mt-3"><?php echo htmlspecialchars($order_message); ?></div>
    <?php } ?>
    <!-- Topbar Start -->
    <!-- Topbar End -->


    <!-- Navbar Start -->
    
    <!-- Navbar End -->


    <main class="store-page-shell checkout-page">
        <div class="store-page-heading"><div><span class="eyebrow">Almost yours</span><h1>Checkout</h1><p>Confirm your details and place your order.</p></div><a class="text-link" href="cart.php"><i class="fa fa-arrow-left"></i> Back to cart</a></div>
        <form method="post">
        <div class="checkout-grid">
            <section class="billing-panel">
                <div class="checkout-section-heading"><span class="step-number">1</span><div><span class="eyebrow">Delivery details</span><h2>Billing address</h2></div></div>
                <div class="billing-fields">
                    <div class="checkout-form-grid">
                        <div class="checkout-field">
                            <label>First Name</label>
                            <input class="form-control" type="text" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                        </div>
                        <div class="checkout-field">
                            <label>Last Name</label>
                            <input class="form-control" type="text" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>">
                        </div>
                        <div class="checkout-field">
                            <label>E-mail</label>
                            <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                        </div>
                        <div class="checkout-field">
                            <label>Mobile No</label>
                            <input class="form-control" type="text" name="mobile" value="<?php echo htmlspecialchars($customer['mobile']); ?>" required>
                        </div>
                        <div class="checkout-field checkout-field-wide">
                            <label>Address Line 1</label>
                            <input class="form-control" type="text" name="address_line1" value="<?php echo htmlspecialchars($customer['address_line1']); ?>" required>
                        </div>
                        <div class="checkout-field checkout-field-wide">
                            <label>Address Line 2</label>
                            <input class="form-control" type="text" name="address_line2" value="<?php echo htmlspecialchars($customer['address_line2']); ?>">
                        </div>
                        <div class="checkout-field">
                            <label>Country</label>
                            <select class="custom-select" name="country" required>
                                <option value="">Select country</option>
                                <option <?php echo $customer['country'] === 'India' ? 'selected' : ''; ?>>India</option>
                                <option>Afghanistan</option>
                                <option>Albania</option>
                                <option>Algeria</option>
                            </select>
                        </div>
                        <div class="checkout-field">
                            <label>City</label>
                            <input class="form-control" type="text" name="city" value="<?php echo htmlspecialchars($customer['city']); ?>" required>
                        </div>
                        <div class="checkout-field">
                            <label>State</label>
                            <input class="form-control" type="text" name="state" value="<?php echo htmlspecialchars($customer['state']); ?>" required>
                        </div>
                        <div class="checkout-field">
                            <label>ZIP Code</label>
                            <input class="form-control" type="text" name="zip_code" value="<?php echo htmlspecialchars($customer['zip_code']); ?>" required>
                        </div>
                        <div class="checkout-field checkout-field-wide">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="newaccount">
                                <label class="custom-control-label" for="newaccount">Create an account</label>
                            </div>
                        </div>
                        <div class="checkout-field checkout-field-wide">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="shipto">
                                <label class="custom-control-label" for="shipto"  data-toggle="collapse" data-target="#shipping-address">Ship to different address</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="collapse mb-5" id="shipping-address">
                    <h5 class="section-title position-relative text-uppercase mb-3"><span class="bg-secondary pr-3">Shipping Address</span></h5>
                    <div class="bg-light p-30">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>First Name</label>
                                <input class="form-control" type="text" placeholder="John">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Last Name</label>
                                <input class="form-control" type="text" placeholder="Doe">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>E-mail</label>
                                <input class="form-control" type="text" placeholder="example@email.com">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Mobile No</label>
                                <input class="form-control" type="text" placeholder="+123 456 789">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 1</label>
                                <input class="form-control" type="text" placeholder="123 Street">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Address Line 2</label>
                                <input class="form-control" type="text" placeholder="123 Street">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Country</label>
                                <select class="custom-select">
                                    <option selected>United States</option>
                                    <option>Afghanistan</option>
                                    <option>Albania</option>
                                    <option>Algeria</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>City</label>
                                <input class="form-control" type="text" placeholder="New York">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>State</label>
                                <input class="form-control" type="text" placeholder="New York">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>ZIP Code</label>
                                <input class="form-control" type="text" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="checkout-sidebar">
                <section class="order-summary-panel">
                    <div class="checkout-section-heading"><span class="step-number">2</span><div><span class="eyebrow">Your selection</span><h2>Order total</h2></div></div>
                    <div class="border-bottom">
                        <h6 class="summary-label">Products</h6>
                        <?php if(empty($checkout_items)){ ?>
                            <p>Your cart is empty. <a href="user_page.php">Continue shopping</a>.</p>
                        <?php } else { ?>
                            <?php foreach($checkout_items as $item){ ?>
                            <div class="checkout-order-line">
                                <p><?php echo htmlspecialchars($item['name']); ?> x <?php echo (int)$item['quantity']; ?></p>
                                <p>₹<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></p>
                            </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <div class="checkout-price-lines">
                        <div class="checkout-order-line">
                            <h6>Subtotal</h6>
                            <h6>₹<?php echo number_format($subtotal, 2); ?></h6>
                        </div>
                        <div class="checkout-order-line">
                            <h6 class="font-weight-medium">Shipping</h6>
                            <h6 class="font-weight-medium">FREE</h6>
                        </div>
                    </div>
                    <div class="checkout-total-line">
                        <div class="checkout-order-line">
                            <h5>Total</h5>
                            <h5>₹<?php echo number_format($total, 2); ?></h5>
                        </div>
                    </div>
                </section>
                <section class="payment-panel">
                    <div class="checkout-section-heading"><span class="step-number">3</span><div><span class="eyebrow">Secure payment</span><h2>Payment method</h2></div></div>
                    <div class="payment-options">
                        <div class="payment-option">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" name="payment" value="paypal" id="paypal" <?php echo $payment_method === 'paypal' ? 'checked' : ''; ?> required>
                                <label class="custom-control-label" for="paypal"><strong>PayPal</strong><small>Pay securely online</small></label>
                            </div>
                        </div>
                        <div class="payment-option">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" name="payment" value="card" id="directcheck" <?php echo $payment_method === 'card' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="directcheck"><strong>Card payment</strong><small>Visa, Mastercard and more</small></label>
                            </div>
                        </div>
                        <div class="payment-option">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" name="payment" value="bank" id="banktransfer" <?php echo $payment_method === 'bank' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="banktransfer"><strong>Bank transfer</strong><small>Pay from your bank account</small></label>
                            </div>
                        </div>
                        <button type="submit" name="place_order" class="primary-store-button" <?php echo empty($checkout_items) ? 'disabled' : ''; ?>>Place order <i class="fa fa-arrow-right"></i></button>
                    </div>
                </section>
            </aside>
        </div>
                </form>
    </main>


    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>
    <!-- Footer Start -->
  
<?php include 'footer.php'; ?>

    <!-- Footer End -->


    <!-- Back to Top -->


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>


    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>