<?php
/**
 * logout.php — Destroys the session and redirects to login.
 */

require_once __DIR__ . '/includes/functions.php';
startSession();

// Log the action before destroying session
if (isAdmin()) {
    logActivity('admin', $_SESSION['admin_id'], 'logged out');
} elseif (isStudent()) {
    logActivity('student', $_SESSION['student_id'], 'logged out');
}

session_unset();
session_destroy();

// Start fresh session just for the flash message
session_start();
setFlash('success', 'You have been logged out successfully.');

header('Location: index.php');
exit;
