<?php
/**
 * includes/auth_check.php — Student authentication guard
 * Include at the top of every student-only page.
 * Redirects unauthenticated or non-active users appropriately.
 */

require_once __DIR__ . '/functions.php';
startSession();
checkSessionTimeout();

if (!isStudent()) {
    // Not logged in at all — go to login
    redirect(baseUrl('login.php'));
}

$status = $_SESSION['student_status'] ?? '';

if ($status === 'pending') {
    redirect(baseUrl('pending.php'));
}
if ($status === 'suspended') {
    redirect(baseUrl('suspended.php'));
}
if ($status !== 'active') {
    // rejected or unknown
    session_unset();
    session_destroy();
    redirect(baseUrl('login.php?rejected=1'));
}
