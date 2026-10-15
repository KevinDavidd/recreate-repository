<?php
/**
 * admin/suspend_student.php — Suspend or reactivate a student account.
 * Reads ?action=suspend|reactivate via GET.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$id     = filter_input(INPUT_GET, 'id',     FILTER_VALIDATE_INT);
$action = trim($_GET['action'] ?? '');

if (!$id || $id < 1 || !in_array($action, ['suspend','reactivate'], true)) {
    setFlash('error', 'Invalid request.');
    redirect(baseUrl('admin/students.php'));
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found.');
    redirect(baseUrl('admin/students.php'));
}

if ($action === 'suspend' && $student['status'] !== 'active') {
    setFlash('warning', 'Student is not currently active.');
    redirect(baseUrl('admin/students.php'));
}
if ($action === 'reactivate' && $student['status'] !== 'suspended') {
    setFlash('warning', 'Student is not currently suspended.');
    redirect(baseUrl('admin/students.php'));
}

$newStatus = $action === 'suspend' ? 'suspended' : 'active';
$pdo->prepare('UPDATE students SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
logActivity('admin', $_SESSION['admin_id'], $action . 'd student', $id);

$msg = $action === 'suspend'
    ? $student['full_name'] . ' has been suspended.'
    : $student['full_name'] . ' has been reactivated.';

setFlash('success', $msg);
redirect(baseUrl('admin/students.php'));
