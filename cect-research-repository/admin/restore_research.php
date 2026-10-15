<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('archived_research.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM researches WHERE id = :id AND status = 'archived'");
$stmt->execute(['id' => $id]);
$research = $stmt->fetch();

if ($research) {
    $stmt = $db->prepare(
        "UPDATE researches SET status = 'active', archived_at = NULL, archived_by = NULL, archive_reason = NULL
         WHERE id = :id"
    );
    $stmt->execute(['id' => $id]);

    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Restore Research', $research['title']);
    flash('success', 'Research paper restored and is now visible in the public repository again.');
} else {
    flash('error', 'Archived research paper not found.');
}

redirect('archived_research.php');
