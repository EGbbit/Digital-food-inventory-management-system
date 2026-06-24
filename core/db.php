<?php
declare(strict_types=1);

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "food_inventory";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'waiter', 'chef', 'manager') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(80),
    unit VARCHAR(20) NOT NULL,
    current_stock DECIMAL(10,2) NOT NULL DEFAULT 0,
    reorder_level DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(80),
    selling_price DECIMAL(10,2) NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) UNIQUE NOT NULL,
    waiter_id INT NOT NULL,
    table_number VARCHAR(20),
    status ENUM('pending', 'preparing', 'served', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE RESTRICT
)");

$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
)");

$conn->query("CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_item_id INT NOT NULL,
    ingredient_id INT NOT NULL,
    quantity_required DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_recipe_line (menu_item_id, ingredient_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    movement_type ENUM('stock_in', 'usage', 'adjustment', 'wastage') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    reference_type ENUM('purchase', 'order', 'manual', 'wastage') DEFAULT 'manual',
    reference_id INT NULL,
    notes VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
)");

$conn->query("CREATE TABLE IF NOT EXISTS wastage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    reason VARCHAR(120) NOT NULL,
    logged_by INT NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
    FOREIGN KEY (logged_by) REFERENCES users(id) ON DELETE RESTRICT
)");

$conn->query("CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    alert_type ENUM('low_stock', 'out_of_stock') NOT NULL,
    message VARCHAR(255) NOT NULL,
    is_resolved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS predictive_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL UNIQUE,
    report_label VARCHAR(40) NOT NULL,
    report_body TEXT NOT NULL,
    generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'auto',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$exists = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc();
$isFreshInstall = ((int)$exists['c'] === 0);

$defaultHash = password_hash('1234', PASSWORD_BCRYPT);
$defaultUsers = [
    ['role' => 'admin', 'name' => 'System Admin', 'email' => 'admin@gmail.com', 'phone' => '555-1001'],
    ['role' => 'waiter', 'name' => 'Mia Waiter', 'email' => 'waiter@gmail.com', 'phone' => '555-1002'],
    ['role' => 'chef', 'name' => 'Liam Chef', 'email' => 'chef@gmail.com', 'phone' => '555-1003'],
    ['role' => 'manager', 'name' => 'Noah Manager', 'email' => 'manager@gmail.com', 'phone' => '555-1004'],
];

$findRoleStmt = $conn->prepare("SELECT id FROM users WHERE role = ? ORDER BY id ASC LIMIT 1");
$insertUserStmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");

foreach ($defaultUsers as $user) {
    $role = $user['role'];
    $name = $user['name'];
    $email = $user['email'];
    $phone = $user['phone'];

    $findRoleStmt->bind_param('s', $role);
    $findRoleStmt->execute();
    $roleResult = $findRoleStmt->get_result()->fetch_assoc();

    if (!$roleResult) {
        $insertUserStmt->bind_param('sssss', $name, $email, $defaultHash, $phone, $role);
        $insertUserStmt->execute();
    }
}

$findRoleStmt->close();
$insertUserStmt->close();

if ($isFreshInstall) {

    $conn->query("INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost) VALUES
        ('Rice', 'Dry Goods', 'kg', 60, 15, 2.40),
        ('Chicken Breast', 'Meat', 'kg', 25, 8, 8.90),
        ('Tomato', 'Vegetables', 'kg', 18, 7, 3.20),
        ('Cooking Oil', 'Pantry', 'liter', 30, 10, 4.50)");

    $conn->query("INSERT INTO menu_items (name, category, selling_price, is_available) VALUES
        ('Chicken Fried Rice', 'Main Course', 18.00, 1),
        ('Tomato Pasta', 'Main Course', 16.50, 1)");

    $waiterRow = $conn->query("SELECT id FROM users WHERE role = 'waiter' ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $waiterId = (int)($waiterRow['id'] ?? 0);

    if ($waiterId > 0) {
        $conn->query("INSERT INTO orders (order_number, waiter_id, table_number, status, total_amount) VALUES
            ('ORD-001', {$waiterId}, 'T1', 'served', 34.50),
            ('ORD-002', {$waiterId}, 'T4', 'preparing', 18.00)");
    }

    $conn->query("INSERT INTO alerts (ingredient_id, alert_type, message, is_resolved) VALUES
        (2, 'low_stock', 'Chicken Breast is nearing reorder level.', 0)");
}

$conn->close();
