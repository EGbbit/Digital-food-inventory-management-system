<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
	header('Location: ../auth/login.php');
	exit;
}

$role = strtolower(trim((string)$_SESSION['role']));

$roleRoutes = [
	'admin' => '../admin/admin_dashboard.php',
	'manager' => 'manager_controls.php',
	'chef' => 'chef_dashboard.php',
	'waiter' => 'waiter_dashboard.php',
];

if (isset($roleRoutes[$role])) {
	header('Location: ' . $roleRoutes[$role]);
	exit;
}

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Access Denied</title>
	<style>
		body {
			margin: 0;
			font-family: Arial, sans-serif;
			background: #f5f7fb;
			display: grid;
			place-items: center;
			min-height: 100vh;
			color: #1f2937;
		}
		.card {
			width: min(560px, 92vw);
			background: #ffffff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 22px;
			box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
		}
		h1 {
			margin: 0 0 10px;
			font-size: 24px;
		}
		p {
			margin: 0 0 10px;
			line-height: 1.5;
		}
		a {
			color: #0d6efd;
			text-decoration: none;
			font-weight: 600;
		}
	</style>
</head>
<body>
	<section class="card">
		<h1>Access Denied</h1>
		<p>Your account role is not recognized by this system.</p>
		<p>Signed in as: <strong><?php echo htmlspecialchars((string)($_SESSION['user_name'] ?? 'Unknown User')); ?></strong></p>
		<p>Role value: <strong><?php echo htmlspecialchars((string)$_SESSION['role']); ?></strong></p>
		<p><a href="../auth/logout.php">Log out</a> and sign in with a valid account.</p>
	</section>
</body>
</html>
