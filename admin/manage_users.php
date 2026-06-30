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
$isError = false;
$currentAdminId = (int)$_SESSION['user_id'];
$allowed = ['admin', 'waiter', 'chef', 'manager'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && in_array($role, $allowed, true)) {
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->bind_param("s", $email);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $isError = true;
                $message = "Create failed: Email already exists.";
            } else {
                $presetPassword = '1234';
                $passwordHash = password_hash($presetPassword, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssss", $name, $email, $passwordHash, $phone, $role);
                if ($stmt->execute()) {
                    $message = "User created. Preset login -> Email: " . $email . " | Password: " . $presetPassword . " (ask user to change after first login).";
                } else {
                    $isError = true;
                    $message = "Create failed: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $isError = true;
            $message = "Please provide valid values for create user.";
        }
    } elseif ($action === 'update') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $resetPassword = isset($_POST['reset_password']);

        if ($userId <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowed, true)) {
            $isError = true;
            $message = "Update failed: invalid data provided.";
        } else {
            $emailCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
            $emailCheck->bind_param('si', $email, $userId);
            $emailCheck->execute();
            $emailInUse = $emailCheck->get_result()->num_rows > 0;
            $emailCheck->close();

            if ($emailInUse) {
                $isError = true;
                $message = "Update failed: another user already has that email.";
            } else {
                if ($userId === $currentAdminId && $isActive === 0) {
                    $isError = true;
                    $message = "Update failed: you cannot deactivate your own admin account.";
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ? WHERE id = ?");
                    $updateStmt->bind_param('ssssii', $name, $email, $phone, $role, $isActive, $userId);
                    $ok = $updateStmt->execute();
                    $updateStmt->close();

                    if ($ok) {
                        if ($resetPassword) {
                            $newPasswordHash = password_hash('1234', PASSWORD_BCRYPT);
                            $passwordStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $passwordStmt->bind_param('si', $newPasswordHash, $userId);
                            $passwordStmt->execute();
                            $passwordStmt->close();
                            $message = "User updated and password reset to 1234.";
                        } else {
                            $message = "User updated successfully.";
                        }
                    } else {
                        $isError = true;
                        $message = "Update failed. Please try again.";
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $isError = true;
            $message = "Delete failed: invalid user id.";
        } elseif ($userId === $currentAdminId) {
            $isError = true;
            $message = "Delete failed: you cannot delete your own account while logged in.";
        } else {
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->bind_param('i', $userId);
            if ($deleteStmt->execute()) {
                if ($deleteStmt->affected_rows > 0) {
                    $message = "User deleted successfully.";
                } else {
                    $isError = true;
                    $message = "Delete failed: user not found.";
                }
            } else {
                $isError = true;
                $message = "Delete failed: user is referenced in operational records. Deactivate instead.";
            }
            $deleteStmt->close();
        }
    } else {
        $isError = true;
        $message = "Unknown action requested.";
    }
}

$users = $conn->query("SELECT id, name, email, role, phone, is_active, created_at FROM users ORDER BY created_at DESC");
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
        <?php if ($message): ?>
            <p class="<?php echo $isError ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <p style="margin-bottom:10px;color:#555;">Admin creates accounts only. All new users are assigned the preset password: <strong>1234</strong>.</p>
        <form method="POST" style="display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:12px;">
            <input type="hidden" name="action" value="create">
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
        <p style="margin-bottom:10px;color:#555;">Use Update to edit user profile, role, status, or reset password. Delete removes user permanently when not referenced by existing records.</p>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th style="text-align:left;padding:8px;">Name</th><th style="text-align:left;padding:8px;">Email</th><th style="text-align:left;padding:8px;">Role</th><th style="text-align:left;padding:8px;">Phone</th><th style="text-align:left;padding:8px;">Status</th><th style="text-align:left;padding:8px;">Created</th><th style="text-align:left;padding:8px;">Actions</th></tr></thead>
                <tbody>
                <?php while($u = $users->fetch_assoc()): ?>
                    <?php $updateFormId = 'update-user-' . (int)$u['id']; ?>
                    <tr>
                        <td style="padding:8px;"><input type="text" name="name" value="<?php echo htmlspecialchars($u['name']); ?>" required style="width:160px;" form="<?php echo htmlspecialchars($updateFormId); ?>"></td>
                        <td style="padding:8px;"><input type="email" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" required style="width:220px;" form="<?php echo htmlspecialchars($updateFormId); ?>"></td>
                        <td style="padding:8px;">
                            <select name="role" required form="<?php echo htmlspecialchars($updateFormId); ?>">
                                <?php foreach ($allowed as $r): ?>
                                    <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($u['role'] === $r) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($r)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td style="padding:8px;"><input type="text" name="phone" value="<?php echo htmlspecialchars((string)$u['phone']); ?>" style="width:150px;" form="<?php echo htmlspecialchars($updateFormId); ?>"></td>
                        <td style="padding:8px;">
                            <label style="display:flex;align-items:center;gap:6px;">
                                <input type="checkbox" name="is_active" value="1" <?php echo ((int)$u['is_active'] === 1) ? 'checked' : ''; ?> form="<?php echo htmlspecialchars($updateFormId); ?>">
                                Active
                            </label>
                        </td>
                        <td style="padding:8px;"><?php echo date('Y-m-d H:i', strtotime((string)$u['created_at'])); ?></td>
                        <td style="padding:8px;">
                            <form id="<?php echo htmlspecialchars($updateFormId); ?>" method="POST" style="display:none;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                            </form>
                            <label style="display:flex;align-items:center;gap:4px;margin-bottom:6px;">
                                <input type="checkbox" name="reset_password" value="1" form="<?php echo htmlspecialchars($updateFormId); ?>">
                                Reset pass to 1234
                            </label>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button type="submit" class="btn btn-primary btn-sm" form="<?php echo htmlspecialchars($updateFormId); ?>">Update</button>
                                    <form method="POST" onsubmit="return confirm('Delete this user account permanently?');" style="margin:0;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
