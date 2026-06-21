<?php
require_once __DIR__ . '/../core/auth.php';
require_role('admin');

$servername = "localhost";
$username = "root";
$password = "1234";
$dbname = "food_inventory";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$total_users = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FoodFlow</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">FoodFlow Admin</div>
        <div class="navbar-user">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (Admin)</span>
            <a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <nav class="admin-nav">
        <ul class="admin-nav-links">
            <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="system_audit.php">System Audit</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1>Admin Dashboard</h1>
            <p>User administration and audit oversight</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="manage_users.php" class="action-btn"> Manage Users</a>
            <a href="system_audit.php" class="action-btn"> Review System Audit</a>
        </div>
    </div>
</body>
</html>