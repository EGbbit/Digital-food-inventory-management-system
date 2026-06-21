<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

require_login();

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters.";
    } else {
        $userId = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $newHash, $userId);
            if ($update->execute()) {
                $success = "Password updated successfully.";
            } else {
                $error = "Unable to update password right now.";
            }
            $update->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - FoodFlow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); overflow: hidden; width: 100%; max-width: 430px; }
        .header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 28px 20px; text-align: center; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.95; font-size: 13px; }
        .form-container { padding: 28px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px 14px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 15px; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .links { text-align: center; margin-top: 15px; }
        .links a { color: #4facfe; text-decoration: none; font-weight: 500; }
        .error { background: #fee; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 14px; text-align: center; border: 1px solid #fcc; }
        .success { background: #efe; color: #2f7d32; padding: 10px; border-radius: 5px; margin-bottom: 14px; text-align: center; border: 1px solid #b9e6bd; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Change Password</h1>
            <p>Update your preset login password</p>
        </div>
        <div class="form-container">
            <?php if ($error !== ''): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success !== ''): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                </div>
                <button type="submit" class="btn">Save New Password</button>
            </form>

            <div class="links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
