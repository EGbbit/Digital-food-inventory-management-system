<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$total_orders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$served_orders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='served'")->fetch_assoc()['c'];
$total_wastage = $conn->query("SELECT IFNULL(SUM(quantity),0) AS total FROM wastage_logs")->fetch_assoc()['total'];
$low_stock = $conn->query("SELECT COUNT(*) AS c FROM ingredients WHERE current_stock <= reorder_level")->fetch_assoc()['c'];

$top_items = $conn->query("SELECT mi.name, SUM(oi.quantity) AS qty
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    GROUP BY oi.menu_item_id
    ORDER BY qty DESC
    LIMIT 5");

$low_items = $conn->query("SELECT name, current_stock, reorder_level, unit
    FROM ingredients
    WHERE current_stock <= reorder_level
    ORDER BY (current_stock - reorder_level) ASC
    LIMIT 8");

$conn->query("CREATE TABLE IF NOT EXISTS predictive_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL UNIQUE,
    report_label VARCHAR(40) NOT NULL,
    report_body TEXT NOT NULL,
    generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'manual',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$latestPredictive = null;
$latestPredictiveRs = $conn->query("SELECT report_label, report_body, generation_mode, generated_at
    FROM predictive_reports
    ORDER BY generated_at DESC
    LIMIT 1");
if ($latestPredictiveRs && $latestPredictiveRs->num_rows > 0) {
    $latestPredictive = $latestPredictiveRs->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">FoodFlow Manager</div>
        <div class="navbar-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <nav class="admin-nav">
        <ul class="admin-nav-links">
            <li><a href="manager_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="manager_controls.php">Thresholds & Approvals</a></li>
            <li><a href="../admin/inventory_reports.php">Reports</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="role-welcome">
            <h1> Manager Dashboard</h1>
            <p>Performance, stock risk, and kitchen trends</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $total_orders; ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $served_orders; ?></div><div class="stat-label">Served Orders</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo number_format((float)$total_wastage, 2); ?></div><div class="stat-label">Total Wastage Qty</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $low_stock; ?></div><div class="stat-label">Low Stock Items</div></div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3>Top Menu Items</h3>
                <?php if ($top_items->num_rows > 0): ?>
                    <ul class="order-list">
                        <?php while($item = $top_items->fetch_assoc()): ?>
                            <li class="order-item">
                                <div class="item-primary"><?php echo htmlspecialchars($item['name']); ?></div>
                                <span class="status-badge">Qty <?php echo (int)$item['qty']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No order data yet.</p>
                <?php endif; ?>

                <div style="margin-top:12px;">
                    <a href="manager_controls.php#menu-management" class="btn btn-primary">Edit Menu Items</a>
                </div>
            </div>

            <div class="card">
                <h3> Ingredients at Risk</h3>
                <?php if ($low_items->num_rows > 0): ?>
                    <ul class="order-list">
                        <?php while($ingredient = $low_items->fetch_assoc()): ?>
                            <li class="order-item">
                                <div class="item-primary"><?php echo htmlspecialchars($ingredient['name']); ?></div>
                                <div class="order-details">
                                    Stock <?php echo number_format((float)$ingredient['current_stock'], 2); ?> <?php echo htmlspecialchars($ingredient['unit']); ?>
                                    / Reorder <?php echo number_format((float)$ingredient['reorder_level'], 2); ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No low-stock ingredients currently.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3> Predictive Report Snapshot</h3>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                    <a href="manager_controls.php" class="btn btn-primary">Open Report Controls</a>
                    <a href="manager_controls.php#menu-management" class="btn btn-warning">Go to Menu Controls</a>
                </div>
                <?php if ($latestPredictive): ?>
                    <p><strong><?php echo htmlspecialchars((string)$latestPredictive['report_label']); ?></strong> (<?php echo htmlspecialchars((string)$latestPredictive['generation_mode']); ?>)</p>
                    <p style="color:#666;font-size:13px;">Generated: <?php echo date('Y-m-d H:i', strtotime((string)$latestPredictive['generated_at'])); ?></p>
                    <pre style="white-space:pre-wrap;background:#fafafa;border:1px solid #eee;padding:10px;border-radius:8px;margin-top:8px;"><?php echo htmlspecialchars((string)$latestPredictive['report_body']); ?></pre>
                <?php else: ?>
                    <p>No predictive report generated yet. Open Report Controls and click Generate Report.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
