<?php
session_start();

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
    header('Location: login.php');
    exit;
}

require 'db.php';

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = '';
$error = '';
$editing_id = (int)($_GET['edit'] ?? 0);

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])){
    $user_id = (int)($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $editing_id = $user_id;

    if($user_id <= 0 || $name === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'Name, username, and a valid email are required.';
    }else{
        $duplicate = $conn->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
        $duplicate->bind_param('ssi', $username, $email, $user_id);
        $duplicate->execute();
        $duplicate->store_result();

        if($duplicate->num_rows > 0){
            $error = 'That username or email is already in use.';
        }else{
            $conn->begin_transaction();
            try{
                if($password !== ''){
                    if(strlen($password) < 6){
                        throw new Exception('New password must contain at least 6 characters.');
                    }
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $user_query = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ?, password = ? WHERE id = ?');
                    $user_query->bind_param('ssssi', $name, $username, $email, $hashed_password, $user_id);
                }else{
                    $user_query = $conn->prepare('UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?');
                    $user_query->bind_param('sssi', $name, $username, $email, $user_id);
                }
                $user_query->execute();
                $user_query->close();

                $customer_query = $conn->prepare(
                    "INSERT INTO customers (user_id, first_name, last_name, email, mobile, address_line1, address_line2, country, city, state, zip_code)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), email = VALUES(email), mobile = VALUES(mobile), address_line1 = VALUES(address_line1), address_line2 = VALUES(address_line2), country = VALUES(country), city = VALUES(city), state = VALUES(state), zip_code = VALUES(zip_code)"
                );
                $customer_query->bind_param('issssssssss', $user_id, $first_name, $last_name, $email, $mobile, $address_line1, $address_line2, $country, $city, $state, $zip_code);
                $customer_query->execute();
                $customer_query->close();
                $conn->commit();
                $message = 'Customer information updated.';
            }catch(Exception $exception){
                $conn->rollback();
                $error = $exception->getMessage();
            }
        }
        $duplicate->close();
    }
}

$edit_customer = null;
if($editing_id > 0){
    $edit_query = $conn->prepare(
        "SELECT u.id, u.name, u.username, u.email, c.first_name, c.last_name, c.mobile,
                c.address_line1, c.address_line2, c.country, c.city, c.state, c.zip_code
         FROM users u LEFT JOIN customers c ON c.user_id = u.id WHERE u.id = ?"
    );
    $edit_query->bind_param('i', $editing_id);
    $edit_query->execute();
    $edit_customer = $edit_query->get_result()->fetch_assoc();
    $edit_query->close();
}

$customers_query = $conn->query(
    "SELECT u.id, u.name, u.username, u.email, u.created_at,
            c.first_name, c.last_name, c.mobile, c.city, c.state
     FROM users u LEFT JOIN customers c ON c.user_id = u.id ORDER BY u.id DESC"
);
$customers = $customers_query ? $customers_query->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customers | Ecommerce Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
<?php include 'header.php'; ?>
<main class="admin-main customers-page">
    <div class="admin-page-heading">
        <div><span class="admin-eyebrow">Admin workspace</span><h1>Customers</h1><p>Manage customer profiles, account names, usernames, and passwords.</p></div>
        <a class="admin-outline-button" href="admin_page.php"><i class="fa fa-arrow-left"></i> Overview</a>
    </div>
    <?php if($message !== ''){ ?><div class="admin-notice success"><i class="fa fa-check-circle"></i><?php echo htmlspecialchars($message); ?></div><?php } ?>
    <?php if($error !== ''){ ?><div class="admin-notice error"><i class="fa fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div><?php } ?>

    <?php if($edit_customer){ ?>
    <section class="admin-panel customer-editor">
        <div class="admin-panel-heading"><div><span class="admin-eyebrow">Account settings</span><h2>Edit customer</h2></div><a href="customers.php" class="admin-text-link">Close <i class="fa fa-times"></i></a></div>
        <form method="post" class="customer-form">
            <input type="hidden" name="user_id" value="<?php echo (int)$edit_customer['id']; ?>">
            <div class="customer-form-grid">
                <label>Full name<input type="text" name="name" value="<?php echo htmlspecialchars($edit_customer['name'] ?? ''); ?>" required></label>
                <label>Username<input type="text" name="username" value="<?php echo htmlspecialchars($edit_customer['username'] ?? ''); ?>" required></label>
                <label>Email<input type="email" name="email" value="<?php echo htmlspecialchars($edit_customer['email'] ?? ''); ?>" required></label>
                <label>New password <span class="field-note">Leave blank to keep current</span><input type="password" name="password" minlength="6" autocomplete="new-password"></label>
                <label>First name<input type="text" name="first_name" value="<?php echo htmlspecialchars($edit_customer['first_name'] ?? ''); ?>"></label>
                <label>Last name<input type="text" name="last_name" value="<?php echo htmlspecialchars($edit_customer['last_name'] ?? ''); ?>"></label>
                <label>Mobile<input type="text" name="mobile" value="<?php echo htmlspecialchars($edit_customer['mobile'] ?? ''); ?>"></label>
                <label>Country<input type="text" name="country" value="<?php echo htmlspecialchars($edit_customer['country'] ?? ''); ?>"></label>
                <label class="wide-field">Address line 1<input type="text" name="address_line1" value="<?php echo htmlspecialchars($edit_customer['address_line1'] ?? ''); ?>"></label>
                <label class="wide-field">Address line 2<input type="text" name="address_line2" value="<?php echo htmlspecialchars($edit_customer['address_line2'] ?? ''); ?>"></label>
                <label>City<input type="text" name="city" value="<?php echo htmlspecialchars($edit_customer['city'] ?? ''); ?>"></label>
                <label>State<input type="text" name="state" value="<?php echo htmlspecialchars($edit_customer['state'] ?? ''); ?>"></label>
                <label>ZIP code<input type="text" name="zip_code" value="<?php echo htmlspecialchars($edit_customer['zip_code'] ?? ''); ?>"></label>
            </div>
            <button type="submit" name="save_customer" class="admin-save-button"><i class="fa fa-check"></i><span>Save customer</span></button>
        </form>
    </section>
    <?php } ?>

    <section class="admin-panel"><div class="admin-panel-heading"><div><span class="admin-eyebrow">Customer directory</span><h2>Customer information</h2></div><span class="admin-record-count"><?php echo count($customers); ?> accounts</span></div>
        <div class="admin-table-wrap"><table class="admin-table customer-table"><thead><tr><th>Customer</th><th>Username</th><th>Email</th><th>Location</th><th>Joined</th><th>Action</th></tr></thead><tbody>
        <?php if(empty($customers)){ ?><tr><td colspan="6" class="admin-empty-cell">No registered customers found.</td></tr><?php } else { foreach($customers as $customer){ ?>
            <tr><td><div class="customer-name"><span class="customer-avatar"><?php echo strtoupper(substr($customer['name'], 0, 1)); ?></span><div><strong><?php echo htmlspecialchars($customer['name']); ?></strong><small><?php echo htmlspecialchars(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))); ?></small></div></div></td><td><?php echo htmlspecialchars($customer['username']); ?></td><td><?php echo htmlspecialchars($customer['email']); ?></td><td><?php echo htmlspecialchars(trim(($customer['city'] ?? '') . (($customer['state'] ?? '') ? ', ' . $customer['state'] : '')) ?: 'Not provided'); ?></td><td><?php echo htmlspecialchars(date('d M Y', strtotime($customer['created_at']))); ?></td><td><a href="customers.php?edit=<?php echo (int)$customer['id']; ?>" class="admin-edit-button"><i class="fa fa-pen"></i> Manage</a></td></tr>
        <?php } } ?>
        </tbody></table></div>
    </section>
</main>
</body>
</html>
