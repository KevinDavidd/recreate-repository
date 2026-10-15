<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();

$wasAdmin = !empty($_SESSION['admin_id']);

if (!empty($_SESSION['student_id'])) {
    log_activity('student', $_SESSION['student_id'], $_SESSION['student_name'] ?? 'Student', 'Logout');
} elseif ($wasAdmin) {
    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'] ?? 'Admin', 'Logout');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

redirect($wasAdmin ? 'admin/login.php' : 'login.php');
