<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS chef_stock_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    chef_id INT NOT NULL,
    observed_stock DECIMAL(10,2) NOT NULL,
    reorder_level_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0,
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

function output_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit();
}

$summary = [
    'ingredients' => (int)$conn->query("SELECT COUNT(*) AS c FROM ingredients")->fetch_assoc()['c'],
    'low_stock' => (int)$conn->query("SELECT COUNT(*) AS c FROM ingredients WHERE current_stock <= reorder_level")->fetch_assoc()['c'],
    'orders' => (int)$conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'],
    'served' => (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='served'")->fetch_assoc()['c'],
    'wastage_qty' => (float)$conn->query("SELECT IFNULL(SUM(quantity),0) AS t FROM wastage_logs")->fetch_assoc()['t']
];

$servedRate = $summary['orders'] > 0 ? round(($summary['served'] / $summary['orders']) * 100, 2) : 0.00;

$monthlyRows = [];
$monthlyRs = $conn->query("SELECT
    DATE_FORMAT(created_at, '%M %Y') AS month_label,
    DATE_FORMAT(created_at, '%Y-%m') AS month_sort,
    COUNT(*) AS total
    FROM orders
    GROUP BY month_sort, month_label
    ORDER BY month_sort DESC
    LIMIT 6");
while ($row = $monthlyRs->fetch_assoc()) {
    $monthlyRows[] = $row;
}
$monthlyRows = array_reverse($monthlyRows);

$topItemsRows = [];
$topRs = $conn->query("SELECT mi.name, IFNULL(SUM(oi.quantity), 0) AS qty
    FROM menu_items mi
    LEFT JOIN order_items oi ON oi.menu_item_id = mi.id
    GROUP BY mi.id, mi.name
    ORDER BY qty DESC
    LIMIT 7");
while ($row = $topRs->fetch_assoc()) {
    $topItemsRows[] = $row;
}

$statusRows = [];
$statusRs = $conn->query("SELECT status, COUNT(*) AS total
    FROM orders
    GROUP BY status
    ORDER BY total DESC");
while ($row = $statusRs->fetch_assoc()) {
    $statusRows[] = $row;
}

$lowRows = [];
$lowRs = $conn->query("SELECT name, current_stock, reorder_level, unit
    FROM ingredients
    WHERE current_stock <= reorder_level
    ORDER BY current_stock ASC");
while ($row = $lowRs->fetch_assoc()) {
    $lowRows[] = $row;
}

$chefNotesRows = [];
$chefNotesRs = $conn->query("SELECT
    n.id,
    i.name AS ingredient_name,
    i.unit,
    u.name AS chef_name,
    n.observed_stock,
    n.reorder_level_snapshot,
    n.expected_expiry_date,
    n.shelf_life_days,
    n.urgency,
    n.comment,
    n.is_acknowledged,
    n.created_at,
    DATEDIFF(n.expected_expiry_date, CURDATE()) AS days_to_expiry
    FROM chef_stock_notes n
    JOIN ingredients i ON i.id = n.ingredient_id
    JOIN users u ON u.id = n.chef_id
    ORDER BY n.created_at DESC
    LIMIT 25");
while ($chefNotesRs && $row = $chefNotesRs->fetch_assoc()) {
    $chefNotesRows[] = $row;
}

$latestPredictive = null;
$predictiveRs = $conn->query("SELECT report_label, report_body, generation_mode, generated_at
    FROM predictive_reports
    ORDER BY generated_at DESC
    LIMIT 1");
if ($predictiveRs && $predictiveRs->num_rows > 0) {
    $latestPredictive = $predictiveRs->fetch_assoc();
}

if (isset($_GET['export'])) {
    $stamp = date('Ymd_His');
    $exportType = $_GET['export'];

    if ($exportType === 'overview') {
        $rows = [
            ['Total Ingredients', $summary['ingredients']],
            ['Low Stock Ingredients', $summary['low_stock']],
            ['Total Orders', $summary['orders']],
            ['Served Orders', $summary['served']],
            ['Served Rate (%)', $servedRate],
            ['Wastage Quantity', number_format($summary['wastage_qty'], 2, '.', '')]
        ];
        output_csv('manager_overview_' . $stamp . '.csv', ['Metric', 'Value'], $rows);
    }

    if ($exportType === 'monthly') {
        $rows = [];
        foreach ($monthlyRows as $row) {
            $rows[] = [$row['month_label'], (int)$row['total']];
        }
        output_csv('manager_monthly_orders_' . $stamp . '.csv', ['Month', 'Orders'], $rows);
    }

    if ($exportType === 'low-stock') {
        $rows = [];
        foreach ($lowRows as $row) {
            $rows[] = [
                $row['name'],
                number_format((float)$row['current_stock'], 2, '.', ''),
                number_format((float)$row['reorder_level'], 2, '.', ''),
                $row['unit']
            ];
        }
        output_csv('manager_low_stock_' . $stamp . '.csv', ['Ingredient', 'Current Stock', 'Reorder Level', 'Unit'], $rows);
    }

    if ($exportType === 'predictive') {
        $rows = [];
        if ($latestPredictive) {
            $rows[] = [
                $latestPredictive['report_label'],
                $latestPredictive['generation_mode'],
                date('Y-m-d H:i', strtotime((string)$latestPredictive['generated_at'])),
                preg_replace('/\s+/', ' ', (string)$latestPredictive['report_body'])
            ];
        }
        output_csv('manager_predictive_' . $stamp . '.csv', ['Label', 'Mode', 'Generated At', 'Body'], $rows);
    }

    if ($exportType === 'chef-notes') {
        $rows = [];
        foreach ($chefNotesRows as $row) {
            $rows[] = [
                $row['ingredient_name'],
                $row['chef_name'],
                number_format((float)$row['observed_stock'], 2, '.', ''),
                number_format((float)$row['reorder_level_snapshot'], 2, '.', ''),
                $row['unit'],
                (string)$row['urgency'],
                ($row['expected_expiry_date'] ?? ''),
                ($row['shelf_life_days'] ?? ''),
                ($row['days_to_expiry'] ?? ''),
                ((int)$row['is_acknowledged'] === 1 ? 'acknowledged' : 'pending'),
                preg_replace('/\s+/', ' ', (string)$row['comment']),
                date('Y-m-d H:i', strtotime((string)$row['created_at']))
            ];
        }
        output_csv('manager_chef_stock_notes_' . $stamp . '.csv', ['Ingredient', 'Chef', 'Observed Stock', 'Reorder Snapshot', 'Unit', 'Urgency', 'Expected Expiry', 'Shelf Life Days', 'Days To Expiry', 'Review Status', 'Comment', 'Created At'], $rows);
    }
}

$monthlyLabels = array_map(static function ($row) {
    return (string)$row['month_label'];
}, $monthlyRows);
$monthlyValues = array_map(static function ($row) {
    return (int)$row['total'];
}, $monthlyRows);

$statusLabels = array_map(static function ($row) {
    return ucfirst((string)$row['status']);
}, $statusRows);
$statusValues = array_map(static function ($row) {
    return (int)$row['total'];
}, $statusRows);

$topItemLabels = array_map(static function ($row) {
    return (string)$row['name'];
}, $topItemsRows);
$topItemValues = array_map(static function ($row) {
    return (int)$row['qty'];
}, $topItemsRows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Reports - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body class="dashboard-photo dashboard-manager">
    <nav class="navbar">
        <div class="navbar-brand">FoodFlow Manager Reports</div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <nav class="admin-nav">
        <ul class="admin-nav-links">
            <li><a href="manager_dashboard.php">Dashboard</a></li>
            <li><a href="manager_controls.php">Thresholds & Approvals</a></li>
            <li><a href="open_menu.php">Open Food Menu</a></li>
            <li><a href="manager_reports.php" class="active">Reports</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="role-welcome">
            <h1>Manager Reporting Center</h1>
            <p>All business reports are managed here with export-ready analytics and visual charts.</p>
        </div>

        <div class="toolbar" style="margin-bottom:14px;">
            <a href="manager_reports.php?export=overview" class="btn btn-primary">Export Overview CSV</a>
            <a href="manager_reports.php?export=monthly" class="btn btn-success">Export Monthly Orders CSV</a>
            <a href="manager_reports.php?export=low-stock" class="btn btn-warning">Export Low-Stock CSV</a>
            <a href="manager_reports.php?export=predictive" class="btn btn-primary">Export Predictive CSV</a>
            <a href="manager_reports.php?export=chef-notes" class="btn btn-success">Export Chef Stock Notes CSV</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $summary['ingredients']; ?></div><div class="stat-label">Ingredients</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $summary['low_stock']; ?></div><div class="stat-label">Low Stock</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $summary['orders']; ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $summary['served']; ?></div><div class="stat-label">Served Orders</div></div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3>Business Logic Snapshot</h3>
            <p>Served rate: <strong><?php echo number_format($servedRate, 2); ?>%</strong></p>
            <p>Total wastage quantity: <strong><?php echo number_format($summary['wastage_qty'], 2); ?></strong></p>
            <p>Risk index (low-stock ingredients): <strong><?php echo (int)$summary['low_stock']; ?></strong></p>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3>Visual Reports</h3>
            <div class="chart-grid">
                <div class="chart-card">
                    <h4>Orders Trend By Month</h4>
                    <div class="chart-wrap">
                        <canvas id="monthlyOrdersChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h4>Order Status Distribution</h4>
                    <div class="chart-wrap">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="chart-card chart-card--wide">
                    <h4>Top Menu Items</h4>
                    <div class="chart-wrap">
                        <canvas id="topItemsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3>Low-Stock Detail</h3>
            <?php if (count($lowRows) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Ingredient</th><th>Current</th><th>Reorder</th><th>Unit</th></tr></thead>
                        <tbody>
                        <?php foreach ($lowRows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$row['name']); ?></td>
                                <td><?php echo number_format((float)$row['current_stock'], 2); ?></td>
                                <td><?php echo number_format((float)$row['reorder_level'], 2); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['unit']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No low-stock ingredients currently.</p>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3>Chef Low-Stock and Shelf-Life Notes</h3>
            <p style="margin-bottom:10px;color:#555;">Use this feed to tune reorder thresholds in manager controls and prioritize restock and expiry-risk actions.</p>
            <?php if (count($chefNotesRows) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Time</th><th>Ingredient</th><th>Chef</th><th>Observed</th><th>Reorder Snapshot</th><th>Urgency</th><th>Shelf-Life</th><th>Comment</th><th>Review</th></tr></thead>
                        <tbody>
                        <?php foreach ($chefNotesRows as $row): ?>
                            <?php
                                $daysToExpiry = $row['days_to_expiry'];
                                $shelfLifeText = 'No expiry date';
                                if (!empty($row['expected_expiry_date'])) {
                                    if ($daysToExpiry !== null && (int)$daysToExpiry < 0) {
                                        $shelfLifeText = 'Expired ' . abs((int)$daysToExpiry) . ' day(s) ago';
                                    } elseif ($daysToExpiry !== null) {
                                        $shelfLifeText = 'Expires in ' . (int)$daysToExpiry . ' day(s)';
                                    } else {
                                        $shelfLifeText = (string)$row['expected_expiry_date'];
                                    }
                                }
                                if (!empty($row['shelf_life_days'])) {
                                    $shelfLifeText .= ' | Shelf-life ' . (int)$row['shelf_life_days'] . ' day(s)';
                                }
                            ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime((string)$row['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['ingredient_name']); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['chef_name']); ?></td>
                                <td><?php echo number_format((float)$row['observed_stock'], 2); ?> <?php echo htmlspecialchars((string)$row['unit']); ?></td>
                                <td><?php echo number_format((float)$row['reorder_level_snapshot'], 2); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper((string)$row['urgency'])); ?></td>
                                <td><?php echo htmlspecialchars($shelfLifeText); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['comment']); ?></td>
                                <td><?php echo ((int)$row['is_acknowledged'] === 1) ? 'Acknowledged' : 'Pending review'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No chef stock notes yet.</p>
            <?php endif; ?>
        </div>

        <div class="card" style="margin-top:16px;">
            <h3>Latest Predictive Report</h3>
            <?php if ($latestPredictive): ?>
                <p><strong><?php echo htmlspecialchars((string)$latestPredictive['report_label']); ?></strong> (<?php echo htmlspecialchars((string)$latestPredictive['generation_mode']); ?>)</p>
                <p style="color:#666;font-size:13px;">Generated: <?php echo date('Y-m-d H:i', strtotime((string)$latestPredictive['generated_at'])); ?></p>
                <pre style="white-space:pre-wrap;background:#fafafa;border:1px solid #eee;padding:10px;border-radius:8px;margin-top:8px;"><?php echo htmlspecialchars((string)$latestPredictive['report_body']); ?></pre>
            <?php else: ?>
                <p>No predictive report generated yet. Use manager controls to generate one.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const refreshMs = 20000;
            setInterval(function () {
                const active = document.activeElement;
                const isEditing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
                if (!document.hidden && !isEditing) {
                    window.location.reload();
                }
            }, refreshMs);
        })();
    </script>
    <script>
        const monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
        const monthlyValues = <?php echo json_encode($monthlyValues); ?>;
        const statusLabels = <?php echo json_encode($statusLabels); ?>;
        const statusValues = <?php echo json_encode($statusValues); ?>;
        const topItemLabels = <?php echo json_encode($topItemLabels); ?>;
        const topItemValues = <?php echo json_encode($topItemValues); ?>;

        new Chart(document.getElementById('monthlyOrdersChart'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Orders',
                    data: monthlyValues,
                    borderColor: '#ff9a3d',
                    backgroundColor: 'rgba(255, 154, 61, 0.25)',
                    fill: true,
                    tension: 0.25
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });

        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: ['#4facfe', '#ff9a3d', '#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        new Chart(document.getElementById('topItemsChart'), {
            type: 'bar',
            data: {
                labels: topItemLabels,
                datasets: [{
                    label: 'Sold Qty',
                    data: topItemValues,
                    backgroundColor: '#ffd93d'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
