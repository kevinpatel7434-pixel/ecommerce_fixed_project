<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ecomdb';

$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    die('Database Connection Failed: ' . $conn->connect_error);
}

if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$database}`")) {
    die('Database Setup Failed: ' . $conn->error);
}

$conn->select_db($database);


// Keep the core tables available on fresh installations.
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categories VARCHAR(100) NOT NULL
)");

// Keep existing installations compatible with per-user cart rows.
$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL DEFAULT 0,
    user_id INT NOT NULL DEFAULT 0,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL
)");
$conn->query("CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mobile VARCHAR(30) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Placed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Migrate the original customers table to the checkout billing structure.
$old_customer_name = $conn->query("SHOW COLUMNS FROM customers LIKE 'name'");
if ($old_customer_name && $old_customer_name->num_rows > 0) {
    $conn->query("ALTER TABLE customers
        ADD COLUMN user_id INT NULL AFTER id,
        ADD COLUMN first_name VARCHAR(100) NULL AFTER user_id,
        ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name,
        ADD COLUMN mobile VARCHAR(30) NULL AFTER email,
        ADD COLUMN address_line1 VARCHAR(255) NULL,
        ADD COLUMN address_line2 VARCHAR(255) NULL,
        ADD COLUMN country VARCHAR(100) NULL,
        ADD COLUMN city VARCHAR(100) NULL,
        ADD COLUMN state VARCHAR(100) NULL,
        ADD COLUMN zip_code VARCHAR(20) NULL,
        ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

    $users_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($users_table && $users_table->num_rows > 0) {
        $conn->query("UPDATE customers c
            LEFT JOIN users u ON u.email = c.email
            SET c.user_id = u.id,
                c.first_name = SUBSTRING_INDEX(TRIM(c.name), ' ', 1),
                c.last_name = TRIM(SUBSTRING(TRIM(c.name), LENGTH(SUBSTRING_INDEX(TRIM(c.name), ' ', 1)) + 1))");
    } else {
        $conn->query("UPDATE customers SET first_name = SUBSTRING_INDEX(TRIM(name), ' ', 1), last_name = ''");
    }

    $conn->query("UPDATE customers SET
        first_name = COALESCE(first_name, ''), last_name = COALESCE(last_name, ''),
        email = COALESCE(email, ''), mobile = COALESCE(mobile, ''),
        address_line1 = COALESCE(address_line1, ''), address_line2 = COALESCE(address_line2, ''),
        country = COALESCE(country, ''), city = COALESCE(city, ''),
        state = COALESCE(state, ''), zip_code = COALESCE(zip_code, '')");
    $conn->query("ALTER TABLE customers
        DROP COLUMN name,
        DROP COLUMN join_date,
        MODIFY user_id INT NULL,
        MODIFY first_name VARCHAR(100) NOT NULL,
        MODIFY last_name VARCHAR(100) NOT NULL,
        MODIFY email VARCHAR(150) NOT NULL,
        MODIFY mobile VARCHAR(30) NOT NULL,
        MODIFY address_line1 VARCHAR(255) NOT NULL,
        MODIFY address_line2 VARCHAR(255) NOT NULL,
        MODIFY country VARCHAR(100) NOT NULL,
        MODIFY city VARCHAR(100) NOT NULL,
        MODIFY state VARCHAR(100) NOT NULL,
        MODIFY zip_code VARCHAR(20) NOT NULL");
    $conn->query("ALTER TABLE customers ADD UNIQUE KEY uq_customers_user_id (user_id)");
}

// Migrate the original orders table to the checkout order structure.
$old_order_date = $conn->query("SHOW COLUMNS FROM orders LIKE 'order_date'");
if ($old_order_date && $old_order_date->num_rows > 0) {
    $conn->query("ALTER TABLE orders
        ADD COLUMN user_id INT NULL AFTER id,
        ADD COLUMN status VARCHAR(30) NULL AFTER total_amount,
        ADD COLUMN created_at TIMESTAMP NULL AFTER status");
    $conn->query("UPDATE orders o
        LEFT JOIN customers c ON c.id = o.customer_id
        SET o.user_id = COALESCE(c.user_id, 0),
            o.status = COALESCE(o.status, 'Placed'),
            o.created_at = o.order_date");
    $conn->query("UPDATE orders SET status = 'Placed' WHERE status IS NULL OR status = ''");
    $conn->query("ALTER TABLE orders
        DROP COLUMN order_date,
        MODIFY user_id INT NOT NULL,
        MODIFY customer_id INT NOT NULL,
        MODIFY total_amount DECIMAL(10,2) NOT NULL,
        MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Placed',
        MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
}
$order_items_table = $conn->query("SHOW TABLES LIKE 'order_items'");
if ($order_items_table && $order_items_table->num_rows > 0) {
    $user_id_column = $conn->query("SHOW COLUMNS FROM order_items LIKE 'user_id'");
    if ($user_id_column && $user_id_column->num_rows === 0) {
        $conn->query("ALTER TABLE order_items ADD COLUMN user_id INT NOT NULL DEFAULT 0 AFTER order_id");
    }
}
?>
