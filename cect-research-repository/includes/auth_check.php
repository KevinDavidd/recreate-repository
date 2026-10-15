<?php
/**
 * Session guard for student-facing pages.
 * Include at the very top of any page that requires an active student login.
 */

require_once __DIR__ . '/functions.php';
start_secure_session();

if (empty($_SESSION['student_id']) || ($_SESSION['user_role'] ?? null) !== 'student') {
    redirect('login.php');
}

enforce_timeout(STUDENT_TIMEOUT, 'login.php');
