<?php
/** JSON endpoint powering the auto-refreshing dashboard tables. */
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

$byCourse = $db->query("SELECT course, COUNT(*) AS total FROM researches WHERE status = 'active' GROUP BY course ORDER BY total DESC")->fetchAll();
$byYear   = $db->query("SELECT year_published, COUNT(*) AS total FROM researches WHERE status = 'active' GROUP BY year_published ORDER BY year_published")->fetchAll();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
echo json_encode([
    'byCourse' => $byCourse,
    'byYear'   => $byYear,
]);
