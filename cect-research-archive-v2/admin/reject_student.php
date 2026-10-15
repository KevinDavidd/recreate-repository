<?php
/**
 * admin/reject_student.php — Reject (and remove) a pending student registration.
 * GET: show confirmation. POST: delete the record.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$id = filter_input(INPUT_GET,  'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    setFlash('error', 'Invalid student ID.');
    redirect(baseUrl('admin/pending_students.php'));
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND status = 'pending'");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Student not found or is not pending.');
    redirect(baseUrl('admin/pending_students.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // Delete the registration entirely
    $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([$id]);
    logActivity('admin', $_SESSION['admin_id'], 'rejected student');
    setFlash('success', 'Registration for ' . $student['full_name'] . ' has been rejected and removed.');
    redirect(baseUrl('admin/pending_students.php'));
}

$pageTitle  = 'Reject Student';
$activePage = 'pending_students';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-5">
    <div class="card border-danger shadow-sm rounded-4">
      <div class="card-body p-4 text-center">
        <i class="bi bi-person-x-fill text-danger" style="font-size:3rem"></i>
        <h5 class="fw-bold mt-3 mb-2">Reject Registration?</h5>
        <p class="text-muted mb-3">
          This will permanently <strong>delete</strong> the registration for:
        </p>
        <div class="alert alert-warning text-start">
          <strong><?= e($student['full_name']) ?></strong><br>
          <small><?= e($student['student_id']) ?> &bull; <?= e($student['email']) ?></small>
        </div>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <div class="d-flex gap-2 justify-content-center mt-3">
            <button type="submit" class="btn btn-danger fw-semibold">
              <i class="bi bi-x-circle me-1"></i>Yes, Reject & Remove
            </button>
            <a href="<?= baseUrl('admin/pending_students.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
