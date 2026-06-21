<?php
require_once __DIR__ . '/../core/auth.php';
require_role('admin');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$recent_logins = $conn->query('SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 20');
$recent_movements = $conn->query('SELECT sm.movement_type, sm.quantity, sm.created_at, i.name AS ingredient_name, u.name AS actor FROM stock_movements sm JOIN ingredients i ON sm.ingredient_id=i.id JOIN users u ON sm.created_by=u.id ORDER BY sm.created_at DESC LIMIT 25');

$backup_text = "Daily backups are handled at server level. Suggested policy:\n- Full backup every midnight\n- Incremental backup every 2 hours\n- Retention: 14 days\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit - FoodFlow</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Admin</div>
    <div class="navbar-user"><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div>
</nav>
<nav class="admin-nav">
    <ul class="admin-nav-links">
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="system_audit.php" class="active">System Audit</a></li>
    </ul>
</nav>
<div class="container">
    <div class="card">
        <h3>🛡️ User and Role Audit</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
                <tbody>
                <?php while($u = $recent_logins->fetch_assoc()): ?>
                    <tr><td><?php echo htmlspecialchars($u['name']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td><td><?php echo htmlspecialchars($u['role']); ?></td><td><?php echo date('Y-m-d H:i', strtotime($u['created_at'])); ?></td></tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>📦 Recent System Activity</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Ingredient</th><th>Qty</th></tr></thead>
                <tbody>
                <?php while($m = $recent_movements->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($m['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($m['actor']); ?></td>
                        <td><?php echo htmlspecialchars($m['movement_type']); ?></td>
                        <td><?php echo htmlspecialchars($m['ingredient_name']); ?></td>
                        <td><?php echo number_format((float)$m['quantity'],2); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>💾 Backup Policy Note</h3>
        <pre style="white-space:pre-wrap;margin:0;"><?php echo htmlspecialchars($backup_text); ?></pre>
    </div>
</div>
</body>
</html>
