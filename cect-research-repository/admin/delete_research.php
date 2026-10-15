<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('archived_research.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$db = Database::getConnection();

$stmt = $db->prepare('SELECT * FROM researches WHERE id = :id');
$stmt->execute(['id' => $id]);
$research = $stmt->fetch();

if ($research) {
    $filePath = UPLOAD_DIR . $research['file_name'];
    if (is_file($filePath)) {
        unlink($filePath);
    }

    $db->prepare('DELETE FROM researches WHERE id = :id')->execute(['id' => $id]);
    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Permanently Delete Research', $research['title']);
    flash('success', 'Research paper permanently deleted.');
} else {
    flash('error', 'Research paper not found.');
}

redirect('archived_research.php');
