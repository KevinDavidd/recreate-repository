<?php
require_once __DIR__ . '/includes/auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM researches WHERE id = :id AND status = 'active'");
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

$db->prepare('UPDATE researches SET download_count = download_count + 1 WHERE id = :id')->execute(['id' => $id]);
log_activity('student', $_SESSION['student_id'], $_SESSION['student_name'], 'View', $research['title']);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($research['original_file_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

readfile($filePath);
exit;
