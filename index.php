<?php
// index.php - FoodFlow Landing Page
require_once __DIR__ . '/core/auth.php';

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['role']);
$dashboardLink = 'auth/login.php';

if ($isLoggedIn) {
    if ($_SESSION['role'] === 'admin') {
        $dashboardLink = 'admin/admin_dashboard.php';
    } elseif ($_SESSION['role'] === 'manager') {
        $dashboardLink = 'roles/manager_dashboard.php';
    } elseif ($_SESSION['role'] === 'waiter') {
        $dashboardLink = 'roles/waiter_dashboard.php';
    } else {
        $dashboardLink = 'roles/chef_dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodFlow | Digital Food Inventory Management System</title>
    <style>
        :root {
            --ink: #f3f7ef;
            --accent: #f0b429;
            --accent-soft: #f8e3ac;
            --glass: rgba(10, 24, 29, 0.58);
            --glass-border: rgba(255, 255, 255, 0.26);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            color: var(--ink);
            background:
                linear-gradient(120deg, rgba(8, 20, 25, 0.92) 5%, rgba(19, 38, 45, 0.65) 45%, rgba(25, 45, 32, 0.68) 100%),
                url('Assets/homepage.jpg') center/cover no-repeat fixed;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: rgba(14, 32, 40, 0.7);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(4px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
        }

        .navbar-brand {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .navbar-links a {
            color: var(--ink);
            text-decoration: none;
            font-weight: 700;
            background: rgba(240, 180, 41, 0.95);
            padding: 10px 18px;
            border-radius: 999px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .navbar-links {
            display: flex;
            gap: 0.7rem;
            align-items: center;
        }

        .navbar-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(240, 180, 41, 0.3);
        }

        .hero {
            margin: 3rem auto;
            width: min(1100px, 92%);
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.35);
            animation: liftIn 0.7s ease;
        }

        .hero h1 {
            color: var(--accent-soft);
            font-size: clamp(2rem, 4vw, 3rem);
            margin-bottom: 0.85rem;
        }

        .hero p {
            color: #e4eee2;
            font-size: 1.08rem;
            max-width: 700px;
            line-height: 1.7;
            margin-bottom: 1.6rem;
        }

        .access-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(140px, 1fr));
            gap: 0.9rem;
        }

        .role-access {
            text-decoration: none;
            text-align: center;
            color: #132322;
            font-weight: 700;
            border-radius: 12px;
            padding: 13px 10px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: transform 0.24s ease, box-shadow 0.24s ease;
        }

        .role-access:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
        }

        .access-admin {
            background: linear-gradient(135deg, #ff8f8f 0%, #ffd0d0 100%);
        }

        .access-manager {
            background: linear-gradient(135deg, #ffd56a 0%, #fff3ce 100%);
        }

        .access-chef {
            background: linear-gradient(135deg, #b8efb2 0%, #e6ffe1 100%);
        }

        .access-waiter {
            background: linear-gradient(135deg, #8cd9ff 0%, #d8f3ff 100%);
        }

        @keyframes liftIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 0.9rem 1rem;
            }

            .hero {
                margin-top: 1.5rem;
                padding: 1.4rem;
            }

            .access-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">FoodFlow</div>
        <div class="navbar-links">
            <?php if ($isLoggedIn): ?>
                <a href="<?php echo htmlspecialchars($dashboardLink); ?>">My Dashboard</a>
                <a href="auth/logout.php">Logout</a>
            <?php else: ?>
                <a href="auth/login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <h1>Welcome to FoodFlow</h1>
        <p>Track stock in real time, reduce wastage, and keep kitchen operations coordinated across admin, waiter, chef, and manager teams. Login then proceed to your dashboard.</p>

        <div class="access-grid">
            <?php if ($isLoggedIn): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin/admin_dashboard.php" class="role-access access-admin">Admin Dashboard</a>
                    <a href="admin/manage_users.php" class="role-access access-manager">Manage Users</a>
                    <a href="admin/system_audit.php" class="role-access access-chef">System Audit</a>
                    <a href="auth/logout.php" class="role-access access-waiter">Logout</a>
                <?php elseif ($_SESSION['role'] === 'manager'): ?>
                    <a href="roles/manager_dashboard.php" class="role-access access-admin">Manager Dashboard</a>
                    <a href="roles/manager_reports.php" class="role-access access-manager">View Reports</a>
                    <a href="roles/ingredients.php" class="role-access access-chef">Ingredients</a>
                    <a href="auth/logout.php" class="role-access access-waiter">Logout</a>
                <?php elseif ($_SESSION['role'] === 'chef'): ?>
                    <a href="roles/chef_dashboard.php" class="role-access access-admin">Chef Dashboard</a>
                    <a href="roles/chef_inventory.php" class="role-access access-manager">Inventory Console</a>
                    <a href="roles/open_menu.php" class="role-access access-chef">Open Menu</a>
                    <a href="auth/logout.php" class="role-access access-waiter">Logout</a>
                <?php else: ?>
                    <a href="roles/waiter_dashboard.php" class="role-access access-admin">Waiter Dashboard</a>
                    <a href="roles/waiter_orders.php" class="role-access access-manager">Record Orders</a>
                    <a href="roles/open_menu.php" class="role-access access-chef">Open Menu</a>
                    <a href="auth/logout.php" class="role-access access-waiter">Logout</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="auth/login.php?role=admin" class="role-access access-admin">Admin Access</a>
                <a href="auth/login.php?role=manager" class="role-access access-manager">Manager Access</a>
                <a href="auth/login.php?role=chef" class="role-access access-chef">Chef Access</a>
                <a href="auth/login.php?role=waiter" class="role-access access-waiter">Waiter Access</a>
            <?php endif; ?>
        </div>
    </section>

</body>
</html>
