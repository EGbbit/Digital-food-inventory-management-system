<?php
require_once __DIR__ . '/../core/auth.php';
require_role('admin');

$conn = new mysqli('localhost', 'root', '1234', 'food_inventory');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$backupDir = __DIR__ . '/core/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0777, true);
}

function generate_sql_backup(mysqli $conn): string
{
    $sql = "-- FoodFlow database backup\n";
    $sql .= '-- Generated at: ' . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = [];
    $tablesRs = $conn->query('SHOW TABLES');
    while ($row = $tablesRs->fetch_array()) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        $tableEscaped = '`' . str_replace('`', '``', $table) . '`';
        $sql .= "-- ----------------------------\n";
        $sql .= "-- Table: {$table}\n";
        $sql .= "-- ----------------------------\n";
        $sql .= "DROP TABLE IF EXISTS {$tableEscaped};\n";

        $createRs = $conn->query("SHOW CREATE TABLE {$tableEscaped}");
        $createRow = $createRs->fetch_assoc();
        $sql .= $createRow['Create Table'] . ";\n\n";

        $rowsRs = $conn->query("SELECT * FROM {$tableEscaped}");
        if ($rowsRs && $rowsRs->num_rows > 0) {
            while ($data = $rowsRs->fetch_assoc()) {
                $columns = array_map(static function ($col) {
                    return '`' . str_replace('`', '``', $col) . '`';
                }, array_keys($data));

                $values = [];
                foreach ($data as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string((string)$value) . "'";
                    }
                }

                $sql .= 'INSERT INTO ' . $tableEscaped . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function generate_users_backup(mysqli $conn): string
{
    $table = 'users';
    $tableEscaped = '`' . str_replace('`', '``', $table) . '`';

    $sql = "-- FoodFlow users table backup\n";
    $sql .= '-- Generated at: ' . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    $sql .= "-- ----------------------------\n";
    $sql .= "-- Table: {$table}\n";
    $sql .= "-- ----------------------------\n";
    $sql .= "DROP TABLE IF EXISTS {$tableEscaped};\n";

    $createRs = $conn->query("SHOW CREATE TABLE {$tableEscaped}");
    if ($createRs && ($createRow = $createRs->fetch_assoc())) {
        $sql .= $createRow['Create Table'] . ";\n\n";
    }

    $rowsRs = $conn->query("SELECT * FROM {$tableEscaped}");
    if ($rowsRs && $rowsRs->num_rows > 0) {
        while ($data = $rowsRs->fetch_assoc()) {
            $columns = array_map(static function ($col) {
                return '`' . str_replace('`', '``', $col) . '`';
            }, array_keys($data));

            $values = [];
            foreach ($data as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string((string)$value) . "'";
                }
            }

            $sql .= 'INSERT INTO ' . $tableEscaped . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

$message = '';
$error = '';

if (isset($_GET['download']) && $_GET['download'] !== '') {
    $fileName = basename((string)$_GET['download']);
    $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

    if (is_file($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        readfile($filePath);
        exit();
    }

    $error = 'Requested backup file was not found.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_backup') {
        $stamp = date('Ymd_His');
        $fileName = 'food_inventory_backup_' . $stamp . '.sql';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $sqlBody = generate_sql_backup($conn);
        $written = @file_put_contents($filePath, $sqlBody);

        if ($written === false) {
            $error = 'Backup failed: unable to write backup file on server.';
        } else {
            $message = 'Backup created successfully: ' . $fileName;
        }
    } elseif ($action === 'download_now') {
        $stamp = date('Ymd_His');
        $fileName = 'food_inventory_backup_' . $stamp . '.sql';
        $sqlBody = generate_sql_backup($conn);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($sqlBody));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $sqlBody;
        exit();
    } elseif ($action === 'save_users_backup') {
        $stamp = date('Ymd_His');
        $fileName = 'food_inventory_users_backup_' . $stamp . '.sql';
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        $sqlBody = generate_users_backup($conn);
        $written = @file_put_contents($filePath, $sqlBody);

        if ($written === false) {
            $error = 'Users backup failed: unable to write backup file on server.';
        } else {
            $message = 'Users backup created successfully: ' . $fileName;
        }
    } elseif ($action === 'download_users_now') {
        $stamp = date('Ymd_His');
        $fileName = 'food_inventory_users_backup_' . $stamp . '.sql';
        $sqlBody = generate_users_backup($conn);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($sqlBody));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo $sqlBody;
        exit();
    }
}

$backupFiles = [];
if (is_dir($backupDir)) {
    $backupFiles = array_values(array_filter(scandir($backupDir), static function ($f) use ($backupDir) {
        return $f !== '.' && $f !== '..' && is_file($backupDir . DIRECTORY_SEPARATOR . $f);
    }));
    rsort($backupFiles);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Center - FoodFlow</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">FoodFlow Admin</div>
    <div class="navbar-user">
        <span><?php echo htmlspecialchars((string)$_SESSION['user_name']); ?> (Admin)</span>
        <a href="../auth/change_password.php" class="logout-btn" style="margin-right:8px;background:#1f7a8c;">Change Password</a>
        <a href="../auth/logout.php" class="logout-btn">Logout</a>
    </div>
</nav>
<nav class="admin-nav">
    <ul class="admin-nav-links">
        <li><a href="admin_dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Manage Users</a></li>
        <li><a href="system_audit.php">System Audit</a></li>
        <li><a href="backup.php" class="active">Backup Center</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <h3>Backup Center</h3>
        <p style="margin:8px 0 12px;color:#555;">Use this page to create immediate database backups for testing evidence and recovery readiness.</p>

        <?php if ($message !== ''): ?><p class="success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="save_backup">
                <button type="submit" class="action-btn">Perform Backup Now (Save Copy)</button>
            </form>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="download_now">
                <button type="submit" class="action-btn">Download Backup Now (.sql)</button>
            </form>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="save_users_backup">
                <button type="submit" class="action-btn">Save Users Backup</button>
            </form>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="download_users_now">
                <button type="submit" class="action-btn">Download Users Backup (.sql)</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>Saved Backup Files</h3>
        <?php if (count($backupFiles) === 0): ?>
            <p>No backup files saved yet. Click "Perform Backup Now (Save Copy)".</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>File Name</th><th>Created</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($backupFiles as $file):
                        $fullPath = $backupDir . DIRECTORY_SEPARATOR . $file;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file); ?></td>
                            <td><?php echo date('Y-m-d H:i:s', filemtime($fullPath)); ?></td>
                            <td><a class="btn btn-primary btn-sm" href="backup.php?download=<?php echo urlencode($file); ?>">Download</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:16px;">
        <h3>Recommended Automated Schedule</h3>
        <pre style="white-space:pre-wrap;margin:0;">- Full backup daily at midnight
- Incremental backup every 2 hours
- Retention: 14 days

For demonstration/testing evidence, use "Perform Backup Now" and capture the success message and file list.</pre>
    </div>
</div>
</body>
</html>
