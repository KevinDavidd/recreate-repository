<?php
/**
 * admin/edit_research.php — Edit an existing research entry.
 * PDF upload is optional; existing file is kept if no new one is provided.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
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

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $data = [
        'title'         => trim($_POST['title']          ?? ''),
        'authors'       => trim($_POST['authors']         ?? ''),
        'course'        => trim($_POST['course']          ?? ''),
        'research_type' => trim($_POST['research_type']   ?? ''),
        'year'          => (int) ($_POST['year']          ?? 0),
        'abstract'      => trim($_POST['abstract']        ?? ''),
        'adviser'       => trim($_POST['adviser']         ?? ''),
        'panel_chair'   => trim($_POST['panel_chair']     ?? ''),
        'panel_members' => trim($_POST['panel_members']   ?? ''),
    ];

    if ($data['title']   === '') $errors[] = 'Title is required.';
    if ($data['authors'] === '') $errors[] = 'Authors are required.';
    if (!in_array($data['course'], allowedCourses(), true)) $errors[] = 'Invalid course.';
    if (!in_array($data['research_type'], allowedTypes(), true)) $errors[] = 'Invalid research type.';
    if ($data['year'] < 2020 || $data['year'] > (int) date('Y')) $errors[] = 'Invalid year.';
    if ($data['abstract']   === '') $errors[] = 'Abstract is required.';
    if ($data['adviser']    === '') $errors[] = 'Adviser is required.';
    if ($data['panel_chair'] === '') $errors[] = 'Panel Chair is required.';

    // Handle optional PDF replacement
    $pdfFilename = $research['pdf_file'];  // keep existing by default
    if (!empty($_FILES['pdf_file']['name'])) {
        $file     = $_FILES['pdf_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error. Code: ' . $file['error'];
        } elseif ($ext !== 'pdf' || $mimeType !== 'application/pdf') {
            $errors[] = 'Only PDF files are allowed.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds 50 MB.';
        } else {
            $newFilename = uniqid('research_', true) . '.pdf';
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newFilename)) {
                $errors[] = 'Failed to save the uploaded file.';
            } else {
                // Remove old file if it is not a sample placeholder
                $oldPath = UPLOAD_DIR . $research['pdf_file'];
                if (!str_starts_with($research['pdf_file'], 'sample_') && file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                $pdfFilename = $newFilename;
            }
        }
    }

    if (empty($errors)) {
        getDB()->prepare(
            'UPDATE researches SET title=?, authors=?, course=?, research_type=?, year=?,
             abstract=?, adviser=?, panel_chair=?, panel_members=?, pdf_file=?
             WHERE id=?'
        )->execute([
            $data['title'], $data['authors'], $data['course'], $data['research_type'],
            $data['year'], $data['abstract'], $data['adviser'], $data['panel_chair'],
            $data['panel_members'], $pdfFilename, $id,
        ]);
        logActivity('admin', $_SESSION['admin_id'], 'edited research', $id);
        setFlash('success', 'Research updated successfully.');
        redirect(baseUrl('admin/researches.php'));
    }

    // Merge edited data back for form repopulation
    $research = array_merge($research, $data, ['id' => $id]);
}

$currentYear = (int) date('Y');
$pageTitle   = 'Edit Research';
$activePage  = 'researches';
include __DIR__ . '/../includes/admin_header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <strong>Please fix these errors:</strong>
    <ul class="mb-0 mt-1 ps-3">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <form method="POST" enctype="multipart/form-data" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">

      <div class="row g-3">

        <div class="col-12">
          <label class="form-label fw-semibold" for="title">Title <span class="text-danger">*</span></label>
          <input type="text" id="title" name="title" class="form-control"
                 value="<?= e($research['title']) ?>" required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold" for="authors">
            Authors <span class="text-danger">*</span> <small class="text-muted fw-normal">(comma-separated)</small>
          </label>
          <input type="text" id="authors" name="authors" class="form-control"
                 value="<?= e($research['authors']) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold" for="course">Course <span class="text-danger">*</span></label>
          <select id="course" name="course" class="form-select" required>
            <?php foreach (allowedCourses() as $c): ?>
              <option value="<?= e($c) ?>" <?= $research['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="research_type">Research Type <span class="text-danger">*</span></label>
          <select id="research_type" name="research_type" class="form-select" required>
            <?php foreach (allowedTypes() as $t): ?>
              <option value="<?= e($t) ?>" <?= $research['research_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold" for="year">Year <span class="text-danger">*</span></label>
          <select id="year" name="year" class="form-select" required>
            <?php for ($y = $currentYear; $y >= 2020; $y--): ?>
              <option value="<?= $y ?>" <?= $research['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold" for="abstract">Abstract <span class="text-danger">*</span></label>
          <textarea id="abstract" name="abstract" class="form-control" rows="7" required><?= e($research['abstract']) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold" for="adviser">Adviser <span class="text-danger">*</span></label>
          <input type="text" id="adviser" name="adviser" class="form-control"
                 value="<?= e($research['adviser']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="panel_chair">Panel Chair <span class="text-danger">*</span></label>
          <input type="text" id="panel_chair" name="panel_chair" class="form-control"
                 value="<?= e($research['panel_chair']) ?>" required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold" for="panel_members">
            Panel Members <small class="text-muted fw-normal">(comma-separated, optional)</small>
          </label>
          <input type="text" id="panel_members" name="panel_members" class="form-control"
                 value="<?= e($research['panel_members']) ?>">
        </div>

        <!-- PDF replacement (optional) -->
        <div class="col-12">
          <label class="form-label fw-semibold" for="pdf_file">
            Replace PDF <small class="text-muted fw-normal">(PDF only, max 50 MB — leave blank to keep current)</small>
          </label>
          <?php if (!str_starts_with($research['pdf_file'], 'sample_')): ?>
            <div class="mb-2 text-muted small">
              <i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>
              Current file: <code><?= e($research['pdf_file']) ?></code>
            </div>
          <?php endif; ?>
          <input type="file" id="pdf_file" name="pdf_file" class="form-control"
                 accept=".pdf,application/pdf">
        </div>

      </div><!-- /row -->

      <hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success fw-semibold">
          <i class="bi bi-check-circle me-1"></i>Save Changes
        </button>
        <a href="<?= baseUrl('admin/researches.php') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
