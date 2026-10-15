<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$errors = [];
$old = ['title' => '', 'abstract' => '', 'authors' => '', 'adviser' => '', 'course' => '', 'research_type' => '', 'year' => date('Y')];
$selectedCategories = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $old['title']         = trim($_POST['title'] ?? '');
    $old['abstract']      = trim($_POST['abstract'] ?? '');
    $old['authors']       = trim($_POST['authors'] ?? '');
    $old['adviser']       = trim($_POST['adviser'] ?? '');
    $old['course']        = trim($_POST['course'] ?? '');
    $old['research_type'] = trim($_POST['research_type'] ?? '');
    $old['year']          = trim($_POST['year'] ?? '');
    $selectedCategories   = array_intersect((array) ($_POST['categories'] ?? []), RESEARCH_CATEGORIES);

    if ($old['title'] === '') $errors[] = 'Title is required.';
    if ($old['abstract'] === '') $errors[] = 'Abstract is required.';
    if ($old['authors'] === '') $errors[] = 'Authors are required.';
    if (!in_array($old['course'], ['BSIT', 'BSCpE', 'BSCS'], true)) $errors[] = 'Please select a valid course.';
    if (!in_array($old['research_type'], RESEARCH_TYPES, true)) $errors[] = 'Please select a valid research type.';
    if (empty($selectedCategories)) $errors[] = 'Please select at least one research category.';
    if (!ctype_digit($old['year']) || $old['year'] < 2000 || $old['year'] > (int) date('Y') + 1) {
        $errors[] = 'Please enter a valid year.';
    }
    if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please upload the research PDF file.';
    }

    $uploadResult = null;
    if (empty($errors)) {
        $uploadResult = store_research_pdf($_FILES['pdf']);
        if (!$uploadResult['ok']) {
            $errors[] = $uploadResult['error'];
        }
    }

    if (empty($errors)) {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO researches (title, abstract, authors, adviser, course, research_type, year_published,
                                      file_name, original_file_name, file_size, uploaded_by)
             VALUES (:title, :abstract, :authors, :adviser, :course, :research_type, :year,
                     :file_name, :original_file_name, :file_size, :uploaded_by)'
        );
        $stmt->execute([
            'title'              => $old['title'],
            'abstract'           => $old['abstract'],
            'authors'            => $old['authors'],
            'adviser'            => $old['adviser'] ?: null,
            'course'             => $old['course'],
            'research_type'      => $old['research_type'],
            'year'               => $old['year'],
            'file_name'          => $uploadResult['file_name'],
            'original_file_name' => $uploadResult['original_file_name'],
            'file_size'          => $uploadResult['file_size'],
            'uploaded_by'        => $_SESSION['admin_id'],
        ]);

        save_research_categories($db, (int) $db->lastInsertId(), $selectedCategories);

        log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Add Research', $old['title']);
        flash('success', 'Research paper added successfully.');
        redirect('researches.php');
    }
}

$pageTitle = 'Add Research';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <p class="form-section-title">Basic Information</p>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= e($old['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Abstract</label>
                <textarea name="abstract" class="form-control" rows="5" required><?= e($old['abstract']) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Authors</label>
                    <input type="text" name="authors" class="form-control" value="<?= e($old['authors']) ?>" placeholder="Comma-separated" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Adviser</label>
                    <input type="text" name="adviser" class="form-control" value="<?= e($old['adviser']) ?>">
                </div>
            </div>
            <p class="form-section-title">Classification</p>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Course</label>
                    <select name="course" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach (['BSIT', 'BSCpE', 'BSCS'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= $old['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Research Type</label>
                    <select name="research_type" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach (RESEARCH_TYPES as $t): ?>
                            <option value="<?= e($t) ?>" <?= $old['research_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Year Published</label>
                    <input type="number" name="year" class="form-control" value="<?= e((string) $old['year']) ?>" min="2000" max="<?= date('Y') + 1 ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Research Categories</label>
                <div class="border rounded p-3">
                    <div class="row">
                        <?php foreach (RESEARCH_CATEGORIES as $cat): ?>
                            <div class="col-md-3 col-sm-4 col-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]"
                                           id="cat_<?= e(str_replace(['/', ' '], '', $cat)) ?>" value="<?= e($cat) ?>"
                                           <?= in_array($cat, $selectedCategories, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="cat_<?= e(str_replace(['/', ' '], '', $cat)) ?>"><?= e($cat) ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-text">Select all categories that apply.</div>
            </div>
            <p class="form-section-title">Research File</p>
            <div class="mb-3">
                <label class="form-label">Research PDF</label>
                <input type="file" name="pdf" class="form-control" accept="application/pdf" required>
                <div class="form-text">PDF only, max 20MB.</div>
            </div>
            <button type="submit" class="btn btn-cect"><i class="bi bi-upload"></i> Save Research</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
