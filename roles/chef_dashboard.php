<?php
// Placeholder file created as requested.
// under roles
//code for chef_dashboard.php

require_once __DIR__ . '/../core/auth.php';
require_role('chef');

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "food_inventory";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$chef_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $target_status = $_POST['target_status'] ?? '';
    $allowed_targets = ['pending', 'preparing', 'served', 'cancelled'];

    if ($order_id <= 0 || !in_array($target_status, $allowed_targets, true)) {
        $error = 'Invalid status update request.';
    } else {
        $currentStmt = $conn->prepare('SELECT status, order_number FROM orders WHERE id = ? LIMIT 1');
        $currentStmt->bind_param('i', $order_id);
        $currentStmt->execute();
        $order = $currentStmt->get_result()->fetch_assoc();
        $currentStmt->close();

        if (!$order) {
            $error = 'Order not found.';
        } else {
            $current_status = $order['status'];
            $valid = false;

            if ($current_status === 'pending' && in_array($target_status, ['preparing', 'cancelled'], true)) {
                $valid = true;
            } elseif ($current_status === 'preparing' && in_array($target_status, ['pending', 'served', 'cancelled'], true)) {
                $valid = true;
            }

            if (!$valid) {
                $error = 'Invalid transition from ' . $current_status . ' to ' . $target_status . '.';
            } else {
                $updateStmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $updateStmt->bind_param('si', $target_status, $order_id);
                if ($updateStmt->execute()) {
                    $message = 'Order ' . $order['order_number'] . ' updated to ' . $target_status . '.';
                } else {
                    $error = 'Status update failed: ' . $updateStmt->error;
                }
                $updateStmt->close();
            }
        }
    }
}

$today_preparing = $conn->query("SELECT COUNT(*) AS count FROM orders WHERE status='preparing' AND DATE(updated_at)=CURDATE()")->fetch_assoc()['count'];
$today_served = $conn->query("SELECT COUNT(*) AS count FROM orders WHERE status='served' AND DATE(updated_at)=CURDATE()")->fetch_assoc()['count'];
$today_usage = $conn->query("SELECT IFNULL(SUM(quantity),0) AS total FROM stock_movements WHERE movement_type='usage' AND DATE(created_at)=CURDATE()")->fetch_assoc()['total'];
$today_wastage = $conn->query("SELECT IFNULL(SUM(quantity),0) AS total FROM wastage_logs WHERE logged_by=$chef_id AND DATE(logged_at)=CURDATE()")->fetch_assoc()['total'];

$pending_orders = $conn->query("SELECT
    o.id,
    o.order_number,
    o.table_number,
    o.status,
    o.total_amount,
    o.updated_at,
    IFNULL(GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) ORDER BY mi.name SEPARATOR ', '), 'No items') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
    WHERE o.status = 'pending'
    GROUP BY o.id, o.order_number, o.table_number, o.status, o.total_amount, o.updated_at
    ORDER BY o.updated_at ASC
    LIMIT 6");

$preparing_orders = $conn->query("SELECT
    o.id,
    o.order_number,
    o.table_number,
    o.status,
    o.total_amount,
    o.updated_at,
    IFNULL(GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) ORDER BY mi.name SEPARATOR ', '), 'No items') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
    WHERE o.status = 'preparing'
    GROUP BY o.id, o.order_number, o.table_number, o.status, o.total_amount, o.updated_at
    ORDER BY o.updated_at ASC
    LIMIT 6");

$served_orders = $conn->query("SELECT
    o.id,
    o.order_number,
    o.table_number,
    o.status,
    o.total_amount,
    o.updated_at,
    IFNULL(GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) ORDER BY mi.name SEPARATOR ', '), 'No items') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
    WHERE o.status = 'served'
    GROUP BY o.id, o.order_number, o.table_number, o.status, o.total_amount, o.updated_at
    ORDER BY o.updated_at DESC
    LIMIT 6");

$low_stock = $conn->query("SELECT name, current_stock, reorder_level, unit
    FROM ingredients WHERE current_stock <= reorder_level ORDER BY current_stock ASC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chef Dashboard - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">FoodFlow Chef</div>
        <div class="navbar-user">
            <span>Welcome, Chef <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <nav class="admin-nav">
        <ul class="admin-nav-links">
            <li><a href="../roles/chef_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="../roles/chef_inventory.php">Inventory Console</a></li>
            <li><a href="../admin/ingredients.php">Ingredients</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <div class="role-welcome">
            <h1> Chef Dashboard</h1>
            <p>Kitchen operations, prep queue, and ingredient risk</p>
            <div class="role-badge">Head Chef</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $today_preparing; ?></div><div class="stat-label">Preparing Orders</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $today_served; ?></div><div class="stat-label">Served Today</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo number_format((float)$today_usage, 2); ?></div><div class="stat-label">Usage Logged (Qty)</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo number_format((float)$today_wastage, 2); ?></div><div class="stat-label">Wastage Today (Qty)</div></div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div style="background:#ffebee;border:1px solid #ef9a9a;border-radius:12px;padding:14px;">
                <h3 style="color:#c62828;"> Pending Orders</h3>
                <?php if ($pending_orders->num_rows > 0): ?>
                    <div class="order-list">
                        <?php while($order = $pending_orders->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="order-time"><?php echo date('g:i A', strtotime($order['updated_at'])); ?></div>
                                <div class="item-primary">
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <div class="order-details">Table <?php echo htmlspecialchars($order['table_number']); ?> • Total Kshs. <?php echo number_format((float)$order['total_amount'], 2); ?></div>
                                    <div class="order-details">Items: <?php echo htmlspecialchars((string)$order['items_summary']); ?></div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <input type="hidden" name="target_status" value="preparing">
                                            <button type="submit" class="btn btn-primary" style="padding:6px 10px;">Mark Preparing</button>
                                        </form>

                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <input type="hidden" name="target_status" value="cancelled">
                                            <button type="submit" class="btn btn-warning" style="padding:6px 10px;">Cancel</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No pending orders.</p>
                <?php endif; ?>
                </div>

                <div style="margin-top: 1rem;background:#fff8e1;border:1px solid #ffe082;border-radius:12px;padding:14px;">
                <h3 style="margin-top: 0;color:#f9a825;"> Preparing Orders</h3>
                <?php if ($preparing_orders->num_rows > 0): ?>
                    <div class="order-list">
                        <?php while($order = $preparing_orders->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="order-time"><?php echo date('g:i A', strtotime($order['updated_at'])); ?></div>
                                <div class="item-primary">
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <div class="order-details">Table <?php echo htmlspecialchars($order['table_number']); ?> • Total Kshs. <?php echo number_format((float)$order['total_amount'], 2); ?></div>
                                    <div class="order-details">Items: <?php echo htmlspecialchars((string)$order['items_summary']); ?></div>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <input type="hidden" name="target_status" value="pending">
                                            <button type="submit" class="btn btn-warning" style="padding:6px 10px;">Mark Pending</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <input type="hidden" name="target_status" value="served">
                                            <button type="submit" class="btn btn-success" style="padding:6px 10px;">Mark Served</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <input type="hidden" name="target_status" value="cancelled">
                                            <button type="submit" class="btn btn-warning" style="padding:6px 10px;">Cancel</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No preparing orders.</p>
                <?php endif; ?>
                </div>

                <div style="margin-top: 1rem;background:#e8f5e9;border:1px solid #a5d6a7;border-radius:12px;padding:14px;">
                <h3 style="margin-top: 0;color:#2e7d32;"> Served Orders</h3>
                <?php if ($served_orders->num_rows > 0): ?>
                    <div class="order-list">
                        <?php while($order = $served_orders->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="order-time"><?php echo date('g:i A', strtotime($order['updated_at'])); ?></div>
                                <div class="item-primary">
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                    <div class="order-details">Table <?php echo htmlspecialchars($order['table_number']); ?> • Total Kshs. <?php echo number_format((float)$order['total_amount'], 2); ?></div>
                                    <div class="order-details">Items: <?php echo htmlspecialchars((string)$order['items_summary']); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No served orders yet.</p>
                <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h3> Low-Stock Ingredients</h3>
                <?php if ($low_stock->num_rows > 0): ?>
                    <div class="order-list">
                        <?php while($ingredient = $low_stock->fetch_assoc()): ?>
                            <div class="order-item">
                                <div class="item-primary"><?php echo htmlspecialchars($ingredient['name']); ?></div>
                                <div class="order-details"><?php echo number_format((float)$ingredient['current_stock'], 2); ?> <?php echo htmlspecialchars($ingredient['unit']); ?> / reorder <?php echo number_format((float)$ingredient['reorder_level'], 2); ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>All stocks are above reorder level.</p>
                <?php endif; ?>

                <h3 style="margin-top: 2rem;"> Quick Actions</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem; padding: 1rem;">
                    <a href="chef_inventory.php" class="btn btn-primary">Log Ingredient Usage</a>
                    <a href="chef_inventory.php" class="btn btn-warning">Log Wastage</a>
                    <a href="../admin/ingredients.php" class="btn btn-success">Check Ingredient Stock</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>