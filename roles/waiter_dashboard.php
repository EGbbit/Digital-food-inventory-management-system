<?php
// Placeholder file created as requested.
//under roles
//code for waiter_dashboard.php

require_once __DIR__ . '/../core/auth.php';
require_role('waiter');

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "food_inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$waiter_id = (int)$_SESSION['user_id'];

$today_orders = $conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiter_id AND DATE(created_at)=CURDATE()")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiter_id AND status='pending'")->fetch_assoc()['count'];
$preparing_orders = $conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiter_id AND status='preparing'")->fetch_assoc()['count'];
$served_orders = $conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiter_id AND status='served'")->fetch_assoc()['count'];

$recent_orders = $conn->query("SELECT * FROM orders WHERE waiter_id = $waiter_id ORDER BY created_at DESC LIMIT 6");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiter Dashboard - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">FoodFlow Waiter</div>
        <div class="navbar-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <nav class="admin-nav">
        <ul class="admin-nav-links">
            <li><a href="waiter_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="waiter_orders.php">Record Orders</a></li>
            <li><a href="../admin/ingredients.php">Stock View</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="role-welcome">
            <h1> Waiter Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            <div class="role-badge"><?php echo ucfirst($_SESSION['role']); ?></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $today_orders; ?></div>
                <div class="stat-label">Today's Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_orders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $preparing_orders; ?></div>
                <div class="stat-label">Preparing</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $served_orders; ?></div>
                <div class="stat-label">Served</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3> Recent Orders</h3>
                <?php if ($recent_orders->num_rows > 0): ?>
                    <div class="order-list">
                        <?php while($order = $recent_orders->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="order-time">
                                    <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="item-primary">
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <div class="order-details">
                                        Table <?php echo htmlspecialchars($order['table_number']); ?> • 
                                        Amount Kshs. <?php echo number_format((float)$order['total_amount'], 2); ?>
                                    </div>
                                </div>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon"></div>
                        <h3>No Orders Yet</h3>
                        <p>No orders have been assigned yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3> Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                    <a href="waiter_orders.php" class="btn btn-primary">Record New Order</a>
                    <a href="../admin/ingredients.php" class="btn btn-success">View Ingredient Stock</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>