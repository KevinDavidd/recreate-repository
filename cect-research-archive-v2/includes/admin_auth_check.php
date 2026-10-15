<?php
/**
 * includes/admin_auth_check.php — Admin authentication guard
 * Include at the top of every admin page.
 */

require_once __DIR__ . '/functions.php';
startSession();
checkSessionTimeout();

if (!isAdmin()) {
    redirect(baseUrl('login.php'));
}
