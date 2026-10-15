<?php
/**
 * admin/approve_student.php — Approve a pending student registration.
 * GET only (no form needed — redirects back immediately with flash).
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    setFlash('error', 'Invalid student ID.');
    redirect(baseUrl('admin/pending_students.php'));
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found or is not pending.');
    redirect(baseUrl('admin/pending_students.php'));
}

$pdo->prepare(
    "UPDATE students SET status='active', approved_at=NOW(), approved_by=? WHERE id=?"
)->execute([$_SESSION['admin_id'], $id]);

logActivity('admin', $_SESSION['admin_id'], 'approved student', $id);
setFlash('success', $student['full_name'] . ' has been approved and can now log in.');
redirect(baseUrl('admin/pending_students.php'));
