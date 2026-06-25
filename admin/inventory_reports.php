<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');
header('Location: ../roles/manager_reports.php');
exit();

/* Legacy report implementation retained below for reference only. */

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS predictive_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL UNIQUE,
    report_label VARCHAR(40) NOT NULL,
    report_body TEXT NOT NULL,
    generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'auto',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$summary = [
    'ingredients' => $conn->query("SELECT COUNT(*) c FROM ingredients")->fetch_assoc()['c'],
    'low_stock' => $conn->query("SELECT COUNT(*) c FROM ingredients WHERE current_stock <= reorder_level")->fetch_assoc()['c'],
    'orders' => $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'],
    'served' => $conn->query("SELECT COUNT(*) c FROM orders WHERE status='served'")->fetch_assoc()['c'],
    'wastage_qty' => $conn->query("SELECT IFNULL(SUM(quantity),0) t FROM wastage_logs")->fetch_assoc()['t']
];

$monthly = $conn->query("SELECT
    DATE_FORMAT(created_at, '%M %Y') AS month_label,
    DATE_FORMAT(created_at, '%Y-%m') AS month_sort,
    COUNT(*) AS total
    FROM orders
    GROUP BY month_sort, month_label
    ORDER BY month_sort DESC
    LIMIT 6");
$low = $conn->query("SELECT name, current_stock, reorder_level, unit FROM ingredients WHERE current_stock <= reorder_level ORDER BY current_stock ASC");

$monthStart = date('Y-m-01 00:00:00');
$nextMonthStart = date('Y-m-01 00:00:00', strtotime('+1 month'));
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
                    'from_qty' => $prevQty,
                    'to_qty' => $currQty,
                    'factor' => $factor,
                ];
            }
        }
    }
}

$analysisNotes = [];
if ($bestGrowth !== null && $bestGrowth['factor'] >= 1.5) {
    $recommendedMultiplier = max(2, min(4, (int)ceil($bestGrowth['factor'])));
    $analysisNotes[] = $bestGrowth['item'] . ' sold ' . number_format($bestGrowth['factor'], 1) . 'x in week ' . (int)$bestGrowth['to_week'] .
        ' compared to week ' . (int)$bestGrowth['from_week'] . ' of ' . $monthLabel . '. ' .
        'This indicates an event-driven or seasonal demand spike. Stock up about ' . $recommendedMultiplier . 'x for the next similar period to maintain customer satisfaction.';
} else {
    $analysisNotes[] = 'No strong weekly spike was detected in ' . $monthLabel . '. Maintain a safety buffer of about 30% above average weekly demand for top-selling dishes.';
}

$topMonthlyItems = $conn->prepare("SELECT mi.name AS item_name, SUM(oi.quantity) AS total_qty
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN menu_items mi ON mi.id = oi.menu_item_id
    WHERE o.created_at >= ?
      AND o.created_at < ?
      AND o.status <> 'cancelled'
    GROUP BY mi.id, mi.name
    ORDER BY total_qty DESC
    LIMIT 5");

$monthlyTopRows = [];
if ($topMonthlyItems) {
    $topMonthlyItems->bind_param('ss', $monthStart, $nextMonthStart);
    $topMonthlyItems->execute();
    $topResult = $topMonthlyItems->get_result();

    while ($row = $topResult->fetch_assoc()) {
        $monthlyTopRows[] = $row;
    }
    $topMonthlyItems->close();
}

$reportLines = [];
foreach ($analysisNotes as $note) {
    $reportLines[] = '- ' . $note;
}
if (count($monthlyTopRows) > 0) {
    $reportLines[] = '- Top dishes for ' . $monthLabel . ':';
    foreach ($monthlyTopRows as $row) {
        $reportLines[] = '  * ' . (string)$row['item_name'] . ' -> ' . (int)$row['total_qty'] . ' sold';
    }
}

$reportBody = implode("\n", $reportLines);
$reportMonthDate = date('Y-m-01');
$todayDay = (int)date('d');
$autoEligible = ($todayDay >= 30);
$manualRequested = isset($_GET['generate_predictive']) && $_GET['generate_predictive'] === '1';
$didGenerateThisRequest = false;
$generationNotice = '';

if ($autoEligible || $manualRequested) {
    $checkStmt = $conn->prepare('SELECT id FROM predictive_reports WHERE report_month = ? LIMIT 1');
    $checkStmt->bind_param('s', $reportMonthDate);
    $checkStmt->execute();
    $alreadyGenerated = $checkStmt->get_result()->num_rows > 0;
    $checkStmt->close();

    if (!$alreadyGenerated) {
        $mode = $manualRequested ? 'manual' : 'auto';
        $insertStmt = $conn->prepare('INSERT INTO predictive_reports (report_month, report_label, report_body, generation_mode) VALUES (?, ?, ?, ?)');
        $insertStmt->bind_param('ssss', $reportMonthDate, $monthLabel, $reportBody, $mode);
        if ($insertStmt->execute()) {
            $didGenerateThisRequest = true;
            $generationNotice = 'Predictive report generated for ' . $monthLabel . ' (' . $mode . ').';
        } else {
            $generationNotice = 'Predictive report generation failed: ' . $insertStmt->error;
        }
        $insertStmt->close();
    } else {
        $generationNotice = 'Predictive report for ' . $monthLabel . ' is already generated.';
    }
}

$latestPredictive = null;
$latestRs = $conn->query("SELECT report_month, report_label, report_body, generation_mode, generated_at
    FROM predictive_reports
    ORDER BY report_month DESC
    LIMIT 1");
if ($latestRs && $latestRs->num_rows > 0) {
    $latestPredictive = $latestRs->fetch_assoc();
}

$thisMonthGenerated = false;
$thisMonthMode = '';
$thisMonthGeneratedAt = '';
$thisMonthStmt = $conn->prepare('SELECT generation_mode, generated_at FROM predictive_reports WHERE report_month = ? LIMIT 1');
$thisMonthStmt->bind_param('s', $reportMonthDate);
$thisMonthStmt->execute();
$thisMonthRow = $thisMonthStmt->get_result()->fetch_assoc();
if ($thisMonthRow) {
    $thisMonthGenerated = true;
    $thisMonthMode = (string)$thisMonthRow['generation_mode'];
    $thisMonthGeneratedAt = (string)$thisMonthRow['generated_at'];
}
$thisMonthStmt->close();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Inventory Reports - FoodFlow</title><link rel="stylesheet" href="admin_styles.css"></head>
<body>
<nav class="navbar"><div class="navbar-brand">FoodFlow Reports</div><div class="navbar-user"><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div></nav>
<nav class="admin-nav"><ul class="admin-nav-links"><li><a href="admin_dashboard.php">Dashboard</a></li><?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?><li><a href="manage_users.php">Manage Users</a></li><li><a href="system_audit.php">System Audit</a></li><?php endif; ?></ul></nav>
<div class="container">
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?php echo $summary['ingredients']; ?></div><div class="stat-label">Ingredients</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $summary['low_stock']; ?></div><div class="stat-label">Low Stock</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $summary['orders']; ?></div><div class="stat-label">Total Orders</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $summary['served']; ?></div><div class="stat-label">Served Orders</div></div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Orders by Month</h3>
        <?php if ($monthly->num_rows > 0): ?>
            <ul class="stats-list">
                <?php while($m = $monthly->fetch_assoc()): ?>
                    <li class="stats-item" style="display:block;">
                        <div style="font-weight:600;"><?php echo htmlspecialchars((string)$m['month_label']); ?></div>
                        <div style="color:#666;font-size:13px;margin-top:2px;"><?php echo (int)$m['total']; ?> orders</div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No order records available yet.</p>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Low-Stock Detail</h3>
        <?php if ($low->num_rows > 0): ?>
            <ul class="order-list">
                <?php while($i = $low->fetch_assoc()): ?>
                    <li class="order-item">
                        <div class="item-primary"><?php echo htmlspecialchars($i['name']); ?></div>
                        <div class="order-details">Stock <?php echo number_format((float)$i['current_stock'],2); ?> <?php echo htmlspecialchars($i['unit']); ?> / Reorder <?php echo number_format((float)$i['reorder_level'],2); ?></div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No low-stock ingredients found.</p>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Wastage Summary</h3>
        <p>Total logged wastage quantity: <strong><?php echo number_format((float)$summary['wastage_qty'], 2); ?></strong></p>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Predictive Analysis (<?php echo htmlspecialchars($monthLabel); ?>)</h3>
        <?php foreach ($analysisNotes as $note): ?>
            <p style="margin-bottom:8px;"><?php echo htmlspecialchars($note); ?></p>
        <?php endforeach; ?>

        <?php if ($generationNotice !== ''): ?>
            <p style="margin-top:8px;"><strong><?php echo htmlspecialchars($generationNotice); ?></strong></p>
        <?php endif; ?>

    </div>
</div>
</body></html>
