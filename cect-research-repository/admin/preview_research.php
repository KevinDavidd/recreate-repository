<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
$db = Database::getConnection();

$stmt = $db->prepare('SELECT file_name, original_file_name FROM researches WHERE id = :id');
$stmt->execute(['id' => $id]);
$research = $stmt->fetch();

if (!$research) {
    http_response_code(404);
    die('Research paper not found.');
}

$filePath = UPLOAD_DIR . $research['file_name'];
if (!is_file($filePath)) {
    http_response_code(404);
    die('The requested file is no longer available.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($research['original_file_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

readfile($filePath);
exit;
