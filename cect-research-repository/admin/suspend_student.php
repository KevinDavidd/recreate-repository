<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('students.php');
}
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$db = Database::getConnection();

$stmt = $db->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute(['id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    flash('error', 'Student not found.');
    redirect('students.php');
}

if ($action === 'suspend' && $student['status'] === 'active') {
    $db->prepare("UPDATE students SET status = 'suspended' WHERE id = :id")->execute(['id' => $id]);
    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Suspend Student', $student['full_name']);
    flash('success', $student['full_name'] . ' has been suspended.');
} elseif ($action === 'reactivate' && $student['status'] === 'suspended') {
    $db->prepare("UPDATE students SET status = 'active' WHERE id = :id")->execute(['id' => $id]);
    log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Reactivate Student', $student['full_name']);
    flash('success', $student['full_name'] . ' has been reactivated.');
}

redirect('students.php');
