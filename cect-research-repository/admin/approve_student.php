<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pending_students.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM students WHERE id = :id AND status = 'pending'");
$stmt->execute(['id' => $id]);
$student = $stmt->fetch();

if ($student) {
    $db->prepare("UPDATE students SET status = 'active', approved_at = NOW() WHERE id = :id")->execute(['id' => $id]);
    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Approve Student', $student['full_name'] . ' (' . $student['student_id'] . ')');
    flash('success', $student['full_name'] . ' has been approved.');
} else {
    flash('error', 'Student registration not found.');
}

redirect('pending_students.php');
