<?php
require_once __DIR__ . '/../core/auth.php';
require_role('waiter');

$servername = 'localhost';
$username = 'root';
$password = '1234';
$dbname = 'food_inventory';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$waiterId = (int)$_SESSION['user_id'];
$waiterName = !empty($_SESSION['user_name']) ? (string)$_SESSION['user_name'] : 'Waiter';

$todayOrders = (int)$conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiterId AND DATE(created_at)=CURDATE()")->fetch_assoc()['count'];
$pendingOrders = (int)$conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiterId AND DATE(created_at)=CURDATE() AND status='pending'")->fetch_assoc()['count'];
$preparingOrders = (int)$conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiterId AND DATE(created_at)=CURDATE() AND status='preparing'")->fetch_assoc()['count'];
$servedOrders = (int)$conn->query("SELECT COUNT(*) AS count FROM orders WHERE waiter_id = $waiterId AND DATE(created_at)=CURDATE() AND status='served'")->fetch_assoc()['count'];

$recentOrders = $conn->query("SELECT order_number, table_number, status, total_amount, created_at
    FROM orders
    WHERE waiter_id = $waiterId
    ORDER BY created_at DESC
    LIMIT 8");

$openTables = $conn->query("SELECT table_number, COUNT(*) AS total
    FROM orders
    WHERE waiter_id = $waiterId
      AND status IN ('pending', 'preparing')
    GROUP BY table_number
    ORDER BY total DESC, table_number ASC
    LIMIT 6");

$station = 'Floor Service - Dining';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="refresh" content="20" />
  <title>Waiter Dashboard - FoodFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --blue: #3A8DD8;
      --blue-lt: #8CC5F4;
      --cream: #F7FAFF;
      --surface: rgba(6, 22, 38, 0.69);
      --surface-hover: rgba(7, 29, 49, 0.86);
      --border: rgba(255,255,255,0.11);
      --green: #4CAF72;
      --amber: #E8A830;
      --red: #E05252;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', sans-serif;
      background: #081522;
      color: var(--cream);
    }

    .bg-layer {
      position: fixed;
      inset: 0;
      background-image: url('../Assets/waiter.jpg');
      background-size: cover;
      background-position: center;
      z-index: 0;
    }

    .bg-layer::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(155deg,
          rgba(4, 11, 18, 0.92) 0%,
          rgba(6, 27, 46, 0.70) 52%,
          rgba(3, 10, 16, 0.90) 100%);
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
      justify-content: space-between;
      align-items: center;
      padding: 1rem 2rem;
      background: rgba(4, 14, 24, 0.66);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--border);
    }

    .topbar__brand {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.5rem;
      font-weight: 300;
      letter-spacing: 0.08em;
    }

    .topbar__brand em { font-style: normal; color: var(--blue-lt); }

    .topbar__right {
      display: flex;
      align-items: center;
      gap: 1.1rem;
    }

    .topbar__meta {
      font-size: 0.72rem;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: rgba(247,250,255,0.47);
    }

    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--blue);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.76rem;
      font-weight: 600;
    }

    .sidebar {
      padding: 1.6rem 1rem;
      border-right: 1px solid var(--border);
      background: rgba(4, 15, 27, 0.54);
      backdrop-filter: blur(16px);
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .sidebar__label {
      font-size: 0.6rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: rgba(247,250,255,0.28);
      padding: 0.85rem 0.8rem 0.35rem;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.66rem 0.8rem;
      border-radius: 6px;
      font-size: 0.81rem;
      color: rgba(247,250,255,0.65);
      text-decoration: none;
      border: 1px solid transparent;
      transition: background 0.16s, color 0.16s, border-color 0.16s;
    }

    .nav-item:hover { background: rgba(255,255,255,0.05); color: #fff; }

    .nav-item.active {
      background: rgba(58,141,216,0.16);
      color: #BBDDFF;
      border-color: rgba(140,197,244,0.36);
    }

    main { padding: 2rem 2.2rem; overflow-y: auto; }

    .page-header {
      margin-bottom: 1.8rem;
    }

    .page-header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2rem;
      font-weight: 300;
    }

    .page-header p {
      font-size: 0.8rem;
      color: rgba(247,250,255,0.45);
      margin-top: 0.34rem;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.15rem;
      backdrop-filter: blur(18px);
    }

    .stat-card__label {
      font-size: 0.66rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(247,250,255,0.44);
      margin-bottom: 0.56rem;
    }

    .stat-card__val {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2.14rem;
      font-weight: 300;
      line-height: 1;
    }

    .stat-card__sub {
      font-size: 0.72rem;
      margin-top: 0.34rem;
      color: var(--blue-lt);
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 1.35rem;
    }

    .section-title {
      font-size: 0.68rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: rgba(247,250,255,0.34);
      margin-bottom: 0.85rem;
    }

    .list-stack {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .order-row {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1rem 1.12rem;
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 1rem;
      align-items: center;
      transition: background 0.16s, border-color 0.16s;
    }

    .order-row:hover {
      background: var(--surface-hover);
      border-color: rgba(140,197,244,0.4);
    }

    .order-row__table {
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.44rem;
      font-weight: 300;
      min-width: 56px;
    }

    .order-row__title {
      font-size: 0.84rem;
      color: rgba(247,250,255,0.9);
      margin-bottom: 0.2rem;
    }

    .order-row__meta {
      font-size: 0.75rem;
      color: rgba(247,250,255,0.45);
    }

    .status {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 600;
      border-radius: 999px;
      padding: 0.28rem 0.62rem;
      border: 1px solid transparent;
      white-space: nowrap;
    }

    .status-pending {
      color: #FFDFA2;
      background: rgba(232,168,48,0.17);
      border-color: rgba(232,168,48,0.35);
    }

    .status-preparing {
      color: #BBDFFF;
      background: rgba(58,141,216,0.16);
      border-color: rgba(58,141,216,0.34);
    }

    .status-served {
      color: #ACE6BE;
      background: rgba(76,175,114,0.15);
      border-color: rgba(76,175,114,0.35);
    }

    .status-cancelled {
      color: #FFC7C7;
      background: rgba(224,82,82,0.16);
      border-color: rgba(224,82,82,0.35);
    }

    .side-panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 1.18rem;
      backdrop-filter: blur(18px);
      margin-bottom: 0.95rem;
    }

    .side-list {
      list-style: none;
    }

    .side-list li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.58rem 0;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      font-size: 0.8rem;
    }

    .side-list li:last-child { border-bottom: none; }

    .actions {
      display: flex;
      flex-direction: column;
      gap: 0.7rem;
      margin-top: 0.8rem;
    }

    .action-btn {
      text-decoration: none;
      color: #fff;
      font-size: 0.8rem;
      text-align: center;
      border: 1px solid rgba(255,255,255,0.19);
      border-radius: 7px;
      padding: 0.58rem 0.65rem;
      background: rgba(255,255,255,0.06);
      transition: background 0.16s;
    }

    .action-btn:hover { background: rgba(255,255,255,0.13); }

    @media (max-width: 1000px) {
      .two-col { grid-template-columns: 1fr; }
    }

    @media (max-width: 860px) {
      .layout { grid-template-columns: 1fr; }
      .sidebar { display: none; }
      .stats { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
<div class="bg-layer"></div>

<div class="layout">
  <header class="topbar">
    <div class="topbar__brand">FoodFlow <em>-</em> Service</div>
    <div class="topbar__right">
      <span class="topbar__meta"><?= htmlspecialchars($station) ?></span>
      <div class="avatar">WV</div>
    </div>
  </header>

  <nav class="sidebar">
    <span class="sidebar__label">Service</span>
    <a href="waiter_dashboard.php" class="nav-item active">Overview</a>
    <a href="waiter_orders.php" class="nav-item">Record Orders</a>
    <a href="open_menu.php" class="nav-item">Open Food Menu</a>

    <span class="sidebar__label">System</span>
    <a href="../auth/change_password.php" class="nav-item">Change Password</a>
    <a href="../auth/logout.php" class="nav-item">Sign Out</a>
  </nav>

  <main>
    <div class="page-header">
      <h1>Floor Service - Live View</h1>
      <p><?= htmlspecialchars($waiterName) ?> - <?= date('l, d F Y - H:i') ?></p>
    </div>

    <div class="stats">
      <div class="stat-card">
        <div class="stat-card__label">Today's Orders</div>
        <div class="stat-card__val"><?= $todayOrders ?></div>
        <div class="stat-card__sub">All tables</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Pending</div>
        <div class="stat-card__val"><?= $pendingOrders ?></div>
        <div class="stat-card__sub">Awaiting kitchen</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Preparing</div>
        <div class="stat-card__val"><?= $preparingOrders ?></div>
        <div class="stat-card__sub">In progress</div>
      </div>
      <div class="stat-card">
        <div class="stat-card__label">Served</div>
        <div class="stat-card__val"><?= $servedOrders ?></div>
        <div class="stat-card__sub">Completed</div>
      </div>
    </div>

    <div class="two-col">
      <div>
        <p class="section-title">Recent Orders</p>
        <div class="list-stack">
          <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
            <?php while ($order = $recentOrders->fetch_assoc()): ?>
              <?php
                $status = (string)$order['status'];
                $statusClass = 'status-' . $status;
              ?>
              <div class="order-row">
                <div class="order-row__table">T<?= htmlspecialchars((string)$order['table_number']) ?></div>
                <div>
                  <div class="order-row__title"><?= htmlspecialchars((string)$order['order_number']) ?> - Kshs. <?= number_format((float)$order['total_amount'], 2) ?></div>
                  <div class="order-row__meta">Created <?= date('H:i', strtotime((string)$order['created_at'])) ?></div>
                </div>
                <span class="status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="order-row">
              <div class="order-row__table">-</div>
              <div>
                <div class="order-row__title">No orders yet</div>
                <div class="order-row__meta">Create an order to begin service tracking</div>
              </div>
              <span class="status status-pending">Idle</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <p class="section-title">Open Tables</p>
        <div class="side-panel">
          <ul class="side-list">
            <?php if ($openTables && $openTables->num_rows > 0): ?>
              <?php while ($table = $openTables->fetch_assoc()): ?>
                <li>
                  <span>Table <?= htmlspecialchars((string)$table['table_number']) ?></span>
                  <strong><?= (int)$table['total'] ?> open</strong>
                </li>
              <?php endwhile; ?>
            <?php else: ?>
              <li>
                <span>No active tables</span>
                <strong>0</strong>
              </li>
            <?php endif; ?>
          </ul>
        </div>

        <p class="section-title">Quick Actions</p>
        <div class="side-panel">
          <div class="actions">
            <a class="action-btn" href="waiter_orders.php">Record New Order</a>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
