<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$report_message = '';
$manager_id = (int)($_SESSION['user_id'] ?? 0);

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

$suggestedCol = $conn->query("SHOW COLUMNS FROM chef_stock_notes LIKE 'suggested_restock_amount'");
if ($suggestedCol && $suggestedCol->num_rows === 0) {
    $conn->query("ALTER TABLE chef_stock_notes ADD COLUMN suggested_restock_amount DECIMAL(10,2) NULL AFTER reorder_level_snapshot");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_predictive_report'])) {
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

    $analysisLines[] = '';
    $analysisLines[] = 'Shelf-life and Expiry Monitoring (from chef notes):';

    $riskyIngredientIds = [];
    $expiryStmt = $conn->prepare("SELECT
        n.ingredient_id,
        i.name AS ingredient_name,
        n.expected_expiry_date,
        n.shelf_life_days,
        COALESCE(
            n.expected_expiry_date,
            CASE
                WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                ELSE NULL
            END
        ) AS effective_expiry_date,
        DATEDIFF(
            COALESCE(
                n.expected_expiry_date,
                CASE
                    WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                    ELSE NULL
                END
            ),
            CURDATE()
        ) AS days_to_expiry,
        n.urgency,
        n.observed_stock,
        n.reorder_level_snapshot,
        n.suggested_restock_amount,
        n.comment,
        n.created_at
        FROM chef_stock_notes n
        JOIN ingredients i ON i.id = n.ingredient_id
        WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND (
                (
                    COALESCE(
                        n.expected_expiry_date,
                        CASE
                            WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                            ELSE NULL
                        END
                    ) IS NOT NULL
                    AND COALESCE(
                        n.expected_expiry_date,
                        CASE
                            WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                            ELSE NULL
                        END
                    ) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                )
                OR n.urgency = 'urgent'
              )
        ORDER BY
          CASE
            WHEN COALESCE(
                n.expected_expiry_date,
                CASE
                    WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                    ELSE NULL
                END
            ) IS NULL THEN 2
            WHEN COALESCE(
                n.expected_expiry_date,
                CASE
                    WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                    ELSE NULL
                END
            ) < CURDATE() THEN 0
            ELSE 1
          END,
          COALESCE(
            n.expected_expiry_date,
            CASE
                WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                ELSE NULL
            END
          ) ASC,
          n.created_at DESC
        LIMIT 10");

    if ($expiryStmt) {
        $expiryStmt->execute();
        $expiryRs = $expiryStmt->get_result();

        if ($expiryRs && $expiryRs->num_rows > 0) {
            while ($row = $expiryRs->fetch_assoc()) {
                $ingredientId = (int)$row['ingredient_id'];
                $ingredientName = (string)$row['ingredient_name'];
                $effectiveExpiryDate = $row['effective_expiry_date'];
                $urgency = strtoupper((string)$row['urgency']);
                $observed = number_format((float)$row['observed_stock'], 2);
                $reorder = number_format((float)$row['reorder_level_snapshot'], 2);
                $suggested = number_format((float)($row['suggested_restock_amount'] ?? 0), 2);
                $shortComment = trim((string)$row['comment']);
                if (strlen($shortComment) > 70) {
                    $shortComment = substr($shortComment, 0, 70) . '...';
                }

                $expiryText = 'No expiry date provided';
                if ($row['days_to_expiry'] !== null && !empty($effectiveExpiryDate)) {
                    $days = (int)$row['days_to_expiry'];
                    if ($days < 0) {
                        $expiryText = 'Expired ' . abs($days) . ' day(s) ago';
                    } elseif ($days === 0) {
                        $expiryText = 'Expires today';
                    } else {
                        $expiryText = 'Expires in ' . $days . ' day(s)';
                    }
                    if (empty($row['expected_expiry_date']) && !empty($row['shelf_life_days'])) {
                        $expiryText .= ' (computed from shelf life)';
                    }
                }

                $analysisLines[] = '- ' . $ingredientName
                    . ' [' . $urgency . '] | ' . $expiryText
                    . ' | observed ' . $observed
                    . ' vs reorder ' . $reorder
                    . ' | suggested restock ' . $suggested
                    . ($shortComment !== '' ? ' | note: ' . $shortComment : '');

                $riskyIngredientIds[$ingredientId] = true;
            }
        } else {
            $analysisLines[] = '- No near-expiry or urgent chef-note risks detected in the last 30 days.';
        }

        $expiryStmt->close();
    } else {
        $analysisLines[] = '- Could not evaluate shelf-life risk because chef notes are unavailable.';
    }

    if (count($riskyIngredientIds) > 0) {
        $analysisLines[] = 'Likely affected food items (based on recipes):';
        $ids = array_keys($riskyIngredientIds);
        $idList = implode(',', array_map('intval', $ids));
        $menuRiskRs = $conn->query("SELECT DISTINCT mi.name AS menu_name
            FROM recipe_ingredients ri
            JOIN menu_items mi ON mi.id = ri.menu_item_id
            WHERE ri.ingredient_id IN (" . $idList . ")
            ORDER BY mi.name ASC
            LIMIT 10");

        if ($menuRiskRs && $menuRiskRs->num_rows > 0) {
            while ($mr = $menuRiskRs->fetch_assoc()) {
                $analysisLines[] = '- ' . (string)$mr['menu_name'];
            }
        } else {
            $analysisLines[] = '- No recipe-linked menu items found for the current risky ingredients.';
        }
    }

    $analysisLines[] = 'Recommendation: prioritize procurement or temporary menu substitution for urgent/near-expiry ingredients.';

    $reportBody = implode("\n", $analysisLines);

    $upsert = $conn->prepare("INSERT INTO predictive_reports (report_month, report_label, report_body, generation_mode)
        VALUES (?, ?, ?, 'manual')
        ON DUPLICATE KEY UPDATE report_label = VALUES(report_label), report_body = VALUES(report_body), generation_mode = 'manual', generated_at = CURRENT_TIMESTAMP");
    $upsert->bind_param('sss', $reportMonthDate, $monthLabel, $reportBody);
    if ($upsert->execute()) {
        $report_message = 'Predictive report generated for ' . $monthLabel . '.';
    } else {
        $report_message = 'Failed to generate predictive report: ' . $upsert->error;
    }
    $upsert->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_chef_note_ack'])) {
    $note_id = (int)($_POST['chef_note_id'] ?? 0);
    $ack_action = (string)($_POST['ack_action'] ?? '');

    if ($note_id > 0 && in_array($ack_action, ['acknowledge', 'reopen'], true)) {
        if ($ack_action === 'acknowledge') {
            $ackStmt = $conn->prepare('UPDATE chef_stock_notes SET is_acknowledged = 1, acknowledged_by = ?, acknowledged_at = NOW() WHERE id = ?');
            if ($ackStmt) {
                $ackStmt->bind_param('ii', $manager_id, $note_id);
                $ackStmt->execute();
                $report_message = 'Chef note marked as acknowledged.';
            }
        } else {
            $ackStmt = $conn->prepare('UPDATE chef_stock_notes SET is_acknowledged = 0, acknowledged_by = NULL, acknowledged_at = NULL WHERE id = ?');
            if ($ackStmt) {
                $ackStmt->bind_param('i', $note_id);
                $ackStmt->execute();
                $report_message = 'Chef note moved back to pending review.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient_quick'])) {
    $ingredientName = trim((string)($_POST['ingredient_name'] ?? ''));
    $ingredientUnit = trim((string)($_POST['ingredient_unit'] ?? ''));
    $ingredientAddAmount = (float)($_POST['ingredient_add_amount'] ?? 0);

    if ($ingredientName === '' || $ingredientAddAmount <= 0) {
        $report_message = 'Please provide ingredient name and amount to add.';
    } else {
        $dupStmt = $conn->prepare('SELECT id, unit FROM ingredients WHERE LOWER(name) = LOWER(?) LIMIT 1');
        if ($dupStmt) {
            $dupStmt->bind_param('s', $ingredientName);
            $dupStmt->execute();
            $exists = $dupStmt->get_result()->fetch_assoc();
            $dupStmt->close();

            if ($exists) {
                $ingredientId = (int)$exists['id'];
                $updateStmt = $conn->prepare('UPDATE ingredients SET current_stock = current_stock + ? WHERE id = ?');
                if ($updateStmt) {
                    $updateStmt->bind_param('di', $ingredientAddAmount, $ingredientId);
                    if ($updateStmt->execute()) {
                        $report_message = 'Ingredient stock updated from chef-note quick actions.';
                    } else {
                        $report_message = 'Failed to update ingredient stock.';
                    }
                    $updateStmt->close();
                }
            } else {
                if ($ingredientUnit === '') {
                    $report_message = 'Unit is required when adding a new ingredient.';
                } else {
                    $addStmt = $conn->prepare('INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    if ($addStmt) {
                        $ingredientCategory = 'General';
                        $ingredientReorder = 0;
                        $ingredientCost = 0.00;
                        $ingredientActive = 1;
                        $addStmt->bind_param('sssdddi', $ingredientName, $ingredientCategory, $ingredientUnit, $ingredientAddAmount, $ingredientReorder, $ingredientCost, $ingredientActive);
                        if ($addStmt->execute()) {
                            $report_message = 'Ingredient added successfully from chef-note quick actions.';
                        } else {
                            $report_message = 'Failed to add ingredient: ' . $addStmt->error;
                        }
                        $addStmt->close();
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_menu_item_quick'])) {
    $name = trim((string)($_POST['menu_name'] ?? ''));
    $category = trim((string)($_POST['menu_category'] ?? ''));
    $sellingPrice = (float)($_POST['selling_price'] ?? 0);
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '' || $sellingPrice <= 0) {
        $report_message = 'Please provide a valid food item name and price.';
    } else {
        if ($category === '') {
            $category = 'General';
        }

        $findStmt = $conn->prepare('SELECT id FROM menu_items WHERE LOWER(name) = LOWER(?) AND LOWER(COALESCE(category, "")) = LOWER(?) LIMIT 1');
        if ($findStmt) {
            $findStmt->bind_param('ss', $name, $category);
            $findStmt->execute();
            $existing = $findStmt->get_result()->fetch_assoc();
            $findStmt->close();

            if ($existing) {
                $existingId = (int)$existing['id'];
                $updateStmt = $conn->prepare('UPDATE menu_items SET selling_price = ?, is_available = ? WHERE id = ?');
                if ($updateStmt) {
                    $updateStmt->bind_param('dii', $sellingPrice, $isAvailable, $existingId);
                    if ($updateStmt->execute()) {
                        $report_message = 'Food item already existed; price/status updated from quick actions.';
                    } else {
                        $report_message = 'Failed to update existing food item.';
                    }
                    $updateStmt->close();
                }
            } else {
                $insertStmt = $conn->prepare('INSERT INTO menu_items (name, category, selling_price, is_available) VALUES (?, ?, ?, ?)');
                if ($insertStmt) {
                    $insertStmt->bind_param('ssdi', $name, $category, $sellingPrice, $isAvailable);
                    if ($insertStmt->execute()) {
                        $report_message = 'Food item added successfully from chef-note quick actions.';
                    } else {
                        $report_message = 'Failed to add food item.';
                    }
                    $insertStmt->close();
                }
            }
        }
    }
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

$chefNotesRows = [];
$chefNotesRs = $conn->query("SELECT
    n.id,
    i.name AS ingredient_name,
    i.unit,
    u.name AS chef_name,
    ack.name AS ack_manager_name,
    n.observed_stock,
    n.reorder_level_snapshot,
    n.suggested_restock_amount,
    n.expected_expiry_date,
    n.shelf_life_days,
    COALESCE(
        n.expected_expiry_date,
        CASE
            WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
            ELSE NULL
        END
    ) AS effective_expiry_date,
    CASE
        WHEN n.expected_expiry_date IS NOT NULL THEN 'expected_date'
        WHEN n.shelf_life_days IS NOT NULL THEN 'shelf_life_from_note_date'
        ELSE 'none'
    END AS expiry_basis,
    n.urgency,
    n.comment,
    n.is_acknowledged,
    n.acknowledged_at,
    n.created_at,
    DATEDIFF(
        COALESCE(
            n.expected_expiry_date,
            CASE
                WHEN n.shelf_life_days IS NOT NULL THEN DATE_ADD(DATE(n.created_at), INTERVAL n.shelf_life_days DAY)
                ELSE NULL
            END
        ),
        CURDATE()
    ) AS days_to_expiry
    FROM chef_stock_notes n
    JOIN ingredients i ON i.id = n.ingredient_id
    JOIN users u ON u.id = n.chef_id
    LEFT JOIN users ack ON ack.id = n.acknowledged_by
    ORDER BY n.created_at DESC
    LIMIT 25");
while ($chefNotesRs && $row = $chefNotesRs->fetch_assoc()) {
    $chefNotesRows[] = $row;
}

$chefIngredientHints = [];
foreach ($chefNotesRows as $noteRow) {
    $ingredientNameHint = trim((string)($noteRow['ingredient_name'] ?? ''));
    if ($ingredientNameHint === '') {
        continue;
    }

    $key = strtolower($ingredientNameHint);
    if (isset($chefIngredientHints[$key])) {
        continue;
    }

    $chefIngredientHints[$key] = [
        'name' => $ingredientNameHint,
        'unit' => (string)($noteRow['unit'] ?? ''),
        'reorder' => (float)($noteRow['reorder_level_snapshot'] ?? 0),
        'suggested' => (float)($noteRow['suggested_restock_amount'] ?? 0),
    ];
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
                number_format((float)($row['suggested_restock_amount'] ?? 0), 2, '.', ''),
                $row['unit'],
                (string)$row['urgency'],
                ($row['expected_expiry_date'] ?? ''),
                ($row['shelf_life_days'] ?? ''),
                ($row['effective_expiry_date'] ?? ''),
                ($row['expiry_basis'] ?? ''),
                ($row['days_to_expiry'] ?? ''),
                ((int)$row['is_acknowledged'] === 1 ? 'acknowledged' : 'pending'),
                ($row['ack_manager_name'] ?? ''),
                (!empty($row['acknowledged_at']) ? date('Y-m-d H:i', strtotime((string)$row['acknowledged_at'])) : ''),
                preg_replace('/\s+/', ' ', (string)$row['comment']),
                date('Y-m-d H:i', strtotime((string)$row['created_at']))
            ];
        }
        output_csv('manager_chef_stock_notes_' . $stamp . '.csv', ['Ingredient', 'Chef', 'Observed Stock', 'Suggested Restock', 'Unit', 'Urgency', 'Expected Expiry', 'Shelf Life Days', 'Effective Expiry Date', 'Expiry Basis', 'Days To Expiry', 'Review Status', 'Acknowledged By', 'Acknowledged At', 'Comment', 'Created At'], $rows);
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
        <?php if ($report_message !== ''): ?>
            <p class="success"><?php echo htmlspecialchars($report_message); ?></p>
        <?php endif; ?>

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
            <h3>Latest Predictive Report</h3>
            <form method="POST" style="margin-bottom:10px;">
                <button type="submit" name="generate_predictive_report" class="btn btn-primary">Generate / Refresh Predictive Report</button>
            </form>
            <?php if ($latestPredictive): ?>
                <p><strong><?php echo htmlspecialchars((string)$latestPredictive['report_label']); ?></strong> (<?php echo htmlspecialchars((string)$latestPredictive['generation_mode']); ?>)</p>
                <p style="color:#666;font-size:13px;">Generated: <?php echo date('Y-m-d H:i', strtotime((string)$latestPredictive['generated_at'])); ?></p>
                <pre style="white-space:pre-wrap;background:#fafafa;border:1px solid #eee;padding:10px;border-radius:8px;margin-top:8px;"><?php echo htmlspecialchars((string)$latestPredictive['report_body']); ?></pre>
            <?php else: ?>
                <p>No predictive report generated yet. Use the button above to generate one.</p>
            <?php endif; ?>
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

            <div style="display:grid;grid-template-columns:repeat(2,minmax(280px,1fr));gap:12px;margin-bottom:14px;">
                <div style="background:#f7fbff;border:1px solid #dce9f7;border-radius:10px;padding:10px;">
                    <h4 style="margin:0 0 8px 0;color:#2f646e;">Quick Add Ingredient</h4>
                    <p style="margin:0 0 8px 0;font-size:12px;color:#4f5f69;">Enter ingredient and amount to add. Unit auto-reflects from chef notes when available.</p>
                    <form method="POST" style="display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:8px;align-items:end;">
                        <input id="quick-ingredient-name" type="text" name="ingredient_name" placeholder="Ingredient name" list="chef-ingredient-hints" required>
                        <datalist id="chef-ingredient-hints">
                            <?php foreach ($chefIngredientHints as $hint): ?>
                                <option value="<?php echo htmlspecialchars((string)$hint['name']); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <input id="quick-ingredient-amount" type="number" step="0.01" min="0.01" name="ingredient_add_amount" placeholder="Amount to add" required>
                        <input id="quick-ingredient-unit" type="text" name="ingredient_unit" placeholder="Unit (auto from chef note)" required>
                        <button type="submit" name="add_ingredient_quick" class="btn btn-success">Add Ingredient</button>
                    </form>
                </div>

                <div style="background:#fdf8ef;border:1px solid #eadcc1;border-radius:10px;padding:10px;">
                    <h4 style="margin:0 0 8px 0;color:#8a6727;">Quick Add Food Item</h4>
                    <form method="POST" style="display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:8px;align-items:end;">
                        <input type="text" name="menu_name" placeholder="Food item name" required>
                        <input type="text" name="menu_category" placeholder="Category">
                        <input type="number" step="0.01" min="0.01" name="selling_price" placeholder="Price (Kshs.)" required>
                        <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_available" checked> Available</label>
                        <button type="submit" name="add_menu_item_quick" class="btn btn-primary" style="grid-column: span 2;">Add Food Item</button>
                    </form>
                </div>
            </div>

            <?php if (count($chefNotesRows) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Time</th><th>Ingredient</th><th>Chef</th><th>Observed</th><th>Suggested Restock</th><th>Urgency</th><th>Shelf-Life</th><th>Comment</th><th>Review</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($chefNotesRows as $row): ?>
                            <?php
                                $daysToExpiry = $row['days_to_expiry'];
                                $shelfLifeText = 'No expiry data';
                                if (!empty($row['effective_expiry_date']) && $daysToExpiry !== null) {
                                    if ($daysToExpiry !== null && (int)$daysToExpiry < 0) {
                                        $shelfLifeText = 'Expired ' . abs((int)$daysToExpiry) . ' day(s) ago';
                                    } elseif ($daysToExpiry !== null) {
                                        $shelfLifeText = 'Expires in ' . (int)$daysToExpiry . ' day(s)';
                                    } else {
                                        $shelfLifeText = (string)$row['effective_expiry_date'];
                                    }
                                }
                                if (!empty($row['shelf_life_days'])) {
                                    $shelfLifeText .= ' | Shelf-life ' . (int)$row['shelf_life_days'] . ' day(s)';
                                }
                                if (($row['expiry_basis'] ?? '') === 'shelf_life_from_note_date') {
                                    $shelfLifeText .= ' | Computed from note date';
                                } elseif (($row['expiry_basis'] ?? '') === 'expected_date') {
                                    $shelfLifeText .= ' | From expected expiry date';
                                }
                            ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime((string)$row['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['ingredient_name']); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['chef_name']); ?></td>
                                <td><?php echo number_format((float)$row['observed_stock'], 2); ?> <?php echo htmlspecialchars((string)$row['unit']); ?></td>
                                <td><?php echo number_format((float)($row['suggested_restock_amount'] ?? 0), 2); ?> <?php echo htmlspecialchars((string)$row['unit']); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper((string)$row['urgency'])); ?></td>
                                <td><?php echo htmlspecialchars($shelfLifeText); ?></td>
                                <td><?php echo htmlspecialchars((string)$row['comment']); ?></td>
                                <td>
                                    <?php if ((int)$row['is_acknowledged'] === 1): ?>
                                        Acknowledged by <?php echo htmlspecialchars((string)($row['ack_manager_name'] ?? 'manager')); ?>
                                        <?php if (!empty($row['acknowledged_at'])): ?>
                                            <br><span style="font-size:12px;color:#555;">at <?php echo date('Y-m-d H:i', strtotime((string)$row['acknowledged_at'])); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Pending review
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="chef_note_id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="toggle_chef_note_ack" value="1">
                                        <?php if ((int)$row['is_acknowledged'] === 1): ?>
                                            <button type="submit" name="ack_action" value="reopen" class="btn btn-warning btn-sm">Reopen</button>
                                        <?php else: ?>
                                            <button type="submit" name="ack_action" value="acknowledge" class="btn btn-success btn-sm">Acknowledge</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No chef stock notes yet.</p>
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
        const chefIngredientHints = <?php echo json_encode($chefIngredientHints, JSON_UNESCAPED_SLASHES); ?>;

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

        (function () {
            const ingredientNameInput = document.getElementById('quick-ingredient-name');
            const amountInput = document.getElementById('quick-ingredient-amount');
            const unitInput = document.getElementById('quick-ingredient-unit');

            if (!ingredientNameInput || !unitInput) {
                return;
            }

            function normalize(value) {
                return (value || '').trim().toLowerCase();
            }

            function applyIngredientHint() {
                const key = normalize(ingredientNameInput.value);
                if (!key || !chefIngredientHints || !chefIngredientHints[key]) {
                    return;
                }

                const hint = chefIngredientHints[key];
                if (unitInput && hint.unit) {
                    unitInput.value = String(hint.unit);
                }
                if (amountInput && (!amountInput.value || Number(amountInput.value) <= 0) && Number(hint.suggested || 0) > 0) {
                    amountInput.value = Number(hint.suggested || 0).toFixed(2);
                }
            }

            ingredientNameInput.addEventListener('change', applyIngredientHint);
            ingredientNameInput.addEventListener('blur', applyIngredientHint);
            ingredientNameInput.addEventListener('input', function () {
                if (ingredientNameInput.value.trim().length > 2) {
                    applyIngredientHint();
                }
            });

            applyIngredientHint();
        })();
    </script>
</body>
</html>
