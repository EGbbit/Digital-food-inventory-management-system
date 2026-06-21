<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager')) {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? 'kg');
    $stock = (float)($_POST['current_stock'] ?? 0);
    $reorder = (float)($_POST['reorder_level'] ?? 0);
    $cost = (float)($_POST['unit_cost'] ?? 0);

    if ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssddd", $name, $category, $unit, $stock, $reorder, $cost);
        $message = $stmt->execute() ? "Ingredient added." : ("Add failed: " . $stmt->error);
        $stmt->close();
    }
}

$ingredients = $conn->query("SELECT * FROM ingredients ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Ingredients - FoodFlow</title><link rel="stylesheet" href="admin_styles.css"></head>
<body>
<nav class="navbar"><div class="navbar-brand">FoodFlow Inventory</div><div class="navbar-user"><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div></nav>
<nav class="admin-nav"><ul class="admin-nav-links"><li><a href="admin_dashboard.php">Dashboard</a></li><?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?><li><a href="manage_users.php">Manage Users</a></li><li><a href="system_audit.php">System Audit</a></li><?php endif; ?></ul></nav>
<div class="container">
    <div class="card">
        <h3> Ingredient Master</h3>
        <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
        <form method="POST" style="display:grid;grid-template-columns:repeat(3,minmax(160px,1fr));gap:10px;">
            <input type="text" name="name" placeholder="Ingredient name" required>
            <input type="text" name="category" placeholder="Category">
            <input type="text" name="unit" placeholder="Unit (kg/liter/pcs)" required>
            <input type="number" step="0.01" name="current_stock" placeholder="Current stock" required>
            <input type="number" step="0.01" name="reorder_level" placeholder="Reorder level" required>
            <input type="number" step="0.01" name="unit_cost" placeholder="Unit cost" required>
            <button type="submit" class="action-btn">Add Ingredient</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Stock Overview</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th style="text-align:left;padding:8px;">Name</th><th style="text-align:left;padding:8px;">Category</th><th style="text-align:left;padding:8px;">Stock</th><th style="text-align:left;padding:8px;">Reorder</th><th style="text-align:left;padding:8px;">Unit Cost</th><th style="text-align:left;padding:8px;">Status</th></tr></thead>
                <tbody>
                <?php while($i = $ingredients->fetch_assoc()): $low = ((float)$i['current_stock'] <= (float)$i['reorder_level']); ?>
                    <tr>
                        <td style="padding:8px;"><?php echo htmlspecialchars($i['name']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars((string)$i['category']); ?></td>
                        <td style="padding:8px;"><?php echo number_format((float)$i['current_stock'],2) . ' ' . htmlspecialchars($i['unit']); ?></td>
                        <td style="padding:8px;"><?php echo number_format((float)$i['reorder_level'],2); ?></td>
                        <td style="padding:8px;">Kshs. <?php echo number_format((float)$i['unit_cost'],2); ?></td>
                        <td style="padding:8px;"><?php echo $low ? 'Low Stock' : 'OK'; ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body></html>
