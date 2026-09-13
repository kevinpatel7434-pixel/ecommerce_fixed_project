<?php
session_start();
require 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    if ($name === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare('INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)');
            $insert->bind_param('ssss', $name, $username, $email, $hashedPassword);

            if ($insert->execute()) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['admin'] = false;
                header('Location: user_page.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }

        $stmt->close();
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Nivo Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <main class="auth-layout auth-register-layout">
        <section class="auth-visual">
            <a class="auth-brand" href="login.php"><span class="logo-mark">N</span><span>nivo Store.</span></a>
            <div class="auth-visual-copy"><span class="pill">Make it yours</span><h1>A better way<br><em>to shop.</em></h1><p>Create an account to save your details, track every order, and make checkout feel effortless.</p></div>
            <img src="img/product-2.jpg" alt="Featured store product" class="auth-product-image">
            <div class="auth-visual-footer"><span>Personalized shopping</span><span><i class="fa fa-arrow-right"></i> Ready when you are</span></div>
        </section>
        <section class="auth-panel">
            <div class="auth-panel-inner">
                <span class="admin-eyebrow">Join the store</span><h2>Create your account</h2><p class="auth-subtitle">It only takes a minute to start shopping.</p>
                <?php if ($error): ?><div class="auth-error"><i class="fa fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form method="post" class="auth-form auth-register-form">
                    <label>Full name<input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" autocomplete="name" required></label>
                    <label>Username<input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username" required></label>
                    <label>Email address<input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="email" required></label>
                    <div class="auth-form-columns"><label>Password<input type="password" name="password" minlength="6" autocomplete="new-password" required><small>At least 6 characters</small></label><label>Confirm password<input type="password" name="confirm_password" minlength="6" autocomplete="new-password" required></label></div>
                    <button type="submit" class="auth-submit">Create account <i class="fa fa-arrow-right"></i></button>
                </form>
                <div class="auth-divider"><span>Already have an account?</span></div>
                <a href="login.php" class="auth-secondary-button">Sign in instead</a>
            </div>
        </section>
    </main>
</body>
</html>
