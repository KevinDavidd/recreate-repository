<?php
// Redirect to unified logout handler at root
require_once __DIR__ . '/../includes/functions.php';
startSession();
redirect(baseUrl('logout.php'));
