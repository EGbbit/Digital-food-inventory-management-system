<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$manager_message = '';
$manager_id = (int)($_SESSION['user_id'] ?? 0);
$login_success_message = (string)($_SESSION['login_success_message'] ?? '');
if ($login_success_message === '') {
    $login_success_message = trim((string)($_GET['login_msg'] ?? ''));
}
if ($login_success_message !== '') {
    unset($_SESSION['login_success_message']);
}

$conn->query("CREATE TABLE IF NOT EXISTS order_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    table_number VARCHAR(20) NOT NULL,
    waiter_id INT NOT NULL,
    alert_status ENUM('new','seen') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_status (alert_status),
    INDEX idx_order_id (order_id)
)");

$total_orders = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$served_orders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='served'")->fetch_assoc()['c'];
$total_wastage = $conn->query("SELECT IFNULL(SUM(quantity),0) AS total FROM wastage_logs")->fetch_assoc()['total'];
$low_stock = $conn->query("SELECT COUNT(*) AS c FROM ingredients WHERE current_stock <= reorder_level")->fetch_assoc()['c'];
$preparing_orders = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'preparing'")->fetch_assoc()['c'];
$new_order_alerts = (int)$conn->query("SELECT COUNT(*) AS c FROM order_alerts WHERE alert_status = 'new'")->fetch_assoc()['c'];

$kitchen_flow_orders = $conn->query("SELECT order_number, table_number, status, created_at
    FROM orders
    WHERE status = 'preparing'
    ORDER BY created_at DESC
    LIMIT 6");

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

$conn->query("CREATE TABLE IF NOT EXISTS unavailable_item_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_query VARCHAR(120) NOT NULL,
    request_date DATE NOT NULL,
    request_count INT NOT NULL DEFAULT 1,
    last_waiter_id INT NULL,
    is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    acknowledged_by INT NULL,
    acknowledged_at TIMESTAMP NULL,
    last_requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item_query_date (item_query, request_date),
    INDEX idx_request_date (request_date)
)");

$ackCol = $conn->query("SHOW COLUMNS FROM unavailable_item_requests LIKE 'is_acknowledged'");
if ($ackCol && $ackCol->num_rows === 0) {
    $conn->query("ALTER TABLE unavailable_item_requests ADD COLUMN is_acknowledged TINYINT(1) NOT NULL DEFAULT 0 AFTER last_waiter_id");
}

$ackByCol = $conn->query("SHOW COLUMNS FROM unavailable_item_requests LIKE 'acknowledged_by'");
if ($ackByCol && $ackByCol->num_rows === 0) {
    $conn->query("ALTER TABLE unavailable_item_requests ADD COLUMN acknowledged_by INT NULL AFTER is_acknowledged");
}

$ackAtCol = $conn->query("SHOW COLUMNS FROM unavailable_item_requests LIKE 'acknowledged_at'");
if ($ackAtCol && $ackAtCol->num_rows === 0) {
    $conn->query("ALTER TABLE unavailable_item_requests ADD COLUMN acknowledged_at TIMESTAMP NULL AFTER acknowledged_by");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ack_unavailable_item'])) {
    $demandId = (int)($_POST['demand_id'] ?? 0);
    if ($demandId > 0) {
        $ackDemandStmt = $conn->prepare('UPDATE unavailable_item_requests SET is_acknowledged = 1, acknowledged_by = ?, acknowledged_at = NOW() WHERE id = ?');
        if ($ackDemandStmt) {
            $ackDemandStmt->bind_param('ii', $manager_id, $demandId);
            if ($ackDemandStmt->execute() && $ackDemandStmt->affected_rows > 0) {
                $manager_message = 'Unavailable item demand acknowledged. It is now removed from active demand list.';
            } else {
                $manager_message = 'Could not acknowledge the selected unavailable item demand.';
            }
            $ackDemandStmt->close();
        }
    }
}

$unavailable_demands = $conn->query("SELECT id, item_query, request_count, last_requested_at
    FROM unavailable_item_requests
    WHERE request_date = CURDATE()
      AND is_acknowledged = 0
    ORDER BY request_count DESC, item_query ASC
    LIMIT 8");

$conn->query("CREATE TABLE IF NOT EXISTS predictive_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL UNIQUE,
    report_label VARCHAR(40) NOT NULL,
    report_body TEXT NOT NULL,
    generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'manual',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS chef_stock_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    chef_id INT NOT NULL,
    observed_stock DECIMAL(10,2) NOT NULL,
    reorder_level_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0,
    suggested_restock_amount DECIMAL(10,2) NULL,
    expected_expiry_date DATE NULL,
    shelf_life_days INT NULL,
    urgency ENUM('normal', 'watch', 'urgent') NOT NULL DEFAULT 'watch',
    comment VARCHAR(300) NOT NULL,
    is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    acknowledged_by INT NULL,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chef_stock_notes_created (created_at),
    INDEX idx_chef_stock_notes_ack (is_acknowledged),
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    FOREIGN KEY (chef_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
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
<body class="dashboard-photo dashboard-manager">
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
            <li><a href="manager_reports.php">Reports</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="role-welcome">
            <h1> Manager Dashboard</h1>
            <p>Performance, stock risk, and kitchen trends</p>
            <p style="margin-top:8px;color:#1f7a8c;font-weight:700;">Login status: You are signed in as <?php echo htmlspecialchars((string)$_SESSION['user_name']); ?> (Manager).</p>
            <?php if ($login_success_message !== ''): ?>
                <p style="margin-top:10px;color:#2e7d32;font-weight:600;"><?php echo htmlspecialchars($login_success_message); ?></p>
            <?php endif; ?>
            <?php if ($manager_message !== ''): ?>
                <p style="margin-top:10px;color:#2e7d32;font-weight:600;"><?php echo htmlspecialchars($manager_message); ?></p>
            <?php endif; ?>
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
                    <a href="open_menu.php" class="btn btn-primary">Open Food Menu</a>
                    <a href="manager_reports.php" class="btn btn-success">Open Reports & Graphs</a>
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

            <div class="card">
                <h3> Kitchen Coordination (Supervisor View)</h3>
                <p>Preparing: <strong><?php echo $preparing_orders; ?></strong> | New Order Alerts: <strong><?php echo $new_order_alerts; ?></strong></p>
                <div class="table-responsive" style="margin-top:10px;">
                    <table class="data-table">
                        <thead><tr><th>Order</th><th>Table</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                        <?php if ($kitchen_flow_orders && $kitchen_flow_orders->num_rows > 0): ?>
                            <?php while($ko = $kitchen_flow_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$ko['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$ko['table_number']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$ko['status']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime((string)$ko['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No preparing kitchen orders.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3>Unavailable Item Demand (Today)</h3>
                <?php if ($unavailable_demands && $unavailable_demands->num_rows > 0): ?>
                    <ul class="order-list">
                        <?php while($d = $unavailable_demands->fetch_assoc()): ?>
                            <li class="order-item">
                                <div>
                                    <div class="item-primary"><?php echo htmlspecialchars((string)$d['item_query']); ?></div>
                                    <div class="order-details" style="margin-top:4px;">Last requested: <?php echo date('Y-m-d H:i', strtotime((string)$d['last_requested_at'])); ?></div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span class="status-badge">Requests <?php echo (int)$d['request_count']; ?></span>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="demand_id" value="<?php echo (int)$d['id']; ?>">
                                        <button type="submit" name="ack_unavailable_item" class="btn btn-success btn-sm">Acknowledge Added</button>
                                    </form>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No active unavailable item demands. Items acknowledged as added are removed from this list.</p>
                <?php endif; ?>
                <p style="margin-top:10px;color:#555;">After adding demanded items in Reports page, click <strong>Acknowledge Added</strong> here to clear them from demand.</p>
            </div>
        </div>
    </div>
        <script>
            (function () {
                const refreshMs = 15000;
                setInterval(function () {
                    const active = document.activeElement;
                    const isEditing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
                    if (!document.hidden && !isEditing) {
                        window.location.reload();
                    }
                }, refreshMs);
            })();
        </script>
</body>
</html>
