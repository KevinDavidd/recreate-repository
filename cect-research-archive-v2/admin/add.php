<?php
/**
 * admin/add.php — Add New Research
 * Handles the upload form with server-side validation and PDF file storage.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle  = 'Add New Research';
$activePage = 'add';
$isAdmin    = true;

$errors = [];
$old    = [];   // repopulate form on validation failure

// ── Handle form submission ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    // Collect and trim text fields
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

    // ── Server-side validation ────────────────────────────────────────────
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

    // PDF upload required on add
    if (empty($_FILES['pdf_file']['name'])) {
        $errors['pdf_file'] = 'Please upload a PDF file.';
    }

    // If text validation passes, handle file upload
    $uploadedFilename = null;
    if (empty($errors) && !empty($_FILES['pdf_file']['name'])) {
        $upload = uploadPdf($_FILES['pdf_file']);
        if (!$upload['ok']) {
            $errors['pdf_file'] = $upload['error'];
        } else {
            $uploadedFilename = $upload['filename'];
        }
    }

    // ── Insert into DB ────────────────────────────────────────────────────
    if (empty($errors) && $uploadedFilename) {
        $stmt = getDB()->prepare(
            'INSERT INTO researches
             (title, authors, course, research_type, year, abstract, adviser, panel_chair, panel_members, pdf_file)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
            $uploadedFilename,
        ]);

        setFlash('success', 'Research paper "' . $old['title'] . '" has been added successfully!');
        header('Location: researches.php');
        exit;
    }
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

        <form method="POST" action="add.php" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>

            <div class="row g-4">

                <!-- Title -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="title">
                        Research Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                           id="title" name="title" maxlength="500" required
                           placeholder="Full title of the research or capstone project"
                           value="<?= e($old['title'] ?? '') ?>">
                    <?php if (isset($errors['title'])): ?>
                    <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Authors -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="authors">
                        Author(s) <span class="text-danger">*</span>
                        <span class="text-muted fw-normal small ms-1">— comma-separated (Last, First M.)</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['authors']) ? 'is-invalid' : '' ?>"
                           id="authors" name="authors" required
                           placeholder="Dela Cruz, Juan A., Reyes, Maria B., Santos, Jose C."
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
                              id="abstract" name="abstract" rows="8" required
                              placeholder="Provide a comprehensive abstract of the research (at least 150 words recommended)…"><?= e($old['abstract'] ?? '') ?></textarea>
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
                           placeholder="Prof. / Dr. Full Name"
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
                           placeholder="Prof. / Dr. Full Name"
                           value="<?= e($old['panel_chair'] ?? '') ?>">
                    <?php if (isset($errors['panel_chair'])): ?>
                    <div class="invalid-feedback"><?= e($errors['panel_chair']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Panel Members -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="panel_members">
                        Panel Members
                        <span class="text-muted fw-normal small">— comma-separated</span>
                    </label>
                    <input type="text" class="form-control"
                           id="panel_members" name="panel_members"
                           placeholder="Name 1, Name 2"
                           value="<?= e($old['panel_members'] ?? '') ?>">
                </div>

                <!-- PDF Upload -->
                <div class="col-12">
                    <label class="form-label fw-semibold" for="pdf_file">
                        PDF File <span class="text-danger">*</span>
                        <span class="text-muted fw-normal small">— max 50 MB, .pdf only</span>
                    </label>
                    <input type="file" class="form-control <?= isset($errors['pdf_file']) ? 'is-invalid' : '' ?>"
                           id="pdf_file" name="pdf_file" accept=".pdf" required>
                    <?php if (isset($errors['pdf_file'])): ?>
                    <div class="invalid-feedback"><?= e($errors['pdf_file']) ?></div>
                    <?php endif; ?>
                    <div class="form-text">Upload the complete research document in PDF format (max 50 MB).</div>
                </div>

            </div><!-- /row -->

            <!-- Form actions -->
            <div class="d-flex gap-3 mt-5 pt-3 border-top">
                <button type="submit" class="btn btn-success fw-semibold px-4">
                    <i class="bi bi-cloud-upload me-2"></i>Upload Research
                </button>
                <a href="researches.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancel
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
    // Live abstract character counter
    const abstractEl = document.getElementById('abstract');
    const countEl    = document.getElementById('abstractCount');
    function updateCount() { countEl.textContent = abstractEl.value.length; }
    abstractEl.addEventListener('input', updateCount);
    updateCount();

    // Client-side PDF file size check (secondary guard; server validates too)
    document.getElementById('pdf_file').addEventListener('change', function () {
        const maxBytes = 50 * 1024 * 1024;
        if (this.files[0] && this.files[0].size > maxBytes) {
            alert('The selected file exceeds the 50 MB limit. Please choose a smaller file.');
            this.value = '';
        }
    });
</script>
</body>
</html>
