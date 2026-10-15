<?php
/**
 * admin/add_research.php — Add a new research entry with PDF upload.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old = [
        'title'         => trim($_POST['title']          ?? ''),
        'authors'       => trim($_POST['authors']         ?? ''),
        'course'        => trim($_POST['course']          ?? ''),
        'research_type' => trim($_POST['research_type']   ?? ''),
        'year'          => trim($_POST['year']            ?? ''),
        'abstract'      => trim($_POST['abstract']        ?? ''),
        'adviser'       => trim($_POST['adviser']         ?? ''),
        'panel_chair'   => trim($_POST['panel_chair']     ?? ''),
        'panel_members' => trim($_POST['panel_members']   ?? ''),
    ];

    // Text field validation
    if ($old['title']   === '') $errors[] = 'Title is required.';
    if ($old['authors'] === '') $errors[] = 'Authors are required.';
    if (!in_array($old['course'], allowedCourses(), true)) $errors[] = 'Invalid course selected.';
    if (!in_array($old['research_type'], allowedTypes(), true)) $errors[] = 'Invalid research type selected.';
    $year = (int) $old['year'];
    if ($year < 2020 || $year > (int) date('Y')) $errors[] = 'Invalid year selected.';
    if ($old['abstract']  === '') $errors[] = 'Abstract is required.';
    if ($old['adviser']   === '') $errors[] = 'Adviser is required.';
    if ($old['panel_chair'] === '') $errors[] = 'Panel Chair is required.';

    // PDF upload validation
    $pdfFilename = '';
    if (empty($_FILES['pdf_file']['name'])) {
        $errors[] = 'PDF file is required.';
    } else {
        $file     = $_FILES['pdf_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error. Code: ' . $file['error'];
        } elseif ($ext !== 'pdf' || $mimeType !== 'application/pdf') {
            $errors[] = 'Only PDF files are allowed.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds 50 MB limit.';
        } else {
            $pdfFilename = uniqid('research_', true) . '.pdf';
        }
    }

    if (empty($errors)) {
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $pdfFilename)) {
            $errors[] = 'Failed to save the uploaded file. Check folder permissions.';
        } else {
            getDB()->prepare(
                'INSERT INTO researches
                   (title, authors, course, research_type, year, abstract,
                    adviser, panel_chair, panel_members, pdf_file)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $old['title'], $old['authors'], $old['course'], $old['research_type'],
                $year, $old['abstract'], $old['adviser'], $old['panel_chair'],
                $old['panel_members'], $pdfFilename,
            ]);
            $newId = (int) getDB()->lastInsertId();
            logActivity('admin', $_SESSION['admin_id'], 'added research', $newId);
            setFlash('success', 'Research paper "' . $old['title'] . '" added successfully.');
            redirect(baseUrl('admin/researches.php'));
        }
    }
}

$currentYear = (int) date('Y');
$pageTitle   = 'Add Research';
$activePage  = 'add_research';
include __DIR__ . '/../includes/admin_header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <strong>Please fix these errors:</strong>
    <ul class="mb-0 mt-1 ps-3">
      <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <form method="POST" enctype="multipart/form-data" novalidate>
      <?= csrfField() ?>

      <div class="row g-3">

        <!-- Title -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="title">Title <span class="text-danger">*</span></label>
          <input type="text" id="title" name="title" class="form-control"
                 value="<?= e($old['title'] ?? '') ?>" required>
        </div>

        <!-- Authors -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="authors">
            Authors <span class="text-danger">*</span>
            <small class="text-muted fw-normal">(comma-separated)</small>
          </label>
          <input type="text" id="authors" name="authors" class="form-control"
                 placeholder="e.g. Juan dela Cruz, Maria Santos"
                 value="<?= e($old['authors'] ?? '') ?>" required>
        </div>

        <!-- Course, Type, Year -->
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="course">Course <span class="text-danger">*</span></label>
          <select id="course" name="course" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach (allowedCourses() as $c): ?>
              <option value="<?= e($c) ?>" <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="research_type">Research Type <span class="text-danger">*</span></label>
          <select id="research_type" name="research_type" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach (allowedTypes() as $t): ?>
              <option value="<?= e($t) ?>" <?= ($old['research_type'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="year">Year <span class="text-danger">*</span></label>
          <select id="year" name="year" class="form-select" required>
            <option value="">— Select —</option>
            <?php for ($y = $currentYear; $y >= 2020; $y--): ?>
              <option value="<?= $y ?>" <?= ($old['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- Abstract -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="abstract">Abstract <span class="text-danger">*</span></label>
          <textarea id="abstract" name="abstract" class="form-control" rows="7" required><?= e($old['abstract'] ?? '') ?></textarea>
        </div>

        <!-- Adviser, Panel Chair -->
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="adviser">Adviser <span class="text-danger">*</span></label>
          <input type="text" id="adviser" name="adviser" class="form-control"
                 value="<?= e($old['adviser'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="panel_chair">Panel Chair <span class="text-danger">*</span></label>
          <input type="text" id="panel_chair" name="panel_chair" class="form-control"
                 value="<?= e($old['panel_chair'] ?? '') ?>" required>
        </div>

        <!-- Panel Members -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="panel_members">
            Panel Members <small class="text-muted fw-normal">(comma-separated, optional)</small>
          </label>
          <input type="text" id="panel_members" name="panel_members" class="form-control"
                 placeholder="e.g. Prof. Torres, Prof. Bautista"
                 value="<?= e($old['panel_members'] ?? '') ?>">
        </div>

        <!-- PDF Upload -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="pdf_file">
            PDF File <span class="text-danger">*</span>
            <small class="text-muted fw-normal">(PDF only, max 50 MB)</small>
          </label>
          <input type="file" id="pdf_file" name="pdf_file" class="form-control"
                 accept=".pdf,application/pdf" required>
        </div>

      </div><!-- /row -->

      <hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success fw-semibold">
          <i class="bi bi-plus-circle me-1"></i>Add Research
        </button>
        <a href="<?= baseUrl('admin/researches.php') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
