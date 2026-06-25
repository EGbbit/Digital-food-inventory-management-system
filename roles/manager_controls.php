<?php
// Placeholder file created as requested.
//under roles
//code for manager_controls.php

require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS predictive_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL UNIQUE,
    report_label VARCHAR(40) NOT NULL,
    report_body TEXT NOT NULL,
    generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'manual',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_predictive_report'])) {
        $monthStart = date('Y-m-01 00:00:00');
        $nextMonthStart = date('Y-m-01 00:00:00', strtotime('+1 month'));
        $reportMonthDate = date('Y-m-01');
        $monthLabel = date('F Y');

        $weeklyItemSales = [];
        $weeklyStmt = $conn->prepare("SELECT
            mi.name AS item_name,
            FLOOR((DAY(o.created_at) - 1) / 7) + 1 AS week_no,
            SUM(oi.quantity) AS qty
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN menu_items mi ON mi.id = oi.menu_item_id
            WHERE o.created_at >= ?
              AND o.created_at < ?
              AND o.status <> 'cancelled'
            GROUP BY mi.name, week_no
            ORDER BY mi.name, week_no");

        if ($weeklyStmt) {
            $weeklyStmt->bind_param('ss', $monthStart, $nextMonthStart);
            $weeklyStmt->execute();
            $weeklyResult = $weeklyStmt->get_result();

            while ($row = $weeklyResult->fetch_assoc()) {
                $itemName = (string)$row['item_name'];
                $weekNo = (int)$row['week_no'];
                $qty = (float)$row['qty'];

                if (!isset($weeklyItemSales[$itemName])) {
                    $weeklyItemSales[$itemName] = [];
                }
                $weeklyItemSales[$itemName][$weekNo] = $qty;
            }
            $weeklyStmt->close();
        }

        $bestGrowth = null;
        foreach ($weeklyItemSales as $itemName => $weeks) {
            for ($week = 2; $week <= 5; $week++) {
                $prevQty = (float)($weeks[$week - 1] ?? 0);
                $currQty = (float)($weeks[$week] ?? 0);

                if ($prevQty > 0 && $currQty > $prevQty) {
                    $factor = $currQty / $prevQty;
                    if ($bestGrowth === null || $factor > $bestGrowth['factor']) {
                        $bestGrowth = [
                            'item' => $itemName,
                            'from_week' => $week - 1,
                            'to_week' => $week,
                            'factor' => $factor,
                        ];
                    }
                }
            }
        }

        $analysisLines = [];
        if ($bestGrowth !== null && $bestGrowth['factor'] >= 1.5) {
            $multiplier = max(2, min(4, (int)ceil($bestGrowth['factor'])));
            $analysisLines[] = $bestGrowth['item'] . ' sold ' . number_format($bestGrowth['factor'], 1) . 'x in week ' . (int)$bestGrowth['to_week'] .
                ' compared to week ' . (int)$bestGrowth['from_week'] . ' of ' . $monthLabel . '.';
            $analysisLines[] = 'Recommendation: stock up around ' . $multiplier . 'x for this dish ahead of similar demand periods.';
        } else {
            $analysisLines[] = 'No major weekly sales spike detected in ' . $monthLabel . '.';
            $analysisLines[] = 'Recommendation: maintain a 30% buffer on top-selling dishes.';
        }

        $topStmt = $conn->prepare("SELECT mi.name AS item_name, SUM(oi.quantity) AS total_qty
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN menu_items mi ON mi.id = oi.menu_item_id
            WHERE o.created_at >= ?
              AND o.created_at < ?
              AND o.status <> 'cancelled'
            GROUP BY mi.id, mi.name
            ORDER BY total_qty DESC
            LIMIT 5");

        if ($topStmt) {
            $topStmt->bind_param('ss', $monthStart, $nextMonthStart);
            $topStmt->execute();
            $topRs = $topStmt->get_result();
            $analysisLines[] = 'Top dishes this month:';
            while ($row = $topRs->fetch_assoc()) {
                $analysisLines[] = '- ' . (string)$row['item_name'] . ': ' . (int)$row['total_qty'] . ' sold';
            }
            $topStmt->close();
        }

        $reportBody = implode("\n", $analysisLines);

        $upsert = $conn->prepare("INSERT INTO predictive_reports (report_month, report_label, report_body, generation_mode)
            VALUES (?, ?, ?, 'manual')
            ON DUPLICATE KEY UPDATE report_label = VALUES(report_label), report_body = VALUES(report_body), generation_mode = 'manual', generated_at = CURRENT_TIMESTAMP");
        $upsert->bind_param('sss', $reportMonthDate, $monthLabel, $reportBody);
        if ($upsert->execute()) {
            $message = 'Predictive report generated for ' . $monthLabel . '.';
        } else {
            $message = 'Failed to generate predictive report: ' . $upsert->error;
        }
        $upsert->close();
    }

    if (isset($_POST['add_menu_item'])) {
        $name = trim($_POST['menu_name'] ?? '');
        $category = trim($_POST['menu_category'] ?? '');
        $selling_price = (float)($_POST['selling_price'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        if ($category === '') {
            $category = 'General';
        }

        if ($name !== '' && $selling_price > 0) {
            $findStmt = $conn->prepare('SELECT id FROM menu_items WHERE LOWER(name) = LOWER(?) AND LOWER(COALESCE(category, "")) = LOWER(?) LIMIT 1');
            $findStmt->bind_param('ss', $name, $category);
            $findStmt->execute();
            $existing = $findStmt->get_result()->fetch_assoc();
            $findStmt->close();

            if ($existing) {
                $existingId = (int)$existing['id'];
                $updateStmt = $conn->prepare('UPDATE menu_items SET selling_price = ?, is_available = ? WHERE id = ?');
                $updateStmt->bind_param('dii', $selling_price, $is_available, $existingId);
                if ($updateStmt->execute()) {
                    $message = 'Menu item updated and reflected for all users.';
                } else {
                    $message = 'Failed to update menu item: ' . $updateStmt->error;
                }
                $updateStmt->close();
            } else {
                $stmt = $conn->prepare('INSERT INTO menu_items (name, category, selling_price, is_available) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssdi', $name, $category, $selling_price, $is_available);
                if ($stmt->execute()) {
                    $message = 'Menu item added and reflected for all users.';
                } else {
                    $message = 'Failed to add menu item: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $message = 'Please provide a valid menu name and price.';
        }
    }

    if (isset($_POST['load_starter_menu'])) {
        $starterItems = [
            ['Beef Burger', 'Main', 550.00, 1],
            ['Chicken Wrap', 'Main', 480.00, 1],
            ['Vegetable Pasta', 'Main', 620.00, 1],
            ['Grilled Fish', 'Main', 760.00, 1],
            ['French Fries', 'Side', 250.00, 1],
            ['Caesar Salad', 'Starter', 390.00, 1],
            ['Tomato Soup', 'Starter', 320.00, 1],
            ['Fresh Juice', 'Drink', 220.00, 1],
            ['Iced Tea', 'Drink', 180.00, 1],
            ['Fruit Salad', 'Dessert', 300.00, 1]
        ];

        $inserted = 0;
        foreach ($starterItems as $item) {
            $check = $conn->prepare('SELECT id FROM menu_items WHERE name = ? LIMIT 1');
            $check->bind_param('s', $item[0]);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$exists) {
                $ins = $conn->prepare('INSERT INTO menu_items (name, category, selling_price, is_available) VALUES (?, ?, ?, ?)');
                $ins->bind_param('ssdi', $item[0], $item[1], $item[2], $item[3]);
                if ($ins->execute()) {
                    $inserted++;
                }
                $ins->close();
            }
        }
        $message = 'Starter menu sync complete. New items added: ' . $inserted . '.';
    }

    if (isset($_POST['delete_menu_item'])) {
        $menu_item_id = (int)($_POST['menu_item_id'] ?? 0);
        if ($menu_item_id > 0) {
            $stmt = $conn->prepare('DELETE FROM menu_items WHERE id = ?');
            $stmt->bind_param('i', $menu_item_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = 'Menu item removed.';
                } else {
                    $message = 'Menu item not found.';
                }
            } else {
                $message = 'Cannot remove this item yet. It may already be used in existing orders or recipes.';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['update_threshold'])) {
        $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
        $reorder_level = (float)($_POST['reorder_level'] ?? 0);
        if ($ingredient_id > 0 && $reorder_level >= 0) {
            $stmt = $conn->prepare('UPDATE ingredients SET reorder_level = ? WHERE id = ?');
            $stmt->bind_param('di', $reorder_level, $ingredient_id);
            $stmt->execute();
            $message = 'Threshold updated.';
        }
    }

    if (isset($_POST['resolve_alert'])) {
        $alert_id = (int)($_POST['alert_id'] ?? 0);
        if ($alert_id > 0) {
            $conn->query("UPDATE alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = {$alert_id}");
            $message = 'Alert resolved.';
        }
    }
}

$ingredients = $conn->query('SELECT id, name, unit, current_stock, reorder_level FROM ingredients ORDER BY name');
$alerts = $conn->query('SELECT a.id, a.alert_type, a.message, a.created_at, i.name AS ingredient_name FROM alerts a JOIN ingredients i ON a.ingredient_id=i.id WHERE a.is_resolved=0 ORDER BY a.created_at DESC');
$menu_items = $conn->query('SELECT id, name, category, selling_price, is_available, created_at FROM menu_items ORDER BY name');

$latestPredictive = null;
$latestRs = $conn->query("SELECT report_label, report_body, generation_mode, generated_at
    FROM predictive_reports
    ORDER BY generated_at DESC
    LIMIT 1");
if ($latestRs && $latestRs->num_rows > 0) {
    $latestPredictive = $latestRs->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Controls - FoodFlow</title>
    <link rel="stylesheet" href="../admin/admin_styles.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Manager</div>
    <div class="navbar-user"><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div>
</nav>
<nav class="admin-nav">
    <ul class="admin-nav-links">
        <li><a href="manager_dashboard.php">Dashboard</a></li>
        <li><a href="manager_controls.php" class="active">Thresholds & Approvals</a></li>
        <li><a href="open_menu.php">Open Food Menu</a></li>
        <li><a href="manager_reports.php">Reports</a></li>
    </ul>
</nav>
<div class="container">
    <div class="card">
        <h3>⚙️ Threshold Management</h3>
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Ingredient</th><th>Current Stock</th><th>Reorder Level</th><th>Update</th></tr></thead>
                <tbody>
                <?php while($i = $ingredients->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($i['name']); ?></td>
                        <td><?php echo number_format((float)$i['current_stock'], 2) . ' ' . htmlspecialchars($i['unit']); ?></td>
                        <td><?php echo number_format((float)$i['reorder_level'], 2); ?></td>
                        <td>
                            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                                <input type="hidden" name="ingredient_id" value="<?php echo (int)$i['id']; ?>">
                                <input type="number" step="0.01" min="0" name="reorder_level" value="<?php echo number_format((float)$i['reorder_level'], 2, '.', ''); ?>" required>
                                <button type="submit" name="update_threshold" class="btn btn-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 id="menu-management"> Menu Management</h3>
        <div class="search-box" style="margin-bottom:10px;">
            <input id="menu-table-search" type="text" placeholder="Search food item in menu table...">
        </div>
        <form method="POST" style="margin-bottom:12px;">
            <button type="submit" name="load_starter_menu" class="btn btn-success">Load Starter Menu Variety</button>
        </form>
        <form method="POST" style="display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:10px;align-items:end;">
            <input type="text" name="menu_name" placeholder="Item name" required>
            <input type="text" name="menu_category" placeholder="Category">
            <input type="number" step="0.01" min="0.01" name="selling_price" placeholder="Price (Kshs.)" required>
            <label style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="is_available" checked> Available</label>
            <button type="submit" name="add_menu_item" class="btn btn-primary">Add Menu Item</button>
        </form>

        <div class="table-responsive" style="margin-top:14px;">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($m = $menu_items->fetch_assoc()): ?>
                    <tr class="menu-row">
                        <td><?php echo htmlspecialchars($m['name']); ?></td>
                        <td><?php echo htmlspecialchars((string)$m['category']); ?></td>
                        <td>Kshs. <?php echo number_format((float)$m['selling_price'], 2); ?></td>
                        <td><?php echo ((int)$m['is_available'] === 1) ? 'Available' : 'Unavailable'; ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this menu item?');">
                                <input type="hidden" name="menu_item_id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" name="delete_menu_item" class="btn btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Predictive Sales Report</h3>
        <div style="display:grid;grid-template-columns:280px 1fr;gap:14px;align-items:start;">
            <div>
                <form method="POST">
                    <button type="submit" name="generate_predictive_report" class="btn btn-primary" style="width:100%;">Generate Report</button>
                </form>
                <p style="margin-top:8px;color:#666;font-size:13px;">Generates or refreshes the current month analysis.</p>
            </div>
            <div style="background:#fafafa;border:1px solid #eee;border-radius:8px;padding:10px;min-height:120px;">
                <?php if ($latestPredictive): ?>
                    <p><strong><?php echo htmlspecialchars((string)$latestPredictive['report_label']); ?></strong> (<?php echo htmlspecialchars((string)$latestPredictive['generation_mode']); ?>)</p>
                    <p style="color:#666;font-size:13px;">Generated: <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime((string)$latestPredictive['generated_at']))); ?></p>
                    <pre style="white-space:pre-wrap;margin-top:8px;"><?php echo htmlspecialchars((string)$latestPredictive['report_body']); ?></pre>
                <?php else: ?>
                    <p>No predictive report generated yet. Click Generate Report.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Alert Review Queue</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Type</th><th>Ingredient</th><th>Message</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($a = $alerts->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['alert_type']); ?></td>
                        <td><?php echo htmlspecialchars($a['ingredient_name']); ?></td>
                        <td><?php echo htmlspecialchars($a['message']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($a['created_at'])); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="alert_id" value="<?php echo (int)$a['id']; ?>">
                                <button type="submit" name="resolve_alert" class="btn btn-success">Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    const menuTableSearch = document.getElementById('menu-table-search');
    if (menuTableSearch) {
        menuTableSearch.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.menu-row').forEach((row) => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>