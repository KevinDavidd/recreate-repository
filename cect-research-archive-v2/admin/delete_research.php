<?php
/**
 * admin/delete_research.php — Delete a research entry and its PDF.
 * GET: show confirmation. POST: perform deletion.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$id = filter_input(INPUT_GET,  'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    setFlash('error', 'Invalid research ID.');
    redirect(baseUrl('admin/researches.php'));
}

$research = getResearchById($id);
if (!$research) {
    setFlash('error', 'Research paper not found.');
    redirect(baseUrl('admin/researches.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Delete PDF file from storage
    $filePath = UPLOAD_DIR . basename($research['pdf_file']);
    if (!str_starts_with($research['pdf_file'], 'sample_') && file_exists($filePath)) {
        @unlink($filePath);
    }

    // Delete DB record
    getDB()->prepare('DELETE FROM researches WHERE id = ?')->execute([$id]);
    logActivity('admin', $_SESSION['admin_id'], 'deleted research', $id);

    setFlash('success', 'Research "' . $research['title'] . '" deleted successfully.');
    redirect(baseUrl('admin/researches.php'));
}

$pageTitle  = 'Delete Research';
$activePage = 'researches';
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="card border-danger shadow-sm rounded-4">
      <div class="card-body p-4 text-center">
        <div class="mb-3">
          <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:3rem"></i>
        </div>
        <h5 class="fw-bold mb-2">Confirm Deletion</h5>
        <p class="text-muted mb-3">
          You are about to permanently delete the following research paper
          and its associated PDF file. This action <strong>cannot be undone</strong>.
        </p>
        <div class="alert alert-warning text-start">
          <strong><?= e($research['title']) ?></strong><br>
          <small class="text-muted"><?= e($research['course']) ?> &bull; <?= e($research['year']) ?></small>
        </div>
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <div class="d-flex gap-2 justify-content-center mt-3">
            <button type="submit" class="btn btn-danger fw-semibold">
              <i class="bi bi-trash me-1"></i>Yes, Delete Permanently
            </button>
            <a href="<?= baseUrl('admin/researches.php') ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
