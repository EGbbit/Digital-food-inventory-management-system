<?php
// Placeholder file created as requested.
// under roles
//code for chef_inventory.php
require_once __DIR__ . '/../core/auth.php';
require_role('chef');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$chef_id = (int)$_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $quantity = (float)($_POST['quantity'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($ingredient_id > 0 && $quantity > 0 && in_array($action, ['usage', 'wastage'], true)) {
        $conn->query("UPDATE ingredients SET current_stock = GREATEST(0, current_stock - {$quantity}) WHERE id = {$ingredient_id}");

        $stmt = $conn->prepare('INSERT INTO stock_movements (ingredient_id, movement_type, quantity, reference_type, reference_id, notes, created_by) VALUES (?, ?, ?, "manual", NULL, ?, ?)');
        $stmt->bind_param('isdsi', $ingredient_id, $action, $quantity, $note, $chef_id);
        $stmt->execute();

        if ($action === 'wastage') {
            $reason = $note !== '' ? $note : 'Kitchen wastage';
            $wasteStmt = $conn->prepare('INSERT INTO wastage_logs (ingredient_id, quantity, reason, logged_by) VALUES (?, ?, ?, ?)');
            $wasteStmt->bind_param('idsi', $ingredient_id, $quantity, $reason, $chef_id);
            $wasteStmt->execute();
        }

        $message = ucfirst($action) . ' logged successfully.';
    }
}

$ingredients = $conn->query('SELECT id, name, unit, current_stock, reorder_level FROM ingredients ORDER BY name');
$alerts = $conn->query('SELECT a.message, a.created_at, i.name AS ingredient_name FROM alerts a JOIN ingredients i ON a.ingredient_id = i.id WHERE a.is_resolved = 0 ORDER BY a.created_at DESC LIMIT 15');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chef Inventory Console - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body class="dashboard-photo dashboard-chef">
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Chef</div>
    <div class="navbar-user"><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div>
</nav>
<nav class="admin-nav">
    <ul class="admin-nav-links">
        <li><a href="../roles/chef_dashboard.php">Dashboard</a></li>
        <li><a href="../roles/chef_inventory.php" class="active">Inventory Console</a></li>
        <li><a href="../roles/open_menu.php">Open Food Menu</a></li>
        <li><a href="../admin/ingredients.php">Ingredients</a></li>
    </ul>
</nav>
<div class="container">
    <div class="card">
        <h3> Log Kitchen Consumption</h3>
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <form method="POST" style="display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;">
            <select name="ingredient_id" required>
                <option value="">Select ingredient</option>
                <?php while($i = $ingredients->fetch_assoc()): ?>
                    <option value="<?php echo (int)$i['id']; ?>"><?php echo htmlspecialchars($i['name']); ?> (<?php echo number_format((float)$i['current_stock'],2) . ' ' . htmlspecialchars($i['unit']); ?>)</option>
                <?php endwhile; ?>
            </select>
            <select name="action" required>
                <option value="usage">Usage</option>
                <option value="wastage">Wastage</option>
            </select>
            <input type="number" step="0.01" min="0.01" name="quantity" placeholder="Quantity" required>
            <input type="text" name="note" placeholder="Batch/Reason note">
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Active Alerts</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Ingredient</th><th>Message</th><th>Created</th></tr></thead>
                <tbody>
                <?php while($a = $alerts->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['ingredient_name']); ?></td>
                        <td><?php echo htmlspecialchars($a['message']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($a['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>