<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('researches.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$reason = trim($_POST['archive_reason'] ?? '');
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM researches WHERE id = :id AND status = 'active'");
$stmt->execute(['id' => $id]);
$research = $stmt->fetch();

if ($research) {
    $stmt = $db->prepare(
        "UPDATE researches SET status = 'archived', archived_at = NOW(), archived_by = :admin_id, archive_reason = :reason
         WHERE id = :id"
    );
    $stmt->execute([
        'admin_id' => $_SESSION['admin_id'],
        'reason'   => $reason ?: null,
        'id'       => $id,
    ]);

    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Archive Research', $research['title']);
    flash('success', 'Research paper archived. It is no longer visible in the public repository.');
} else {
    flash('error', 'Research paper not found.');
}

redirect('researches.php');
