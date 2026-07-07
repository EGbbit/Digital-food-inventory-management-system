<?php
require_once __DIR__ . '/../core/auth.php';
require_role('manager');
header('Location: manager_reports.php');
exit();
