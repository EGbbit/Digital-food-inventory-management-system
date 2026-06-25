<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

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
