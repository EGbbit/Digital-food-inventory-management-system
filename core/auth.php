<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header('Location: ../auth/login.php');
        exit;
    }
}

function require_role(string $requiredRole): void
{
    require_login();

    $currentRole = strtolower((string)($_SESSION['role'] ?? ''));
    if ($currentRole !== strtolower($requiredRole)) {
        if (isset($_SESSION['role'])) {
            redirect_by_role((string)$_SESSION['role']);
        }
        header('Location: ../auth/login.php');
        exit;
    }
}

function redirect_by_role(string $role): void
{
    $role = strtolower(trim($role));

    switch ($role) {
        case 'admin':
            header('Location: ../admin/admin_dashboard.php');
            break;
        case 'manager':
            header('Location: ../roles/manager_controls.php');
            break;
        case 'chef':
            header('Location: ../roles/chef_dashboard.php');
            break;
        case 'waiter':
            header('Location: ../roles/waiter_dashboard.php');
            break;
        default:
            header('Location: ../auth/login.php');
            break;
    }
    exit;
}
