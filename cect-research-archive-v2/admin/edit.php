<?php
/**
 * admin/edit.php — Edit Existing Research
 * Pre-populates form with current data. PDF upload is optional on edit.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle  = 'Edit Research';
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

$existing = getResearchById($id);
if (!$existing) {
    setFlash('error', 'Research record not found.');
    header('Location: researches.php');
    exit;
}

$errors = [];

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $old = [
        'title'         => trim($_POST['title']         ?? ''),
        'authors'       => trim($_POST['authors']        ?? ''),
        'course'        => trim($_POST['course']         ?? ''),
        'research_type' => trim($_POST['research_type']  ?? ''),
        'year'          => trim($_POST['year']           ?? ''),
        'abstract'      => trim($_POST['abstract']       ?? ''),
        'adviser'       => trim($_POST['adviser']        ?? ''),
        'panel_chair'   => trim($_POST['panel_chair']    ?? ''),
        'panel_members' => trim($_POST['panel_members']  ?? ''),
    ];

    $validCourses = ['IT', 'Computer Engineering', 'Computer Science'];
    $validTypes   = ['Web App', 'Mobile App', 'IoT', 'Machine Learning/AI',
                     'Desktop App', 'Cybersecurity', 'Data Analytics'];
    $validYears   = range(2020, (int)date('Y') + 1);

    if ($old['title'] === '')              $errors['title']         = 'Title is required.';
    if (strlen($old['title']) > 500)       $errors['title']         = 'Title must not exceed 500 characters.';
    if ($old['authors'] === '')            $errors['authors']       = 'Author(s) are required.';
    if (!in_array($old['course'], $validCourses, true))
                                           $errors['course']        = 'Please select a valid course.';
    if (!in_array($old['research_type'], $validTypes, true))
                                           $errors['research_type'] = 'Please select a valid research type.';
    if (!in_array((int)$old['year'], $validYears, true))
                                           $errors['year']          = 'Please select a valid year.';
    if ($old['abstract'] === '')           $errors['abstract']      = 'Abstract is required.';
    if ($old['adviser'] === '')            $errors['adviser']       = 'Adviser name is required.';
    if ($old['panel_chair'] === '')        $errors['panel_chair']   = 'Panel Chair name is required.';

    // Handle optional PDF replacement
    $newFilename = null;
    if (!empty($_FILES['pdf_file']['name'])) {
        $upload = uploadPdf($_FILES['pdf_file']);
        if (!$upload['ok']) {
            $errors['pdf_file'] = $upload['error'];
        } else {
            $newFilename = $upload['filename'];
        }
    }

    // ── Update DB ─────────────────────────────────────────────────────────
    if (empty($errors)) {
        $pdfToStore = $newFilename ?? $existing['pdf_file'];

        $stmt = getDB()->prepare(
            'UPDATE researches SET
               title = ?, authors = ?, course = ?, research_type = ?,
               year = ?, abstract = ?, adviser = ?,
               panel_chair = ?, panel_members = ?, pdf_file = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $old['title'],
            $old['authors'],
            $old['course'],
            $old['research_type'],
            (int)$old['year'],
            $old['abstract'],
            $old['adviser'],
            $old['panel_chair'],
            $old['panel_members'],
            $pdfToStore,
            $id,
        ]);

        // Delete old PDF file if it was replaced
        if ($newFilename && $existing['pdf_file'] !== $newFilename) {
            deletePdf($existing['pdf_file']);
        }

        setFlash('success', 'Research paper updated successfully!');
        header('Location: researches.php');
        exit;
    }
} else {
    // Populate $old from existing record on GET
    $old = $existing;
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4 p-lg-5">

        <!-- Error summary -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following errors:</div>
            <ul class="mb-0 small">
                <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Current PDF info -->
        <div class="alert alert-info d-flex align-items-center gap-2 mb-4 py-2">
            <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
            <span class="small">
                Current PDF: <strong><?= e($existing['pdf_file']) ?></strong> —
                Leave the file field empty to keep the existing file.
            </span>
        </div>

        <form method="POST" action="edit.php?id=<?= $id ?>" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="row g-4">

                <!-- Title -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="title">
                        Research Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                           id="title" name="title" maxlength="500" required
                           value="<?= e($old['title'] ?? '') ?>">
                    <?php if (isset($errors['title'])): ?>
                    <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Authors -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="authors">
                        Author(s) <span class="text-danger">*</span>
                        <span class="text-muted fw-normal small ms-1">— comma-separated</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['authors']) ? 'is-invalid' : '' ?>"
                           id="authors" name="authors" required
                           value="<?= e($old['authors'] ?? '') ?>">
                    <?php if (isset($errors['authors'])): ?>
                    <div class="invalid-feedback"><?= e($errors['authors']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Course -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="course">
                        Course / Program <span class="text-danger">*</span>
                    </label>
                    <select class="form-select <?= isset($errors['course']) ? 'is-invalid' : '' ?>"
                            id="course" name="course" required>
                        <option value="">— Select Course —</option>
                        <?php foreach (['IT', 'Computer Engineering', 'Computer Science'] as $c): ?>
                        <option value="<?= e($c) ?>" <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>>
                            <?= e($c) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['course'])): ?>
                    <div class="invalid-feedback"><?= e($errors['course']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Research Type -->
                <div class="col-md-5">
                    <label class="form-label fw-semibold" for="research_type">
                        Research Type <span class="text-danger">*</span>
                    </label>
                    <select class="form-select <?= isset($errors['research_type']) ? 'is-invalid' : '' ?>"
                            id="research_type" name="research_type" required>
                        <option value="">— Select Type —</option>
                        <?php foreach (['Web App','Mobile App','IoT','Machine Learning/AI','Desktop App','Cybersecurity','Data Analytics'] as $t): ?>
                        <option value="<?= e($t) ?>" <?= ($old['research_type'] ?? '') === $t ? 'selected' : '' ?>>
                            <?= e($t) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['research_type'])): ?>
                    <div class="invalid-feedback"><?= e($errors['research_type']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Year -->
                <div class="col-md-3">
                    <label class="form-label fw-semibold" for="year">
                        Year <span class="text-danger">*</span>
                    </label>
                    <select class="form-select <?= isset($errors['year']) ? 'is-invalid' : '' ?>"
                            id="year" name="year" required>
                        <option value="">— Year —</option>
                        <?php for ($y = (int)date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= (string)($old['year'] ?? '') === (string)$y ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                    <?php if (isset($errors['year'])): ?>
                    <div class="invalid-feedback"><?= e($errors['year']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Abstract -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="abstract">
                        Abstract <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control <?= isset($errors['abstract']) ? 'is-invalid' : '' ?>"
                              id="abstract" name="abstract" rows="8" required><?= e($old['abstract'] ?? '') ?></textarea>
                    <?php if (isset($errors['abstract'])): ?>
                    <div class="invalid-feedback"><?= e($errors['abstract']) ?></div>
                    <?php endif; ?>
                    <div class="form-text"><span id="abstractCount">0</span> characters</div>
                </div>

                <!-- Adviser -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="adviser">
                        Adviser <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['adviser']) ? 'is-invalid' : '' ?>"
                           id="adviser" name="adviser" required
                           value="<?= e($old['adviser'] ?? '') ?>">
                    <?php if (isset($errors['adviser'])): ?>
                    <div class="invalid-feedback"><?= e($errors['adviser']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Panel Chair -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="panel_chair">
                        Panel Chair <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['panel_chair']) ? 'is-invalid' : '' ?>"
                           id="panel_chair" name="panel_chair" required
                           value="<?= e($old['panel_chair'] ?? '') ?>">
                    <?php if (isset($errors['panel_chair'])): ?>
                    <div class="invalid-feedback"><?= e($errors['panel_chair']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Panel Members -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="panel_members">Panel Members</label>
                    <input type="text" class="form-control"
                           id="panel_members" name="panel_members"
                           value="<?= e($old['panel_members'] ?? '') ?>">
                </div>

                <!-- PDF Upload (optional on edit) -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="pdf_file">
                        Replace PDF File
                        <span class="text-muted fw-normal small">— optional, leave empty to keep current</span>
                    </label>
                    <input type="file" class="form-control <?= isset($errors['pdf_file']) ? 'is-invalid' : '' ?>"
                           id="pdf_file" name="pdf_file" accept=".pdf">
                    <?php if (isset($errors['pdf_file'])): ?>
                    <div class="invalid-feedback"><?= e($errors['pdf_file']) ?></div>
                    <?php endif; ?>
                </div>

            </div><!-- /row -->

            <!-- Form actions -->
            <div class="d-flex gap-3 mt-5 pt-3 border-top">
                <button type="submit" class="btn btn-warning fw-semibold px-4">
                    <i class="bi bi-check-circle me-2"></i>Update Research
                </button>
                <a href="researches.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
                <a href="../research.php?id=<?= $id ?>" target="_blank"
                   class="btn btn-outline-success ms-auto">
                    <i class="bi bi-eye me-2"></i>Preview
                </a>
            </div>
        </form>

    </div>
</div>

        </div><!-- /admin-main content -->
    </main>
</div><!-- /admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const abstractEl = document.getElementById('abstract');
    const countEl    = document.getElementById('abstractCount');
    function updateCount() { countEl.textContent = abstractEl.value.length; }
    abstractEl.addEventListener('input', updateCount);
    updateCount();

    document.getElementById('pdf_file').addEventListener('change', function () {
        const maxBytes = 50 * 1024 * 1024;
        if (this.files[0] && this.files[0].size > maxBytes) {
            alert('File exceeds 50 MB limit.');
            this.value = '';
        }
    });
</script>
</body>
</html>
