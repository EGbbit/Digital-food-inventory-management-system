<?php
require_once __DIR__ . '/../core/auth.php';
require_login();

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = 'SELECT id, name, category, selling_price, is_available FROM menu_items WHERE 1=1';
$params = [];
$types = '';

if ($q !== '') {
    $sql .= ' AND name LIKE ?';
    $types .= 's';
    $params[] = '%' . $q . '%';
}

if ($category !== '') {
    $sql .= ' AND category = ?';
    $types .= 's';
    $params[] = $category;
}

$sql .= ' ORDER BY is_available DESC, name ASC';

$stmt = $conn->prepare($sql);
if ($stmt && $types !== '') {
    $stmt->bind_param($types, ...$params);
}
if ($stmt) {
    $stmt->execute();
    $menu = $stmt->get_result();
} else {
    $menu = $conn->query('SELECT id, name, category, selling_price, is_available FROM menu_items ORDER BY is_available DESC, name ASC');
}

$categories = $conn->query('SELECT DISTINCT category FROM menu_items WHERE category IS NOT NULL AND category <> "" ORDER BY category');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Food Menu - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body class="dashboard-photo dashboard-manager">
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Open Menu</div>
    <div class="navbar-user">
        <span><?php echo htmlspecialchars((string)($_SESSION['user_name'] ?? 'User')); ?> (<?php echo htmlspecialchars((string)($_SESSION['role'] ?? 'staff')); ?>)</span>
        <a href="../auth/logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="card">
        <h1>Open Food Menu</h1>
        <form method="GET" class="form-grid" style="margin-bottom:12px;">
            <div class="form-group">
                <label for="q">Search Food Item</label>
                <input id="q" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Type item name...">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <?php $cat = (string)$c['category']; ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="display:flex;gap:8px;align-items:flex-end;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="open_menu.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Food Item</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($menu && $menu->num_rows > 0): ?>
                    <?php while ($m = $menu->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$m['name']); ?></td>
                            <td><?php echo htmlspecialchars((string)$m['category']); ?></td>
                            <td>Kshs. <?php echo number_format((float)$m['selling_price'], 2); ?></td>
                            <td><?php echo ((int)$m['is_available'] === 1) ? 'Available' : 'Unavailable'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No menu items found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
