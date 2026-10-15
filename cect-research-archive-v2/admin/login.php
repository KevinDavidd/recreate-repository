<?php
// Admin login is unified with student login at login.php
require_once __DIR__ . '/../includes/functions.php';
startSession();
redirect(baseUrl('login.php'));
