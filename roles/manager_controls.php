<?php
// Placeholder file created as requested.
//under roles
//code for manager_controls.php

require_once __DIR__ . '/../core/auth.php';
require_role('manager');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE TABLE IF NOT EXISTS predictive_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_month DATE NOT NULL UNIQUE,
    report_label VARCHAR(40) NOT NULL,
    report_body TEXT NOT NULL,
    generation_mode ENUM('auto', 'manual') NOT NULL DEFAULT 'manual',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS chef_stock_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredient_id INT NOT NULL,
    chef_id INT NOT NULL,
    observed_stock DECIMAL(10,2) NOT NULL,
    reorder_level_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0,
    suggested_restock_amount DECIMAL(10,2) NULL,
    expected_expiry_date DATE NULL,
    shelf_life_days INT NULL,
    urgency ENUM('normal', 'watch', 'urgent') NOT NULL DEFAULT 'watch',
    comment VARCHAR(300) NOT NULL,
    is_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    acknowledged_by INT NULL,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chef_stock_notes_created (created_at),
    INDEX idx_chef_stock_notes_ack (is_acknowledged),
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE,
    FOREIGN KEY (chef_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (acknowledged_by) REFERENCES users(id) ON DELETE SET NULL
)");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_menu_item'])) {
        $name = trim($_POST['menu_name'] ?? '');
        $category = trim($_POST['menu_category'] ?? '');
        $selling_price = (float)($_POST['selling_price'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        if ($category === '') {
            $category = 'General';
        }

        if ($name !== '' && $selling_price > 0) {
            $findStmt = $conn->prepare('SELECT id FROM menu_items WHERE LOWER(name) = LOWER(?) AND LOWER(COALESCE(category, "")) = LOWER(?) LIMIT 1');
            $findStmt->bind_param('ss', $name, $category);
            $findStmt->execute();
            $existing = $findStmt->get_result()->fetch_assoc();
            $findStmt->close();

            if ($existing) {
                $existingId = (int)$existing['id'];
                $updateStmt = $conn->prepare('UPDATE menu_items SET selling_price = ?, is_available = ? WHERE id = ?');
                $updateStmt->bind_param('dii', $selling_price, $is_available, $existingId);
                if ($updateStmt->execute()) {
                    $message = 'Menu item updated and reflected for all users.';
                } else {
                    $message = 'Failed to update menu item: ' . $updateStmt->error;
                }
                $updateStmt->close();
            } else {
                $stmt = $conn->prepare('INSERT INTO menu_items (name, category, selling_price, is_available) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssdi', $name, $category, $selling_price, $is_available);
                if ($stmt->execute()) {
                    $message = 'Menu item added and reflected for all users.';
                } else {
                    $message = 'Failed to add menu item: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $message = 'Please provide a valid menu name and price.';
        }
    }

    if (isset($_POST['add_ingredient'])) {
        $ingredientName = trim((string)($_POST['ingredient_name'] ?? ''));
        $ingredientCategory = trim((string)($_POST['ingredient_category'] ?? ''));
        $ingredientUnit = trim((string)($_POST['ingredient_unit'] ?? ''));
        $ingredientStock = (float)($_POST['ingredient_stock'] ?? 0);
        $ingredientReorder = (float)($_POST['ingredient_reorder'] ?? 0);
        $ingredientCost = (float)($_POST['ingredient_unit_cost'] ?? 0);
        $ingredientActive = isset($_POST['ingredient_is_active']) ? 1 : 0;

        if ($ingredientName === '' || $ingredientUnit === '' || $ingredientStock < 0 || $ingredientReorder < 0 || $ingredientCost < 0) {
            $message = 'Please provide valid ingredient name, unit and non-negative values.';
        } else {
            if ($ingredientCategory === '') {
                $ingredientCategory = 'General';
            }

            $dupStmt = $conn->prepare('SELECT id FROM ingredients WHERE LOWER(name) = LOWER(?) LIMIT 1');
            $dupStmt->bind_param('s', $ingredientName);
            $dupStmt->execute();
            $dupRow = $dupStmt->get_result()->fetch_assoc();
            $dupStmt->close();

            if ($dupRow) {
                $message = 'Ingredient already exists. Update its threshold below or edit through ingredient controls.';
            } else {
                $addIngredientStmt = $conn->prepare('INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                if ($addIngredientStmt) {
                    $addIngredientStmt->bind_param('sssdddi', $ingredientName, $ingredientCategory, $ingredientUnit, $ingredientStock, $ingredientReorder, $ingredientCost, $ingredientActive);
                    if ($addIngredientStmt->execute()) {
                        $message = 'Ingredient added successfully and is now available to chef and stock flows.';
                    } else {
                        $message = 'Failed to add ingredient: ' . $addIngredientStmt->error;
                    }
                    $addIngredientStmt->close();
                }
            }
        }
    }

    if (isset($_POST['load_starter_menu'])) {
        $starterItems = [
            ['Beef Burger', 'Main', 550.00, 1],
            ['Chicken Wrap', 'Main', 480.00, 1],
            ['Vegetable Pasta', 'Main', 620.00, 1],
            ['Grilled Fish', 'Main', 760.00, 1],
            ['French Fries', 'Side', 250.00, 1],
            ['Caesar Salad', 'Starter', 390.00, 1],
            ['Tomato Soup', 'Starter', 320.00, 1],
            ['Fresh Juice', 'Drink', 220.00, 1],
            ['Iced Tea', 'Drink', 180.00, 1],
            ['Fruit Salad', 'Dessert', 300.00, 1]
        ];

        $inserted = 0;
        foreach ($starterItems as $item) {
            $check = $conn->prepare('SELECT id FROM menu_items WHERE name = ? LIMIT 1');
            $check->bind_param('s', $item[0]);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$exists) {
                $ins = $conn->prepare('INSERT INTO menu_items (name, category, selling_price, is_available) VALUES (?, ?, ?, ?)');
                $ins->bind_param('ssdi', $item[0], $item[1], $item[2], $item[3]);
                if ($ins->execute()) {
                    $inserted++;
                }
                $ins->close();
            }
        }
        $message = 'Starter menu sync complete. New items added: ' . $inserted . '.';
    }

    if (isset($_POST['delete_menu_item'])) {
        $menu_item_id = (int)($_POST['menu_item_id'] ?? 0);
        if ($menu_item_id > 0) {
            $stmt = $conn->prepare('DELETE FROM menu_items WHERE id = ?');
            $stmt->bind_param('i', $menu_item_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = 'Menu item removed.';
                } else {
                    $message = 'Menu item not found.';
                }
            } else {
                $message = 'Cannot remove this item yet. It may already be used in existing orders or recipes.';
            }
            $stmt->close();
        }
    }

    if (isset($_POST['update_threshold'])) {
        $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
        $reorder_level = (float)($_POST['reorder_level'] ?? 0);
        if ($ingredient_id > 0 && $reorder_level >= 0) {
            $stmt = $conn->prepare('UPDATE ingredients SET reorder_level = ? WHERE id = ?');
            $stmt->bind_param('di', $reorder_level, $ingredient_id);
            $stmt->execute();
            $message = 'Threshold updated.';
        }
    }

    if (isset($_POST['resolve_alert'])) {
        $alert_id = (int)($_POST['alert_id'] ?? 0);
        if ($alert_id > 0) {
            $conn->query("UPDATE alerts SET is_resolved = 1, resolved_at = NOW() WHERE id = {$alert_id}");
            $message = 'Alert resolved.';
        }
    }
}

$ingredients = $conn->query('SELECT id, name, category, unit, current_stock, reorder_level, unit_cost FROM ingredients ORDER BY name');
$alerts = $conn->query('SELECT a.id, a.alert_type, a.message, a.created_at, i.name AS ingredient_name FROM alerts a JOIN ingredients i ON a.ingredient_id=i.id WHERE a.is_resolved=0 ORDER BY a.created_at DESC');
$menu_items = $conn->query('SELECT id, name, category, selling_price, is_available, created_at FROM menu_items ORDER BY name');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Controls - FoodFlow</title>
    <link rel="stylesheet" href="roles_styles.css">
</head>
<body class="dashboard-photo dashboard-manager">
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Manager</div>
    <div class="navbar-user"><span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span><a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a><a href="../auth/logout.php" class="logout-btn">Logout</a></div>
</nav>
<nav class="admin-nav">
    <ul class="admin-nav-links">
        <li><a href="manager_dashboard.php">Dashboard</a></li>
        <li><a href="manager_controls.php" class="active">Thresholds & Approvals</a></li>
        <li><a href="open_menu.php">Open Food Menu</a></li>
        <li><a href="manager_reports.php">Reports</a></li>
    </ul>
</nav>
<div class="container">
    <div class="card" style="margin-bottom:16px;">
        <h3>Inventory Creation Actions</h3>
        <p style="margin-bottom:10px;color:#555;">Add Ingredient and Add Food Item containers are maintained in Reports only to avoid duplicate entry points.</p>
        <a href="manager_reports.php" class="btn btn-primary">Open Reports Quick Add</a>
    </div>

    <div class="card">
        <h3>⚙️ Threshold Management</h3>
        <?php if ($message): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Ingredient</th><th>Current Stock</th><th>Reorder Level</th><th>Update</th></tr></thead>
                <tbody>
                <?php while($i = $ingredients->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($i['name']); ?></td>
                        <td><?php echo number_format((float)$i['current_stock'], 2) . ' ' . htmlspecialchars($i['unit']); ?></td>
                        <td><?php echo number_format((float)$i['reorder_level'], 2); ?></td>
                        <td>
                            <form method="POST" style="display:flex;gap:8px;align-items:center;">
                                <input type="hidden" name="ingredient_id" value="<?php echo (int)$i['id']; ?>">
                                <input type="number" step="0.01" min="0" name="reorder_level" value="<?php echo number_format((float)$i['reorder_level'], 2, '.', ''); ?>" required>
                                <button type="submit" name="update_threshold" class="btn btn-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3 id="menu-management"> Menu Management</h3>
        <div class="search-box" style="margin-bottom:10px;">
            <input id="menu-table-search" type="text" placeholder="Search food item in menu table...">
        </div>
        <form method="POST" style="margin-bottom:12px;">
            <button type="submit" name="load_starter_menu" class="btn btn-success">Load Starter Menu Variety</button>
        </form>
        <p style="margin-bottom:12px;color:#555;">Add Food Item is available in Reports quick actions.</p>

        <div class="table-responsive" style="margin-top:14px;">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($m = $menu_items->fetch_assoc()): ?>
                    <tr class="menu-row">
                        <td><?php echo htmlspecialchars($m['name']); ?></td>
                        <td><?php echo htmlspecialchars((string)$m['category']); ?></td>
                        <td>Kshs. <?php echo number_format((float)$m['selling_price'], 2); ?></td>
                        <td><?php echo ((int)$m['is_available'] === 1) ? 'Available' : 'Unavailable'; ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this menu item?');">
                                <input type="hidden" name="menu_item_id" value="<?php echo (int)$m['id']; ?>">
                                <button type="submit" name="delete_menu_item" class="btn btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3> Alert Review Queue</h3>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Type</th><th>Ingredient</th><th>Message</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($a = $alerts->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['alert_type']); ?></td>
                        <td><?php echo htmlspecialchars($a['ingredient_name']); ?></td>
                        <td><?php echo htmlspecialchars($a['message']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($a['created_at'])); ?></td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="alert_id" value="<?php echo (int)$a['id']; ?>">
                                <button type="submit" name="resolve_alert" class="btn btn-success">Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    const menuTableSearch = document.getElementById('menu-table-search');
    if (menuTableSearch) {
        menuTableSearch.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('.menu-row').forEach((row) => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>