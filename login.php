<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin'] = true;
        $_SESSION['username'] = 'Administrator';
        header('Location: admin_page.php');
        exit;
    }

    if ($username === 'user' && $password === 'user123') {
        $_SESSION['admin'] = false;
        $_SESSION['username'] = 'User';
        header('Location: user_page.php');
        exit;
    }

    $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $db_username, $hashed_password);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $db_username;
                $_SESSION['admin'] = false;
                header('Location: user_page.php');
                exit;
            }
        }

        $stmt->close();
    }

    $error = 'Invalid username or password';
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | Nivo Store</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<main class="auth-layout">
    <section class="auth-visual">
        <a class="auth-brand" href="login.php"><span class="logo-mark">N</span><span>nivo Store.</span></a>
        <div class="auth-visual-copy"><span class="pill">Your everyday edit</span><h1>Good things<br><em>start here.</em></h1><p>Sign in to pick up where you left off and discover products made for your everyday.</p></div>
        <img src="img/product-1.jpg" alt="Featured store product" class="auth-product-image">
        <div class="auth-visual-footer"><span>Secure account access</span><span><i class="fa fa-arrow-right"></i> Shop with ease</span></div>
    </section>
    <section class="auth-panel">
        <div class="auth-panel-inner">
            <span class="admin-eyebrow">Welcome back</span><h2>Sign in to Nivo</h2><p class="auth-subtitle">Access your cart, orders, and favorite finds.</p>
            <?php if($error){ ?><div class="auth-error"><i class="fa fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div><?php } ?>
            <form method="post" class="auth-form">
                <label>Username<input type="text" name="username" autocomplete="username" required></label>
                <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
                <button type="submit" class="auth-submit">Sign in <i class="fa fa-arrow-right"></i></button>
            </form>
            <div class="auth-divider"><span>New to Nivo?</span></div>
            <a href="register.php" class="auth-secondary-button">Create a new account</a>
        </div>
    </section>
</main>
</body>
</html>
