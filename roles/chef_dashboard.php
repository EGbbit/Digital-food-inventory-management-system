<?php
require_once __DIR__ . '/../core/auth.php';
require_role('chef');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
  die('Database connection failed: ' . $conn->connect_error);
}

$page_title = "Chef Dashboard - FoodFlow";
$chef_name = !empty($_SESSION['user_name']) ? (string)$_SESSION['user_name'] : 'Chef Marco';
$station = "Head Chef - Main Kitchen";
$message = '';
$login_success_message = (string)($_SESSION['login_success_message'] ?? '');
if ($login_success_message === '') {
  $login_success_message = trim((string)($_GET['login_msg'] ?? ''));
}
if ($login_success_message !== '') {
  unset($_SESSION['login_success_message']);
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

$conn->query("CREATE TABLE IF NOT EXISTS alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ingredient_id INT NOT NULL,
  alert_type ENUM('low_stock', 'out_of_stock') NOT NULL,
  message VARCHAR(255) NOT NULL,
  is_resolved TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE CASCADE
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

$suggestedCol = $conn->query("SHOW COLUMNS FROM chef_stock_notes LIKE 'suggested_restock_amount'");
if ($suggestedCol && $suggestedCol->num_rows === 0) {
  $conn->query("ALTER TABLE chef_stock_notes ADD COLUMN suggested_restock_amount DECIMAL(10,2) NULL AFTER reorder_level_snapshot");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['submit_stock_note'])) {
    $chefId = (int)($_SESSION['user_id'] ?? 0);
    $ingredientId = (int)($_POST['ingredient_id'] ?? 0);
    $ingredientNameRaw = trim((string)($_POST['ingredient_name'] ?? ''));
    $ingredientName = substr($ingredientNameRaw, 0, 120);
    $ingredientUnit = trim((string)($_POST['ingredient_unit'] ?? ''));
    $observedStock = (float)($_POST['observed_stock'] ?? 0);
    $reorderSnapshot = (float)($_POST['reorder_level_snapshot'] ?? 0);
    $suggestedRestockRaw = trim((string)($_POST['suggested_restock_amount'] ?? ''));
    $suggestedRestock = $suggestedRestockRaw === '' ? max(0, $reorderSnapshot - $observedStock) : (float)$suggestedRestockRaw;
    $shelfLifeDaysRaw = trim((string)($_POST['shelf_life_days'] ?? ''));
    $shelfLifeDays = $shelfLifeDaysRaw === '' ? null : (int)$shelfLifeDaysRaw;
    $expiryDateRaw = trim((string)($_POST['expected_expiry_date'] ?? ''));
    $expiryDate = $expiryDateRaw === '' ? null : $expiryDateRaw;
    $urgency = trim((string)($_POST['urgency'] ?? 'watch'));
    $comment = trim((string)($_POST['chef_comment'] ?? ''));

    if ($comment === '') {
      $message = 'Comment is required for stock note.';
    } else {
      $allowedUrgency = ['normal', 'watch', 'urgent'];
      if (!in_array($urgency, $allowedUrgency, true)) {
        $urgency = 'watch';
      }

      if ($ingredientUnit === '') {
        $ingredientUnit = 'units';
      }

      if ($shelfLifeDays !== null && $shelfLifeDays < 0) {
        $shelfLifeDays = 0;
      }

      if ($suggestedRestock < 0) {
        $suggestedRestock = 0;
      }

      if ($expiryDate !== null) {
        $d = DateTime::createFromFormat('Y-m-d', $expiryDate);
        if (!$d || $d->format('Y-m-d') !== $expiryDate) {
          $expiryDate = null;
        }
      }

      $comment = substr($comment, 0, 300);

      // Priority: if chef typed an item name, use it (resolve existing or auto-create).
      // Otherwise use selected ingredient ID from dropdown.
      if ($ingredientName !== '') {
        $lookupStmt = $conn->prepare('SELECT id, reorder_level FROM ingredients WHERE LOWER(name) = LOWER(?) LIMIT 1');
        if ($lookupStmt) {
          $lookupStmt->bind_param('s', $ingredientName);
          $lookupStmt->execute();
          $existingIngredient = $lookupStmt->get_result()->fetch_assoc();
          $lookupStmt->close();

          if ($existingIngredient) {
            $ingredientId = (int)$existingIngredient['id'];
            if ($reorderSnapshot <= 0) {
              $reorderSnapshot = (float)$existingIngredient['reorder_level'];
            }
          } else {
            $seedStock = max(0, $observedStock);
            $seedReorder = max(0, $reorderSnapshot);
            $seedCategory = 'Chef Note';
            $seedUnitCost = 0.00;
            $isActive = 1;
            $createIngredientStmt = $conn->prepare('INSERT INTO ingredients (name, category, unit, current_stock, reorder_level, unit_cost, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
            if ($createIngredientStmt) {
              $createIngredientStmt->bind_param('sssdddi', $ingredientName, $seedCategory, $ingredientUnit, $seedStock, $seedReorder, $seedUnitCost, $isActive);
              if ($createIngredientStmt->execute()) {
                $ingredientId = (int)$conn->insert_id;
              }
              $createIngredientStmt->close();
            }
          }
        }
      }

      if ($ingredientId <= 0) {
        $message = 'Select an existing ingredient or type a new stock item name.';
        goto end_stock_note;
      }

      $noteStmt = $conn->prepare('INSERT INTO chef_stock_notes (ingredient_id, chef_id, observed_stock, reorder_level_snapshot, suggested_restock_amount, expected_expiry_date, shelf_life_days, urgency, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
      if ($noteStmt) {
        $shelfLifeDaysParam = $shelfLifeDays === null ? null : (string)$shelfLifeDays;
        $noteStmt->bind_param('iidddssss', $ingredientId, $chefId, $observedStock, $reorderSnapshot, $suggestedRestock, $expiryDate, $shelfLifeDaysParam, $urgency, $comment);
        if ($noteStmt->execute()) {
          $message = 'Stock note logged and shared with manager reports.';
        } else {
          $message = 'Could not save stock note. Please try again.';
        }
      }
    }
    end_stock_note:
  }

  if (isset($_POST['send_low_stock_alerts'])) {
    $lowRows = $conn->query("SELECT id, name, current_stock, reorder_level FROM ingredients WHERE current_stock <= reorder_level");
    $existingStmt = $conn->prepare('SELECT id FROM alerts WHERE ingredient_id = ? AND is_resolved = 0 AND alert_type IN ("low_stock", "out_of_stock") LIMIT 1');
    $insertStmt = $conn->prepare('INSERT INTO alerts (ingredient_id, alert_type, message, is_resolved) VALUES (?, ?, ?, 0)');

    $created = 0;
    $skipped = 0;

    if ($lowRows && $existingStmt && $insertStmt) {
      while ($row = $lowRows->fetch_assoc()) {
        $ingredientId = (int)$row['id'];
        $ingredientName = (string)$row['name'];
        $currentStock = (float)$row['current_stock'];
        $reorderLevel = (float)$row['reorder_level'];

        $existingStmt->bind_param('i', $ingredientId);
        $existingStmt->execute();
        $existing = $existingStmt->get_result()->fetch_assoc();
        if ($existing) {
          $skipped++;
          continue;
        }

        $alertType = $currentStock <= 0 ? 'out_of_stock' : 'low_stock';
        $alertMessage = $alertType === 'out_of_stock'
          ? ($ingredientName . ' is out of stock.')
          : ($ingredientName . ' is below reorder level (' . number_format($currentStock, 2) . ' <= ' . number_format($reorderLevel, 2) . ').');

        $insertStmt->bind_param('iss', $ingredientId, $alertType, $alertMessage);
        if ($insertStmt->execute()) {
          $created++;
        }
      }
    }

    $message = "Low stock alerts updated. Created {$created} new alert(s), skipped {$skipped} existing.";
  }

  if (isset($_POST['mark_alert_seen'])) {
    $alertId = (int)($_POST['alert_id'] ?? 0);
    if ($alertId > 0) {
      $markStmt = $conn->prepare('UPDATE order_alerts SET alert_status = "seen" WHERE id = ?');
      $markStmt->bind_param('i', $alertId);
      $markStmt->execute();
    }
  }

  if (isset($_POST['set_order_status'])) {
    $alertId = (int)($_POST['alert_id'] ?? 0);
    $orderId = (int)($_POST['order_id'] ?? 0);
    $statusTarget = (string)($_POST['status_target'] ?? '');
    $allowed = ['pending', 'preparing', 'served'];

    if ($orderId > 0 && in_array($statusTarget, $allowed, true)) {
      $statusStmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
      $statusStmt->bind_param('si', $statusTarget, $orderId);
      $statusStmt->execute();

      if ($alertId > 0) {
        $markStmt = $conn->prepare('UPDATE order_alerts SET alert_status = "seen" WHERE id = ?');
        $markStmt->bind_param('i', $alertId);
        $markStmt->execute();
      }

      // If action came from the active ticket queue, clear any new alert tied to this order.
      $markByOrderStmt = $conn->prepare('UPDATE order_alerts SET alert_status = "seen" WHERE order_id = ? AND alert_status = "new"');
      $markByOrderStmt->bind_param('i', $orderId);
      $markByOrderStmt->execute();

      $message = 'Order status updated to ' . $statusTarget . '.';
    }
  }
}

$active_tickets = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE status IN ('pending','preparing')")->fetch_assoc()['c'];
$served_today = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='served' AND DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$pending_orders = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$low_stock_items = (int)$conn->query("SELECT COUNT(*) AS c FROM ingredients WHERE current_stock <= reorder_level")->fetch_assoc()['c'];

$tickets = $conn->query("SELECT
  o.id,
  o.order_number,
  o.table_number,
  o.status,
  o.created_at,
  IFNULL(GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) ORDER BY mi.name SEPARATOR '|'), '') AS items
  FROM orders o
  LEFT JOIN order_items oi ON oi.order_id = o.id
  LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
  WHERE o.status IN ('pending','preparing')
  GROUP BY o.id, o.order_number, o.table_number, o.status, o.created_at
  ORDER BY o.created_at ASC
  LIMIT 8");

$inventory = $conn->query("SELECT name, current_stock, reorder_level
  FROM ingredients
  ORDER BY (CASE WHEN reorder_level > 0 THEN current_stock / reorder_level ELSE 999 END) ASC, name ASC
  LIMIT 8");

$new_order_alerts_count = (int)$conn->query("SELECT COUNT(*) AS c FROM order_alerts WHERE alert_status = 'new'")->fetch_assoc()['c'];
$new_order_alerts = $conn->query("SELECT
  a.id,
  a.order_id,
  a.order_number,
  a.table_number,
  a.created_at,
  o.status,
  IFNULL(GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) ORDER BY mi.name SEPARATOR '|'), '') AS items
  FROM order_alerts a
  LEFT JOIN orders o ON o.id = a.order_id
  LEFT JOIN order_items oi ON oi.order_id = a.order_id
  LEFT JOIN menu_items mi ON mi.id = oi.menu_item_id
  WHERE a.alert_status = 'new'
  GROUP BY a.id, a.order_id, a.order_number, a.table_number, a.created_at, o.status
  ORDER BY a.created_at DESC
  LIMIT 6");

$stock_note_ingredients = $conn->query("SELECT id, name, unit, current_stock, reorder_level
  FROM ingredients
  WHERE is_active = 1
  ORDER BY (CASE WHEN reorder_level > 0 THEN current_stock / reorder_level ELSE 999 END) ASC, name ASC
  LIMIT 50");

$recent_stock_notes = $conn->query("SELECT
  n.id,
  i.name AS ingredient_name,
  ack.name AS ack_manager_name,
  i.unit,
  n.observed_stock,
  n.reorder_level_snapshot,
  n.suggested_restock_amount,
  n.expected_expiry_date,
  n.shelf_life_days,
  n.urgency,
  n.comment,
  n.created_at,
  n.is_acknowledged,
  n.acknowledged_at,
  DATEDIFF(n.expected_expiry_date, CURDATE()) AS days_to_expiry
  FROM chef_stock_notes n
  JOIN ingredients i ON i.id = n.ingredient_id
  LEFT JOIN users ack ON ack.id = n.acknowledged_by
  ORDER BY n.created_at DESC
  LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:      #3FAD61;
      --gold-lt:   #9CE3B3;
      --cream:     #FAF7F2;
      --ink:       #1A1410;
      --surface:   rgba(10, 7, 4, 0.70);
      --s-lt:      rgba(255,255,255,0.05);
      --border:    rgba(255,255,255,0.09);
      --green:     #4CAF72;
      --amber:     #E8A830;
      --red:       #E05252;
      --orange:    #3FAD61;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background: #0d0a07;
      color: var(--cream);
    }

    .bg-layer {
      position: fixed;
      inset: 0;
      background-image: url('../Assets/chef.jpg');
      background-size: cover;
      background-position: center 25%;
      z-index: 0;
    }

    .bg-layer::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(160deg,
          rgba(8, 5, 2, 0.92) 0%,
          rgba(30, 15, 5, 0.72) 50%,
          rgba(8, 5, 2, 0.90) 100%);
    }

    .layout {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 220px 1fr;
      grid-template-rows: auto 1fr;
      min-height: 100vh;
    }

    .topbar {
      grid-column: 1 / -1;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 2rem;
      background: rgba(8,5,2,0.65);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
    }

    .topbar__brand {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.5rem;
      font-weight: 300;
      letter-spacing: 0.08em;
    }

    .topbar__brand em { font-style: normal; color: var(--orange); }

    .topbar__meta {
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: rgba(250,247,242,0.35);
    }

    .topbar__right { display: flex; align-items: center; gap: 1.2rem; }

    .topbar__actions {
      display: flex;
      align-items: center;
      gap: 0.55rem;
    }

    .topbar__link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.4rem 0.75rem;
      border-radius: 6px;
      border: 1px solid rgba(255,255,255,0.2);
      color: var(--cream);
      text-decoration: none;
      font-size: 0.75rem;
      font-weight: 600;
      background: rgba(255,255,255,0.06);
      transition: background 0.15s, border-color 0.15s;
    }

    .topbar__link:hover {
      background: rgba(255,255,255,0.14);
      border-color: rgba(255,255,255,0.32);
    }

    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--orange);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.76rem;
      font-weight: 600;
      color: #fff;
    }

    .sidebar {
      padding: 1.6rem 1rem;
      border-right: 1px solid var(--border);
      background: rgba(8,5,2,0.50);
      backdrop-filter: blur(18px);
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .sidebar__label {
      font-size: 0.6rem;
      font-weight: 500;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: rgba(250,247,242,0.25);
      padding: 0.85rem 0.8rem 0.35rem;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.65rem 0.8rem;
      border-radius: 6px;
      font-size: 0.81rem;
      color: rgba(250,247,242,0.6);
      cursor: pointer;
      border: 1px solid transparent;
      transition: background 0.15s, color 0.15s, border-color 0.15s;
      text-decoration: none;
    }

    .nav-item:hover { background: var(--s-lt); color: var(--cream); }

    .nav-item.active {
      background: rgba(63,173,97,0.18);
      color: #BDECCB;
      border-color: rgba(156,227,179,0.45);
    }

    .nav-item--button {
      width: 100%;
      background: transparent;
      font: inherit;
      text-align: left;
    }

    .nav-item__icon { width: 1rem; text-align: center; }

    .badge {
      margin-left: auto;
      background: var(--red);
      color: #fff;
      font-size: 0.62rem;
      font-weight: 600;
      padding: 0.1rem 0.42rem;
      border-radius: 20px;
    }

    main { padding: 2rem 2.2rem; overflow-y: auto; }

    .page-header { margin-bottom: 1.8rem; }

    .page-header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2rem;
      font-weight: 300;
    }

    .page-header p {
      font-size: 0.78rem;
      color: rgba(250,247,242,0.4);
      margin-top: 0.3rem;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--surface);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.2rem;
    }

    .stat-card__label {
      font-size: 0.66rem;
      font-weight: 500;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(250,247,242,0.35);
      margin-bottom: 0.55rem;
    }

    .stat-card__val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.2rem;
      font-weight: 300;
      line-height: 1;
    }

    .stat-card__sub {
      font-size: 0.7rem;
      color: var(--orange);
      margin-top: 0.35rem;
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 1.4rem;
    }

    .section-title {
      font-size: 0.68rem;
      font-weight: 500;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: rgba(250,247,242,0.3);
      margin-bottom: 0.9rem;
    }

    .ticket-queue { display: flex; flex-direction: column; gap: 0.75rem; }

    .ticket {
      background: var(--surface);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1rem 1.2rem;
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: start;
      gap: 1rem;
      transition: border-color 0.15s;
    }

    .ticket:hover { border-color: var(--orange); }
    .ticket--urgent { border-left: 3px solid var(--red); }
    .ticket--normal { border-left: 3px solid var(--amber); }
    .ticket--done   { border-left: 3px solid var(--green); opacity: 0.55; }

    .ticket__table {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.5rem;
      font-weight: 300;
      line-height: 1;
      color: var(--cream);
    }

    .ticket__items { list-style: none; }

    .ticket__items li {
      font-size: 0.82rem;
      color: rgba(250,247,242,0.75);
      padding: 0.2rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.04);
      display: flex;
      justify-content: space-between;
    }

    .ticket__items li:last-child { border-bottom: none; }

    .ticket__qty {
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--gold);
      margin-left: 0.5rem;
    }

    .ticket__meta { text-align: right; }

    .ticket__time {
      font-size: 0.72rem;
      color: rgba(250,247,242,0.35);
      white-space: nowrap;
    }

    .ticket__elapsed {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--red);
      margin-top: 0.3rem;
    }

    .ticket__elapsed--ok { color: var(--green); }

    .inv-panel {
      background: var(--surface);
      backdrop-filter: blur(20px);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.2rem;
    }

    .inv-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.65rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      font-size: 0.82rem;
    }

    .inv-item:last-child { border-bottom: none; }

    .inv-item__name { color: rgba(250,247,242,0.75); }

    .inv-bar-wrap {
      width: 90px;
      height: 5px;
      background: rgba(255,255,255,0.1);
      border-radius: 3px;
      overflow: hidden;
      margin: 0 0.7rem;
      flex-shrink: 0;
    }

    .inv-bar {
      height: 100%;
      border-radius: 3px;
      transition: width 0.3s;
    }

    .inv-bar--ok   { background: var(--green); }
    .inv-bar--warn { background: var(--amber); }
    .inv-bar--low  { background: var(--red); }

    .inv-item__pct {
      font-size: 0.72rem;
      font-weight: 600;
      width: 2.5rem;
      text-align: right;
    }

    .inv-item__pct--ok   { color: var(--green); }
    .inv-item__pct--warn { color: var(--amber); }
    .inv-item__pct--low  { color: var(--red); }

    @media (max-width: 1000px) {
      .two-col { grid-template-columns: 1fr; }
    }

    @media (max-width: 860px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { display: none; }
      .stats { grid-template-columns: repeat(2, 1fr); }
      .topbar__meta { display: none; }
    }
  </style>
</head>
<body>

<div class="bg-layer"></div>

<div class="layout">
  <header class="topbar">
    <div class="topbar__brand">FoodFlow <em>-</em> Kitchen</div>
    <div class="topbar__right">
      <span class="topbar__meta"><?= htmlspecialchars($station) ?></span>
      <div class="topbar__actions">
        <a href="../auth/change_password.php" class="topbar__link">Change Password</a>
      </div>
      <div class="avatar">CM</div>
    </div>
  </header>

  <nav class="sidebar">
    <span class="sidebar__label">Kitchen</span>
    <a href="#" class="nav-item active">
      <span class="nav-item__icon">🍽️</span> Ticket Queue
      <span class="badge"><?= (int)$active_tickets ?></span>
    </a>
    <form method="POST" style="margin:0;">
      <button type="submit" name="send_low_stock_alerts" value="1" class="nav-item nav-item--button">
        <span class="nav-item__icon">⚠️</span> Low Stock Alert
        <span class="badge"><?= (int)$low_stock_items ?></span>
      </button>
    </form>

    <span class="sidebar__label">Planning</span>
    <a href="chef_inventory.php" class="nav-item">
      <span class="nav-item__icon">📦</span> Inventory Console
    </a>
    <a href="open_menu.php" class="nav-item">
      <span class="nav-item__icon">📋</span> Today's Menu
    </a>

    <span class="sidebar__label">System</span>
    <a href="../auth/change_password.php" class="nav-item">
      <span class="nav-item__icon">🔐</span> Change Password
    </a>
    <a href="../auth/logout.php" class="nav-item">
      <span class="nav-item__icon">⎋</span> Sign Out
    </a>
  </nav>

  <main>
    <div class="page-header">
      <h1>Kitchen - Live View</h1>
      <p><?= htmlspecialchars($chef_name) ?> - <?= date('l, d F Y - H:i') ?></p>
      <?php if ($login_success_message !== ''): ?>
        <p style="margin-top:10px;color:#BDECCB;"><?= htmlspecialchars($login_success_message) ?></p>
      <?php endif; ?>
      <?php if ($message !== ''): ?>
        <p style="margin-top:10px;color:#BDECCB;"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-card__label">Active Tickets</div>
        <div class="stat-card__val"><?= (int)$active_tickets ?></div>
        <div class="stat-card__sub">Pending + Preparing</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Served Today</div>
        <div class="stat-card__val"><?= (int)$served_today ?></div>
        <div class="stat-card__sub">Completed orders today</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Pending Orders</div>
        <div class="stat-card__val"><?= (int)$pending_orders ?></div>
        <div class="stat-card__sub">Awaiting kitchen prep</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Low Stock Items</div>
        <div class="stat-card__val"><?= (int)$low_stock_items ?></div>
        <div class="stat-card__sub">At or below reorder level</div>
      </div>
    </div>

    <div class="card" style="margin-bottom:18px;background:rgba(10, 7, 4, 0.70);border:1px solid rgba(255,255,255,0.09);border-radius:8px;padding:1rem;">
      <h3 style="margin-bottom:8px;color:#BDECCB;">New Order Alerts (Waiter -> Kitchen): <?= (int)$new_order_alerts_count ?></h3>
      <?php if ($new_order_alerts && $new_order_alerts->num_rows > 0): ?>
        <?php while ($alert = $new_order_alerts->fetch_assoc()): ?>
          <?php $alertItems = array_filter(explode('|', (string)$alert['items'])); ?>
          <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);gap:10px;">
            <div>
              <strong><?php echo htmlspecialchars((string)$alert['order_number']); ?></strong>
              <span style="margin-left:6px;">Table <?php echo htmlspecialchars((string)$alert['table_number']); ?></span>
              <span style="margin-left:6px;padding:1px 8px;border:1px solid rgba(255,255,255,0.18);border-radius:12px;font-size:12px;">
                <?php echo htmlspecialchars(ucfirst((string)($alert['status'] ?? 'pending'))); ?>
              </span>
              <span style="margin-left:6px;color:rgba(250,247,242,0.6);"><?php echo date('H:i', strtotime((string)$alert['created_at'])); ?></span>
              <div style="margin-top:6px;font-size:13px;color:rgba(250,247,242,0.85);">
                <?php if (count($alertItems) > 0): ?>
                  <?php echo htmlspecialchars(implode(' | ', $alertItems)); ?>
                <?php else: ?>
                  Items not listed yet.
                <?php endif; ?>
              </div>
            </div>
            <form method="POST" style="margin:0;display:flex;gap:6px;">
              <input type="hidden" name="alert_id" value="<?php echo (int)$alert['id']; ?>">
              <input type="hidden" name="order_id" value="<?php echo (int)$alert['order_id']; ?>">
              <input type="hidden" name="set_order_status" value="1">
              <button type="submit" name="status_target" value="pending" class="btn btn-warning">Set Pending</button>
              <button type="submit" name="status_target" value="preparing" class="btn btn-primary">Set Preparing</button>
              <button type="submit" name="status_target" value="served" class="btn btn-success">Set Served</button>
            </form>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="margin:0;">No new order alerts.</p>
      <?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:18px;background:rgba(10, 7, 4, 0.70);border:1px solid rgba(255,255,255,0.09);border-radius:8px;padding:1rem;">
      <h3 style="margin-bottom:10px;color:#BDECCB;">Low Stock and Shelf-Life Notes (Chef -> Manager)</h3>
      <form method="POST" style="display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;align-items:end;">
        <input id="stock-note-ingredient-name" type="text" name="ingredient_name" list="stock-note-ingredient-list" placeholder="Type stock item name (new or existing)">
        <datalist id="stock-note-ingredient-list">
          <?php if ($stock_note_ingredients && $stock_note_ingredients->num_rows > 0): ?>
            <?php mysqli_data_seek($stock_note_ingredients, 0); ?>
            <?php while ($ingList = $stock_note_ingredients->fetch_assoc()): ?>
              <option value="<?= htmlspecialchars((string)$ingList['name']) ?>"></option>
            <?php endwhile; ?>
            <?php mysqli_data_seek($stock_note_ingredients, 0); ?>
          <?php endif; ?>
        </datalist>
        <select id="stock-note-ingredient" name="ingredient_id">
          <option value="">Select existing ingredient (optional if typed above)</option>
          <?php if ($stock_note_ingredients && $stock_note_ingredients->num_rows > 0): ?>
            <?php while ($ing = $stock_note_ingredients->fetch_assoc()): ?>
              <option
                value="<?= (int)$ing['id'] ?>"
                data-name="<?= htmlspecialchars((string)$ing['name']) ?>"
                data-unit="<?= htmlspecialchars((string)$ing['unit']) ?>"
                data-current="<?= number_format((float)$ing['current_stock'], 2, '.', '') ?>"
                data-reorder="<?= number_format((float)$ing['reorder_level'], 2, '.', '') ?>"
              >
                <?= htmlspecialchars((string)$ing['name']) ?> (stock <?= number_format((float)$ing['current_stock'], 2) ?> <?= htmlspecialchars((string)$ing['unit']) ?> / reorder <?= number_format((float)$ing['reorder_level'], 2) ?>)
              </option>
            <?php endwhile; ?>
          <?php endif; ?>
        </select>
        <input id="observed-stock" type="number" step="0.01" min="0" name="observed_stock" placeholder="Observed stock" required>
        <input id="reorder-snapshot" type="number" step="0.01" min="0" name="reorder_level_snapshot" placeholder="Reorder level snapshot" required>
        <input id="ingredient-unit" type="text" name="ingredient_unit" placeholder="Unit (e.g. kg, liters, pcs)">
        <div style="display:flex;flex-direction:column;gap:4px;">
          <input id="suggested-restock" type="number" step="0.01" min="0" name="suggested_restock_amount" placeholder="Suggested amount needed">
          <small id="suggested-restock-hint" style="color:rgba(250,247,242,0.72);">Suggestion updates from observed vs reorder levels.</small>
        </div>
        <select name="urgency" required>
          <option value="normal">Normal</option>
          <option value="watch" selected>Watch</option>
          <option value="urgent">Urgent</option>
        </select>
        <input type="number" step="1" min="0" name="shelf_life_days" placeholder="Shelf life (days)">
        <input type="date" name="expected_expiry_date" placeholder="Expected expiry date">
        <input type="text" name="chef_comment" maxlength="300" placeholder="Comment for manager (restock, quality, expiry risk)" style="grid-column: span 2;" required>
        <button type="submit" name="submit_stock_note" value="1" class="btn btn-success">Send Note to Manager</button>
      </form>
      <p style="margin-top:8px;color:rgba(250,247,242,0.72);font-size:13px;">Chef can type any stock item name. If it does not exist yet, the system creates it automatically for manager follow-up and autofill.</p>

      <div style="margin-top:12px;">
        <h4 style="margin-bottom:8px;color:#FAF7F2;">Recent Kitchen Stock Notes</h4>
        <?php if ($recent_stock_notes && $recent_stock_notes->num_rows > 0): ?>
          <?php while ($note = $recent_stock_notes->fetch_assoc()): ?>
            <?php
              $urgencyLabel = strtoupper((string)$note['urgency']);
              $daysToExpiry = $note['days_to_expiry'];
              $expiryText = 'No expiry date';
              if ($note['expected_expiry_date']) {
                if ($daysToExpiry !== null && (int)$daysToExpiry < 0) {
                  $expiryText = 'Expired ' . abs((int)$daysToExpiry) . ' day(s) ago';
                } elseif ($daysToExpiry !== null) {
                  $expiryText = 'Expires in ' . (int)$daysToExpiry . ' day(s)';
                } else {
                  $expiryText = 'Expiry: ' . htmlspecialchars((string)$note['expected_expiry_date']);
                }
              }
            ?>
            <div style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.08);">
              <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                <strong><?= htmlspecialchars((string)$note['ingredient_name']) ?></strong>
                <span class="status <?= ((string)$note['urgency'] === 'urgent') ? 'status-cancelled' : (((string)$note['urgency'] === 'watch') ? 'status-pending' : 'status-served') ?>"><?= htmlspecialchars($urgencyLabel) ?></span>
              </div>
              <div style="font-size:13px;color:rgba(250,247,242,0.78);margin-top:4px;">
                Stock <?= number_format((float)$note['observed_stock'], 2) ?> <?= htmlspecialchars((string)$note['unit']) ?> |
                Reorder <?= number_format((float)$note['reorder_level_snapshot'], 2) ?> |
                Suggested restock <?= number_format((float)($note['suggested_restock_amount'] ?? 0), 2) ?> <?= htmlspecialchars((string)$note['unit']) ?> |
                <?= $expiryText ?>
              </div>
              <div style="font-size:13px;color:rgba(250,247,242,0.86);margin-top:4px;"><?= htmlspecialchars((string)$note['comment']) ?></div>
              <div style="font-size:12px;color:rgba(250,247,242,0.52);margin-top:3px;">
                <?= date('Y-m-d H:i', strtotime((string)$note['created_at'])) ?> |
                <?php if ((int)$note['is_acknowledged'] === 1): ?>
                  Acknowledged by <?= htmlspecialchars((string)($note['ack_manager_name'] ?? 'manager')) ?>
                  <?php if (!empty($note['acknowledged_at'])): ?>
                    at <?= date('Y-m-d H:i', strtotime((string)$note['acknowledged_at'])) ?>
                  <?php endif; ?>
                <?php else: ?>
                  Waiting manager review
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="margin:0;">No stock notes yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="two-col">
      <div>
        <p class="section-title">Active Ticket Queue</p>
        <div class="ticket-queue">
          <?php if ($tickets && $tickets->num_rows > 0): ?>
            <?php while ($t = $tickets->fetch_assoc()): ?>
              <?php
                $elapsedMins = max(0, (int)floor((time() - strtotime((string)$t['created_at'])) / 60));
                $isUrgent = ((string)$t['status'] === 'pending' && $elapsedMins >= 20);
                $cls = $isUrgent ? 'ticket--urgent' : 'ticket--normal';
                $elapsedCls = $isUrgent ? 'ticket__elapsed' : 'ticket__elapsed ticket__elapsed--ok';
                $itemLines = array_filter(explode('|', (string)$t['items']));
              ?>
              <div class="ticket <?= htmlspecialchars($cls) ?>">
                <div class="ticket__table"><?= htmlspecialchars((string)$t['table_number']) ?></div>
                <div style="grid-column: 2 / 4; display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:4px;">
                  <div style="font-size:0.76rem; color: rgba(250,247,242,0.58);">
                    Order <?= htmlspecialchars((string)$t['order_number']) ?>
                  </div>
                  <div style="font-size:0.72rem; padding:1px 8px; border-radius:12px; border:1px solid rgba(255,255,255,0.18); color:rgba(250,247,242,0.85);">
                    <?= htmlspecialchars(strtoupper((string)$t['status'])) ?>
                  </div>
                </div>
                <ul class="ticket__items">
                  <?php if (count($itemLines) > 0): ?>
                    <?php foreach ($itemLines as $line): ?>
                      <li><?= htmlspecialchars($line) ?></li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li>No items listed</li>
                  <?php endif; ?>
                </ul>
                <div class="ticket__meta">
                  <div class="ticket__time"><?= date('H:i', strtotime((string)$t['created_at'])) ?></div>
                  <div class="<?= htmlspecialchars($elapsedCls) ?>"><?= (int)$elapsedMins ?> min</div>
                </div>
                <div style="grid-column: 1 / -1; display:flex; gap:6px; margin-top:8px; flex-wrap:wrap;">
                  <form method="POST" style="margin:0; display:flex; gap:6px; flex-wrap:wrap;">
                    <input type="hidden" name="order_id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="set_order_status" value="1">
                    <button type="submit" name="status_target" value="pending" class="btn btn-warning">Set Pending</button>
                    <button type="submit" name="status_target" value="preparing" class="btn btn-primary">Set Preparing</button>
                    <button type="submit" name="status_target" value="served" class="btn btn-success">Set Served</button>
                  </form>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="ticket ticket--done">
              <div class="ticket__table">-</div>
              <ul class="ticket__items">
                <li>No active tickets</li>
              </ul>
              <div class="ticket__meta">
                <div class="ticket__time">Now</div>
                <div class="ticket__elapsed ticket__elapsed--ok">Clear</div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <p class="section-title">Inventory - Live Levels</p>
        <div class="inv-panel">
          <?php if ($inventory && $inventory->num_rows > 0): ?>
            <?php while ($i = $inventory->fetch_assoc()): ?>
              <?php
                $stock = (float)$i['current_stock'];
                $reorder = (float)$i['reorder_level'];
                $pct = $reorder > 0 ? (int)round(min(100, ($stock / $reorder) * 100)) : 100;
                if ($pct < 35) {
                    $state = 'low';
                } elseif ($pct < 70) {
                    $state = 'warn';
                } else {
                    $state = 'ok';
                }
              ?>
              <div class="inv-item">
                <span class="inv-item__name"><?= htmlspecialchars((string)$i['name']) ?></span>
                <div class="inv-bar-wrap">
                  <div class="inv-bar inv-bar--<?= htmlspecialchars($state) ?>" style="width:<?= (int)$pct ?>%"></div>
                </div>
                <span class="inv-item__pct inv-item__pct--<?= htmlspecialchars($state) ?>"><?= (int)$pct ?>%</span>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="inv-item">
              <span class="inv-item__name">No ingredients found</span>
              <span class="inv-item__pct inv-item__pct--ok">0%</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<script>
  (function () {
    const ingredientNameInput = document.getElementById('stock-note-ingredient-name');
    const ingredientSelect = document.getElementById('stock-note-ingredient');
    const unitInput = document.getElementById('ingredient-unit');
    const observedInput = document.getElementById('observed-stock');
    const reorderInput = document.getElementById('reorder-snapshot');
    const suggestedInput = document.getElementById('suggested-restock');
    const hint = document.getElementById('suggested-restock-hint');

    function parseNum(value) {
      const n = parseFloat(value || '0');
      return Number.isFinite(n) ? n : 0;
    }

    function selectedMeta() {
      if (!ingredientSelect) {
        return { unit: 'units', reorder: 0 };
      }
      const opt = ingredientSelect.options[ingredientSelect.selectedIndex];
      return {
        unit: (opt && opt.dataset && opt.dataset.unit) ? opt.dataset.unit : 'units',
        reorder: (opt && opt.dataset && opt.dataset.reorder) ? parseNum(opt.dataset.reorder) : 0
      };
    }

    function updateSuggestion(force) {
      if (!suggestedInput || !observedInput || !reorderInput) {
        return;
      }

      const meta = selectedMeta();
      const observed = parseNum(observedInput.value);
      const reorder = parseNum(reorderInput.value);
      const suggestion = Math.max(0, reorder - observed);

      if (force || suggestedInput.dataset.manual !== '1') {
        suggestedInput.value = suggestion.toFixed(2);
      }

      if (hint) {
        hint.textContent = 'Suggested amount needed: ' + suggestion.toFixed(2) + ' ' + meta.unit;
      }
    }

    if (ingredientSelect) {
      ingredientSelect.addEventListener('change', function () {
        const meta = selectedMeta();
        const opt = ingredientSelect.options[ingredientSelect.selectedIndex];
        if (ingredientNameInput && opt && opt.dataset && opt.dataset.name) {
          ingredientNameInput.value = opt.dataset.name;
        }
        if (unitInput && opt && opt.dataset && opt.dataset.unit) {
          unitInput.value = opt.dataset.unit;
        }
        if (reorderInput && (reorderInput.value === '' || parseNum(reorderInput.value) <= 0)) {
          reorderInput.value = meta.reorder.toFixed(2);
        }
        if (suggestedInput) {
          suggestedInput.dataset.manual = '0';
        }
        updateSuggestion(true);
      });
    }

    if (ingredientNameInput) {
      ingredientNameInput.addEventListener('input', function () {
        if (!ingredientSelect) {
          return;
        }
        const typed = (ingredientNameInput.value || '').trim().toLowerCase();
        if (typed === '') {
          ingredientSelect.value = '';
          return;
        }
        let matched = false;
        for (let i = 0; i < ingredientSelect.options.length; i++) {
          const opt = ingredientSelect.options[i];
          const optName = ((opt.dataset && opt.dataset.name) ? opt.dataset.name : opt.text || '').trim().toLowerCase();
          if (optName === typed) {
            ingredientSelect.value = opt.value;
            if (unitInput && opt.dataset && opt.dataset.unit) {
              unitInput.value = opt.dataset.unit;
            }
            matched = true;
            break;
          }
        }
        if (!matched) {
          ingredientSelect.value = '';
        }
      });
    }

    if (observedInput) {
      observedInput.addEventListener('input', function () {
        updateSuggestion(false);
      });
    }

    if (reorderInput) {
      reorderInput.addEventListener('input', function () {
        updateSuggestion(false);
      });
    }

    if (suggestedInput) {
      suggestedInput.addEventListener('input', function () {
        suggestedInput.dataset.manual = suggestedInput.value.trim() === '' ? '0' : '1';
      });
    }

    updateSuggestion(true);
  })();

  (function () {
    const refreshMs = 15000;
    setInterval(function () {
      const active = document.activeElement;
      const isEditing = active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);
      if (!document.hidden && !isEditing) {
        window.location.reload();
      }
    }, refreshMs);
  })();
</script>
</body>
</html>
