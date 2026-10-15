<?php
/**
 * admin/delete.php — Delete Research Entry
 * Removes the DB record AND the associated PDF file from storage.
 * Uses a CSRF-protected confirmation form (GET shows confirm, POST deletes).
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle  = 'Delete Research';
$activePage = 'researches';
$isAdmin    = true;

// Validate ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlash('error', 'No research ID provided.');
    header('Location: researches.php');
    exit;
}

$research = getResearchById($id);
if (!$research) {
    setFlash('error', 'Research record not found.');
    header('Location: researches.php');
    exit;
}

// ── POST: perform deletion ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $title = $research['title'];

    // Delete PDF file from server
    deletePdf($research['pdf_file']);

    // Delete DB record
    $stmt = getDB()->prepare('DELETE FROM researches WHERE id = ?');
    $stmt->execute([$id]);

    setFlash('success', 'Research paper "' . $title . '" has been deleted.');
    header('Location: researches.php');
    exit;
}

// ── GET: show confirmation page ───────────────────────────────────────────
include __DIR__ . '/../includes/admin_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-5 text-center">

                <!-- Warning icon -->
                <div class="delete-icon mx-auto mb-4">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:3rem;"></i>
                </div>

                <h4 class="fw-bold mb-2">Confirm Deletion</h4>
                <p class="text-muted mb-4">
                    You are about to permanently delete this research entry.
                    This action <strong>cannot be undone</strong> and will also
                    remove the uploaded PDF file from the server.
                </p>

                <!-- Research details -->
                <div class="alert alert-light border text-start mb-4">
                    <p class="mb-1"><strong>Title:</strong> <?= e($research['title']) ?></p>
                    <p class="mb-1"><strong>Authors:</strong> <?= e($research['authors']) ?></p>
                    <p class="mb-1"><strong>Course:</strong> <?= e($research['course']) ?></p>
                    <p class="mb-0"><strong>Year:</strong> <?= e($research['year']) ?></p>
                </div>

                <!-- Confirmation form -->
                <form method="POST" action="delete.php?id=<?= $id ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="submit" class="btn btn-danger fw-semibold px-4">
                            <i class="bi bi-trash3 me-2"></i>Yes, Delete Permanently
                        </button>
                        <a href="researches.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

        </div><!-- /admin-main content -->
    </main>
</div><!-- /admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
