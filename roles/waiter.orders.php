<?php
// Placeholder file created as requested.
//under roles
//code for waiter_orders.php
require_once __DIR__ . '/../core/auth.php';
require_role('waiter');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$waiter_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_number = trim($_POST['table_number'] ?? '');
    $menu_item_id = (int)($_POST['menu_item_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if ($table_number === '' || $menu_item_id <= 0) {
        $error = 'Please provide valid order details.';
    } else {
        $conn->begin_transaction();
        try {
            $menuStmt = $conn->prepare('SELECT id, name, selling_price FROM menu_items WHERE id = ? AND is_available = 1');
            $menuStmt->bind_param('i', $menu_item_id);
            $menuStmt->execute();
            $menu = $menuStmt->get_result()->fetch_assoc();
            if (!$menu) {
                throw new Exception('Menu item is unavailable.');
            }

            $order_number = 'ORD-' . date('Ymd-His') . '-' . random_int(100, 999);
            $unit_price = (float)$menu['selling_price'];
            $line_total = $unit_price * $quantity;

            $orderStmt = $conn->prepare('INSERT INTO orders (order_number, waiter_id, table_number, status, total_amount) VALUES (?, ?, ?, "pending", ?)');
            $orderStmt->bind_param('sisd', $order_number, $waiter_id, $table_number, $line_total);
            $orderStmt->execute();
            $order_id = $conn->insert_id;

            $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)');
            $itemStmt->bind_param('iiidd', $order_id, $menu_item_id, $quantity, $unit_price, $line_total);
            $itemStmt->execute();

            $recipe = $conn->prepare('SELECT ingredient_id, quantity_required FROM recipe_ingredients WHERE menu_item_id = ?');
            $recipe->bind_param('i', $menu_item_id);
            $recipe->execute();
            $recipeResult = $recipe->get_result();

            while ($row = $recipeResult->fetch_assoc()) {
                $ingredient_id = (int)$row['ingredient_id'];
                $used_qty = (float)$row['quantity_required'] * $quantity;

                $conn->query("UPDATE ingredients SET current_stock = GREATEST(0, current_stock - {$used_qty}) WHERE id = {$ingredient_id}");

                $movementStmt = $conn->prepare('INSERT INTO stock_movements (ingredient_id, movement_type, quantity, reference_type, reference_id, notes, created_by) VALUES (?, "usage", ?, "order", ?, ?, ?)');
                $note = 'Auto usage from order ' . $order_number;
                $movementStmt->bind_param('idisi', $ingredient_id, $used_qty, $order_id, $note, $waiter_id);
                $movementStmt->execute();
            }

            $conn->commit();
            $message = 'Order created successfully: ' . $order_number;
        } catch (Throwable $e) {
            $conn->rollback();
            $error = 'Order failed: ' . $e->getMessage();
        }
    }
}

$menu_items = $conn->query('SELECT id, name, selling_price FROM menu_items WHERE is_available = 1 ORDER BY name');
$orders = $conn->query("SELECT
    o.order_number,
    o.table_number,
    o.status,
    o.total_amount,
    o.created_at,
    IFNULL(GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) ORDER BY mi.name SEPARATOR ', '), 'No items') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
    WHERE o.waiter_id = {$waiter_id}
    GROUP BY o.id, o.order_number, o.table_number, o.status, o.total_amount, o.created_at
    ORDER BY o.created_at DESC
    LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiter Orders - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Waiter</div>
    <div class="navbar-user"><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div>
</nav>
<nav class="admin-nav">
    <ul class="admin-nav-links">
        <li><a href="waiter_dashboard.php">Dashboard</a></li>
        <li><a href="waiter_orders.php" class="active">Record Orders</a></li>
        <li><a href="../admin/ingredients.php">Stock View</a></li>
    </ul>
</nav>
<div class="container">
    <div class="card">
        <h3>🧾 Record New Order</h3>
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
        <form method="POST" style="display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;">
            <input type="text" name="table_number" placeholder="Table (e.g. T5)" required>
            <select name="menu_item_id" required>
                <option value="">Select item</option>
                <?php while($m = $menu_items->fetch_assoc()): ?>
                    <option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['name']); ?> - Kshs. <?php echo number_format((float)$m['selling_price'], 2); ?></option>
                <?php endwhile; ?>
            </select>
            <input type="number" min="1" name="quantity" value="1" required>
            <button type="submit" class="btn btn-primary">Create Order</button>
        </form>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> My Recent Orders</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Order #</th><th>Table</th><th>Items</th><th>Status</th><th>Total</th><th>Time</th></tr></thead>
                <tbody>
                <?php while($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($o['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($o['table_number']); ?></td>
                        <td><?php echo htmlspecialchars((string)$o['items_summary']); ?></td>
                        <td><?php echo htmlspecialchars($o['status']); ?></td>
                        <td>Kshs. <?php echo number_format((float)$o['total_amount'], 2); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($o['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>