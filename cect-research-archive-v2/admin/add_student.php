<?php
/**
 * admin/add_student.php — Manually create a student account (auto-approved).
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old = [
        'full_name'  => trim($_POST['full_name']  ?? ''),
        'student_id' => trim($_POST['student_id'] ?? ''),
        'course'     => trim($_POST['course']      ?? ''),
        'year_level' => trim($_POST['year_level']  ?? ''),
        'email'      => trim($_POST['email']       ?? ''),
    ];
    $password = $_POST['password'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!isValidStudentId($old['student_id'])) $errors[] = 'Invalid Student ID format (XX-XXXX-XXX).';
    if (!in_array($old['course'], allowedCourses(), true)) $errors[] = 'Invalid course.';
    if (!in_array($old['year_level'], allowedYearLevels(), true)) $errors[] = 'Invalid year level.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        $pdo = getDB();
        $dupId = $pdo->prepare('SELECT id FROM students WHERE student_id = ?');
        $dupId->execute([$old['student_id']]);
        if ($dupId->fetch()) $errors[] = 'This Student ID is already registered.';

        $dupEmail = $pdo->prepare('SELECT id FROM students WHERE email = ?');
        $dupEmail->execute([$old['email']]);
        if ($dupEmail->fetch()) $errors[] = 'This email address is already registered.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $now  = date('Y-m-d H:i:s');
        getDB()->prepare(
            "INSERT INTO students
               (student_id, full_name, course, year_level, email, password, status, approved_at, approved_by)
             VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?)"
        )->execute([
            $old['student_id'], $old['full_name'], $old['course'],
            $old['year_level'], $old['email'], $hash, $now, $_SESSION['admin_id'],
        ]);
        logActivity('admin', $_SESSION['admin_id'], 'added student manually');
        setFlash('success', 'Student account created and approved: ' . $old['full_name']);
        redirect(baseUrl('admin/students.php'));
    }
}

$pageTitle  = 'Add Student';
$activePage = 'add_student';
include __DIR__ . '/../includes/admin_header.php';
?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0 ps-3">
      <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-4">
    <div class="alert alert-info mb-4">
      <i class="bi bi-info-circle me-1"></i>
      Admin-created accounts are <strong>automatically approved</strong> (status = active).
    </div>
    <form method="POST" novalidate>
      <?= csrfField() ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="full_name">Full Name <span class="text-danger">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control"
                 value="<?= e($old['full_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="student_id">
            Student ID <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="text" id="student_id" name="student_id" class="form-control"
                   placeholder="22-2003-264" maxlength="12"
                   value="<?= e($old['student_id'] ?? '') ?>"
                   pattern="^\d{2}-\d{4}-\d{3}$" required>
            <span class="input-group-text" id="sidStatus">
              <i class="bi bi-dash text-muted" id="sidIcon"></i>
            </span>
          </div>
          <div class="form-text">Format: YY-NNNN-NNN (e.g. 22-2003-264)</div>
          <div id="sidFeedback" class="form-text"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="course">Course <span class="text-danger">*</span></label>
          <select id="course" name="course" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach (allowedCourses() as $c): ?>
              <option value="<?= e($c) ?>" <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="year_level">Year Level <span class="text-danger">*</span></label>
          <select id="year_level" name="year_level" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach (allowedYearLevels() as $y): ?>
              <option value="<?= e($y) ?>" <?= ($old['year_level'] ?? '') === $y ? 'selected' : '' ?>><?= e($y) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="email">Email <span class="text-danger">*</span></label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e($old['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold" for="password">
            Password <span class="text-danger">*</span>
            <small class="text-muted fw-normal">(min. 8 chars)</small>
          </label>
          <input type="text" id="password" name="password" class="form-control"
                 placeholder="Set initial password" minlength="8" required>
        </div>
      </div>

      <hr class="my-4">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success fw-semibold">
          <i class="bi bi-person-plus me-1"></i>Create Account
        </button>
        <a href="<?= baseUrl('admin/students.php') ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script src="<?= baseUrl('assets/js/validation.js') ?>"></script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
