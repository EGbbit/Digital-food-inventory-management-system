<?php

require_once __DIR__ . '/../core/auth.php';
require_role('waiter');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS order_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL,
    table_number VARCHAR(20) NOT NULL,
    waiter_id INT NOT NULL,
    alert_status ENUM('new','seen') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alert_status (alert_status),
    INDEX idx_order_id (order_id)
)");

$waiter_id = (int)$_SESSION['user_id'];
$message = '';
$error = '';

$conn->query("CREATE TABLE IF NOT EXISTS unavailable_item_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_query VARCHAR(120) NOT NULL,
    request_date DATE NOT NULL,
    request_count INT NOT NULL DEFAULT 1,
    last_waiter_id INT NULL,
    last_requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_item_query_date (item_query, request_date),
    INDEX idx_request_date (request_date)
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['log_unavailable_request'])) {
        $searchedItem = trim((string)($_POST['searched_item_query'] ?? ''));
        if ($searchedItem !== '') {
            $searchedItem = substr($searchedItem, 0, 120);
            $today = date('Y-m-d');
            $logStmt = $conn->prepare('INSERT INTO unavailable_item_requests (item_query, request_date, request_count, last_waiter_id) VALUES (?, ?, 1, ?) ON DUPLICATE KEY UPDATE request_count = request_count + 1, last_waiter_id = VALUES(last_waiter_id), last_requested_at = CURRENT_TIMESTAMP');
            if ($logStmt) {
                $logStmt->bind_param('ssi', $searchedItem, $today, $waiter_id);
                $logStmt->execute();
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }

    $table_number = trim($_POST['table_number'] ?? '');
    $menu_item_id = (int)($_POST['menu_item_id'] ?? 0);
    $food_item_name = trim((string)($_POST['food_item_name'] ?? ''));
    $meal_category = trim((string)($_POST['meal_category'] ?? ''));
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if ($menu_item_id <= 0 && $food_item_name !== '') {
        $lookupSql = 'SELECT id FROM menu_items WHERE LOWER(name) = LOWER(?) AND is_available = 1';
        if ($meal_category !== '') {
            $lookupSql .= ' AND category = ?';
        }
        $lookupSql .= ' LIMIT 1';

        $lookupStmt = $conn->prepare($lookupSql);
        if ($lookupStmt) {
            if ($meal_category !== '') {
                $lookupStmt->bind_param('ss', $food_item_name, $meal_category);
            } else {
                $lookupStmt->bind_param('s', $food_item_name);
            }
            $lookupStmt->execute();
            $lookupRow = $lookupStmt->get_result()->fetch_assoc();
            if ($lookupRow) {
                $menu_item_id = (int)$lookupRow['id'];
            }
        }
    }

    if ($table_number === '' || $menu_item_id <= 0) {
        $error = 'Not available in menu. Please select an available menu item.';
    } else {
        $conn->begin_transaction();
        try {
            $menuStmt = $conn->prepare('SELECT id, name, category, selling_price FROM menu_items WHERE id = ? AND is_available = 1');
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

            $alertStmt = $conn->prepare('INSERT INTO order_alerts (order_id, order_number, table_number, waiter_id, alert_status) VALUES (?, ?, ?, ?, "new")');
            $alertStmt->bind_param('issi', $order_id, $order_number, $table_number, $waiter_id);
            $alertStmt->execute();

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

$menu_items = $conn->query('SELECT id, name, category, selling_price FROM menu_items WHERE is_available = 1 ORDER BY name');
$menu_counts = $conn->query('SELECT COUNT(*) AS available_count FROM menu_items WHERE is_available = 1')->fetch_assoc();
$available_menu_count = (int)($menu_counts['available_count'] ?? 0);
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
        <li><a href="open_menu.php">Open Food Menu</a></li>
    </ul>
</nav>
<div class="container">
    <div class="card">
        <h3>🧾 Record New Order</h3>
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
        <p style="margin-bottom:10px;color:#555;">Available menu items: <strong><?php echo $available_menu_count; ?></strong></p>
        <?php if ($available_menu_count === 0): ?>
            <p class="warning">No available food items found. Ask supervisor/manager to add or enable menu items in Menu Management.</p>
        <?php endif; ?>
        <form id="order-form" method="POST" style="display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;">
            <input type="text" name="table_number" placeholder="Table (e.g. T5)" required>
            <select id="menu-category" name="meal_category">
                <option value="">All Categories</option>
                <option value="Breakfast">Breakfast</option>
                <option value="Lunch">Lunch</option>
                <option value="Dinner">Dinner</option>
                <option value="Starter">Starter</option>
                <option value="Main">Main</option>
                <option value="Side">Side</option>
                <option value="Dessert">Dessert</option>
                <option value="Drink">Drink</option>
            </select>
            <input id="food-item-input" type="text" name="food_item_name" list="food-item-list" placeholder="Search food item..." autocomplete="off" required>
            <datalist id="food-item-list">
                <?php while($m = $menu_items->fetch_assoc()): ?>
                    <option
                        value="<?php echo htmlspecialchars((string)$m['name']); ?>"
                        data-id="<?php echo (int)$m['id']; ?>"
                        data-category="<?php echo htmlspecialchars((string)$m['category']); ?>"
                        data-price="<?php echo number_format((float)$m['selling_price'], 2, '.', ''); ?>"
                    >
                    </option>
                <?php endwhile; ?>
            </datalist>
            <input id="menu-item-id" type="hidden" name="menu_item_id" value="">
            <input id="order-qty" type="number" min="1" name="quantity" value="1" required>
            <button type="submit" class="btn btn-primary">Create Order</button>
        </form>
        <p id="availability-message" style="margin-top:8px;color:#b00020;display:none;">Item is not available in menu.</p>
        <p id="order-preview" style="margin-top:10px;color:#555;"></p>
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
<script>
    const menuCategory = document.getElementById('menu-category');
    const foodItemInput = document.getElementById('food-item-input');
    const foodItemList = document.getElementById('food-item-list');
    const menuItemIdInput = document.getElementById('menu-item-id');
    const orderQty = document.getElementById('order-qty');
    const orderPreview = document.getElementById('order-preview');
    const availabilityMessage = document.getElementById('availability-message');
    const orderForm = document.getElementById('order-form');
    let lastLoggedUnavailableQuery = '';

    function logUnavailableDemand(query) {
        if (!query || query.trim() === '') {
            return;
        }
        const body = new URLSearchParams();
        body.set('log_unavailable_request', '1');
        body.set('searched_item_query', query.trim());
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: body.toString()
        }).catch(function () {
            // Silent fail: should not block waiter workflow.
        });
    }

    function normalize(value) {
        return (value || '').trim().toLowerCase();
    }

    const allMenuItems = foodItemList
        ? Array.from(foodItemList.options).map(function (opt) {
            return {
                id: parseInt(opt.getAttribute('data-id') || '0', 10),
                name: opt.value || '',
                category: opt.getAttribute('data-category') || '',
                price: parseFloat(opt.getAttribute('data-price') || '0')
            };
        }).filter(function (item) {
            return item.id > 0 && item.name !== '';
        })
        : [];

    function renderDatalist() {
        if (!foodItemList) {
            return;
        }
        const selectedCategory = normalize(menuCategory ? menuCategory.value : '');
        foodItemList.innerHTML = '';

        allMenuItems.forEach(function (item) {
            const categoryMatch = selectedCategory === '' || normalize(item.category) === selectedCategory;
            if (!categoryMatch) {
                return;
            }
            const option = document.createElement('option');
            option.value = item.name;
            option.setAttribute('data-id', String(item.id));
            option.setAttribute('data-category', item.category);
            option.setAttribute('data-price', String(item.price));
            foodItemList.appendChild(option);
        });
    }

    function getMatchingItemsByQuery(query) {
        const selectedCategory = normalize(menuCategory ? menuCategory.value : '');
        const q = normalize(query);
        return allMenuItems.filter(function (item) {
            const categoryMatch = selectedCategory === '' || normalize(item.category) === selectedCategory;
            const queryMatch = q === '' || normalize(item.name).includes(q);
            return categoryMatch && queryMatch;
        });
    }

    function placeExactItemIfAvailable() {
        if (!foodItemInput || !menuItemIdInput) {
            return { exact: null, matches: [] };
        }
        const query = normalize(foodItemInput.value);
        const matches = getMatchingItemsByQuery(query);
        const exact = matches.find(function (item) {
            return normalize(item.name) === query;
        }) || null;

        if (exact && exact.id > 0) {
            menuItemIdInput.value = String(exact.id);
        } else {
            menuItemIdInput.value = '';
        }

        return { exact, matches };
    }

    function renderOrderPreview() {
        if (!foodItemInput || !orderQty || !orderPreview) {
            return;
        }
        const query = normalize(foodItemInput.value);
        const selectedCategory = normalize(menuCategory ? menuCategory.value : '');
        const matchedItem = allMenuItems.find(function (item) {
            const categoryMatch = selectedCategory === '' || normalize(item.category) === selectedCategory;
            return categoryMatch && normalize(item.name) === query;
        });
        const price = matchedItem ? matchedItem.price : 0;
        const qty = Math.max(1, parseInt(orderQty.value || '1', 10));
        if (price > 0) {
            orderPreview.textContent = 'Estimated line total: Kshs. ' + (price * qty).toFixed(2);
        } else {
            orderPreview.textContent = 'Select a food item to preview line total.';
        }
    }

    if (menuCategory) {
        menuCategory.addEventListener('change', function () {
            if (foodItemInput) {
                foodItemInput.value = '';
            }
            if (menuItemIdInput) {
                menuItemIdInput.value = '';
            }
            if (availabilityMessage) {
                availabilityMessage.style.display = 'none';
            }
            renderDatalist();
            renderOrderPreview();
        });
    }

    if (foodItemInput) {
        foodItemInput.addEventListener('input', function () {
            const stats = placeExactItemIfAvailable();
            const query = normalize(foodItemInput.value);
            if (availabilityMessage) {
                availabilityMessage.style.display = (query !== '' && stats.matches.length === 0) ? 'block' : 'none';
            }
            renderOrderPreview();
        });

        foodItemInput.addEventListener('blur', function () {
            const query = normalize(foodItemInput.value);
            const stats = placeExactItemIfAvailable();
            if (query !== '' && stats.matches.length === 0 && lastLoggedUnavailableQuery !== query) {
                logUnavailableDemand(query);
                lastLoggedUnavailableQuery = query;
            }
        });
    }

    if (orderForm) {
        orderForm.addEventListener('submit', function (e) {
            const stats = placeExactItemIfAvailable();
            const selectedId = menuItemIdInput ? parseInt(menuItemIdInput.value || '0', 10) : 0;
            if (!(selectedId > 0)) {
                const query = normalize(foodItemInput ? foodItemInput.value : '');
                if (query !== '' && stats.matches.length === 0) {
                    alert('Not available in menu. This demand has been logged for manager review.');
                    if (lastLoggedUnavailableQuery !== query) {
                        logUnavailableDemand(query);
                        lastLoggedUnavailableQuery = query;
                    }
                } else {
                    alert('Please select an available menu item before creating the order.');
                }
                e.preventDefault();
            }
        });
    }

    if (orderQty) {
        orderQty.addEventListener('input', renderOrderPreview);
    }
    renderDatalist();
    placeExactItemIfAvailable();
    renderOrderPreview();
</script>
</body>
</html>