<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

$error = "";
$info = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'admin_creates_accounts') {
    $info = "Accounts are created by admin. Use the credentials provided to you.";
}

$conn = new mysqli("localhost", "root", "1234", "food_inventory");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            redirect_by_role($user['role']);
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FoodFlow Inventory</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); overflow: hidden; width: 100%; max-width: 400px; }
        .header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .form-container { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 16px; }
        .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #4facfe; text-decoration: none; font-weight: 500; }
        .error { background: #fee; color: #c33; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #fcc; }
        .info { background: #eef7ff; color: #205081; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; border: 1px solid #c8dfff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FoodFlow</h1>
            <p>Digital Food Inventory Management</p>
        </div>
        <div class="form-container">
            <?php if (!empty($error)): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
            <?php if (!empty($info)): ?><div class="info"><?php echo htmlspecialchars($info); ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
            <div class="links">
                <p>Need access? Contact your admin to create your role account.</p>
                <p style="margin-top:8px;font-size:13px;color:#666;">Use your admin-assigned email and preset password.</p>
            </div>
        </div>
    </div>
</body>
</html>
