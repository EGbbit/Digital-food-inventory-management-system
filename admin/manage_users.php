<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $allowed = ['admin', 'waiter', 'chef', 'manager'];

    if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && in_array($role, $allowed, true)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $message = "Create failed: Email already exists.";
        } else {
            $presetPassword = '1234';
            $passwordHash = password_hash($presetPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $passwordHash, $phone, $role);
            if ($stmt->execute()) {
                $message = "User created. Preset login -> Email: " . $email . " | Password: " . $presetPassword . " (ask user to change after first login).";
            } else {
                $message = "Create failed: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $message = "Please provide valid values.";
    }
}

$users = $conn->query("SELECT name, email, role, phone, is_active, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FoodFlow</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
<nav class="navbar"><div class="navbar-brand">FoodFlow Admin</div><div class="navbar-user"><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div></nav>
<nav class="admin-nav"><ul class="admin-nav-links"><li><a href="admin_dashboard.php">Dashboard</a></li><li><a href="manage_users.php" class="active">Manage Users</a></li><li><a href="system_audit.php">System Audit</a></li></ul></nav>
<div class="container">
    <div class="card">
        <h3>➕ Add User</h3>
        <?php if ($message): ?><p><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <p style="margin-bottom:10px;color:#555;">Admin creates accounts only. All new users are assigned the preset password: <strong>1234</strong>.</p>
        <form method="POST" style="display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:12px;">
            <input type="text" name="name" placeholder="Full name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone">
            <select name="role" required>
                <option value="">Select role</option><option value="admin">Admin</option><option value="waiter">Waiter</option><option value="chef">Chef</option><option value="manager">Manager</option>
            </select>
            <button type="submit" class="action-btn">Create User</button>
        </form>
    </div>
    <div class="card" style="margin-top:16px;">
        <h3>👥 User Directory</h3>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th style="text-align:left;padding:8px;">Name</th><th style="text-align:left;padding:8px;">Email</th><th style="text-align:left;padding:8px;">Role</th><th style="text-align:left;padding:8px;">Phone</th><th style="text-align:left;padding:8px;">Status</th></tr></thead>
                <tbody>
                <?php while($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:8px;"><?php echo htmlspecialchars($u['name']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars($u['role']); ?></td>
                        <td style="padding:8px;"><?php echo htmlspecialchars((string)$u['phone']); ?></td>
                        <td style="padding:8px;"><?php echo ((int)$u['is_active'] === 1) ? 'Active' : 'Inactive'; ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
