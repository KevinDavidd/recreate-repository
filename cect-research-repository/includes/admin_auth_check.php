<?php
/**
 * Session guard for admin-facing pages.
 * Include at the very top of any page under /admin/.
 */

require_once __DIR__ . '/functions.php';
start_secure_session();

if (empty($_SESSION['admin_id']) || ($_SESSION['user_role'] ?? null) !== 'admin') {
    redirect('login.php');
}

enforce_timeout(ADMIN_TIMEOUT, 'login.php');
