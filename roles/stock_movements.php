<?php
require_once __DIR__ . '/../core/auth.php';
require_login();

$role = (string)($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'manager'], true)) {
    redirect_by_role($role);
}

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
    $type = $_POST['movement_type'] ?? '';
    $quantity = (float)($_POST['quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $allowed = ['stock_in', 'usage', 'adjustment', 'wastage'];
    if ($ingredient_id > 0 && in_array($type, $allowed, true) && $quantity > 0) {
        $stmt = $conn->prepare('INSERT INTO stock_movements (ingredient_id, movement_type, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?)');
        $createdBy = (int)$_SESSION['user_id'];
        $stmt->bind_param('isdsi', $ingredient_id, $type, $quantity, $notes, $createdBy);

        if ($stmt->execute()) {
            if ($type === 'stock_in' || $type === 'adjustment') {
                $conn->query("UPDATE ingredients SET current_stock = current_stock + $quantity WHERE id = $ingredient_id");
            } else {
                $conn->query("UPDATE ingredients SET current_stock = GREATEST(0, current_stock - $quantity) WHERE id = $ingredient_id");
            }

            if ($type === 'wastage') {
                $reason = $notes !== '' ? $notes : 'Kitchen wastage';
                $conn->query("INSERT INTO wastage_logs (ingredient_id, quantity, reason, logged_by) VALUES ($ingredient_id, $quantity, '" . $conn->real_escape_string($reason) . "', $createdBy)");
            }
            $message = 'Stock movement recorded.';
        } else {
            $message = 'Failed to save movement: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = 'Please provide valid movement details.';
    }
}

$ingredients = $conn->query('SELECT id, name, unit, current_stock FROM ingredients ORDER BY name');
$history = $conn->query('SELECT sm.*, i.name AS ingredient_name, i.unit, u.name AS actor
    FROM stock_movements sm
    JOIN ingredients i ON sm.ingredient_id=i.id
    JOIN users u ON sm.created_by=u.id
    ORDER BY sm.created_at DESC
    LIMIT 20');
$isAdmin = $role === 'admin';
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Stock Movements - FoodFlow</title><link rel="stylesheet" href="roles_styles.css"></head>
<body class="dashboard-photo <?php echo $isAdmin ? 'dashboard-admin' : 'dashboard-manager'; ?>">
<nav class="navbar"><div class="navbar-brand">FoodFlow Inventory</div><div class="navbar-user"><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div></nav>
<nav class="admin-nav"><ul class="admin-nav-links"><?php if ($isAdmin): ?><li><a href="../admin/admin_dashboard.php">Dashboard</a></li><li><a href="../admin/manage_users.php">Manage Users</a></li><li><a href="../admin/system_audit.php">System Audit</a></li><?php else: ?><li><a href="manager_dashboard.php">Dashboard</a></li><li><a href="manager_controls.php">Thresholds &amp; Approvals</a></li><li><a href="manager_reports.php">Reports</a></li><?php endif; ?><li><a href="ingredients.php">Ingredients</a></li><li><a href="stock_movements.php" class="active">Stock Movements</a></li></ul></nav>
<div class="container">
    <div class="card">
        <h3> Log Stock Movement</h3>
        <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <form method="POST" style="display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:10px;">
            <select name="ingredient_id" required>
                <option value="">Select ingredient</option>
                <?php while($i = $ingredients->fetch_assoc()): ?>
                    <option value="<?php echo (int)$i['id']; ?>"><?php echo htmlspecialchars($i['name']); ?> (<?php echo number_format((float)$i['current_stock'],2) . ' ' . htmlspecialchars($i['unit']); ?>)</option>
                <?php endwhile; ?>
            </select>
            <select name="movement_type" required>
                <option value="">Movement type</option>
                <option value="stock_in">Stock In</option>
                <option value="usage">Usage</option>
                <option value="adjustment">Adjustment (+)</option>
                <option value="wastage">Wastage</option>
            </select>
            <input type="number" step="0.01" name="quantity" placeholder="Quantity" required>
            <input type="text" name="notes" placeholder="Notes">
            <button type="submit" class="action-btn">Save Movement</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Recent Movements</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th style="text-align:left;padding:8px;">Date</th><th style="text-align:left;padding:8px;">Ingredient</th><th style="text-align:left;padding:8px;">Type</th><th style="text-align:left;padding:8px;">Qty</th><th style="text-align:left;padding:8px;">By</th><th style="text-align:left;padding:8px;">Notes</th></tr></thead>
                <tbody>
                <?php while($m = $history->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:8px;"><?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($m['ingredient_name']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($m['movement_type']); ?></td>
                        <td style="padding:8px;"><?php echo number_format((float)$m['quantity'],2) . ' ' . htmlspecialchars($m['unit']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($m['actor']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars((string)$m['notes']); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>