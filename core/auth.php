<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
}

function require_role(string $role): void
{
    require_login();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        if (isset($_SESSION['role'])) {
            redirect_by_role((string)$_SESSION['role']);
        }
        header('Location: ../auth/login.php');
        exit();
    }
}

function redirect_by_role(string $role): void
{
    if ($role === 'admin') {
        header('Location: ../admin/admin_dashboard.php');
    } elseif ($role === 'manager') {
        header('Location: ../roles/manager_dashboard.php');
    } elseif ($role === 'waiter') {
        header('Location: ../roles/waiter_dashboard.php');
    } else {
        header('Location: ../roles/chef_dashboard.php');
    }
    exit();
}
