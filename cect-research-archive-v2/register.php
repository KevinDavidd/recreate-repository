<?php
/**
 * register.php — Student self-registration
 * Creates a PENDING account; admin must approve before login is allowed.
 */

require_once __DIR__ . '/includes/functions.php';
startSession();

// Already logged in? Go home
if (isAdmin())   redirect(baseUrl('admin/dashboard.php'));
if (isStudent()) redirect(baseUrl('home.php'));

$errors = [];
$old    = [];  // repopulate form on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $old = [
        'full_name'  => trim($_POST['full_name']  ?? ''),
        'student_id' => trim($_POST['student_id'] ?? ''),
        'course'     => trim($_POST['course']      ?? ''),
        'year_level' => trim($_POST['year_level']  ?? ''),
        'email'      => trim($_POST['email']       ?? ''),
    ];
    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // ── Server-side validation ────────────────────────────────────────
    if ($old['full_name'] === '') $errors[] = 'Full name is required.';

    if (!isValidStudentId($old['student_id'])) {
        $errors[] = 'Student ID must match the format XX-XXXX-XXX (e.g. 22-2003-264). Only digits and hyphens.';
    }

    if (!in_array($old['course'], allowedCourses(), true)) {
        $errors[] = 'Please select a valid course.';
    }

    if (!in_array($old['year_level'], allowedYearLevels(), true)) {
        $errors[] = 'Please select a valid year level.';
    }

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Uniqueness checks
    if (empty($errors)) {
        $pdo = getDB();

        $dupId = $pdo->prepare('SELECT id FROM students WHERE student_id = ?');
        $dupId->execute([$old['student_id']]);
        if ($dupId->fetch()) $errors[] = 'This Student ID is already registered.';

        $dupEmail = $pdo->prepare('SELECT id FROM students WHERE email = ?');
        $dupEmail->execute([$old['email']]);
        if ($dupEmail->fetch()) $errors[] = 'This email address is already registered.';
    }

    // ── Insert if valid ────────────────────────────────────────────────
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare(
            'INSERT INTO students (student_id, full_name, course, year_level, email, password, status)
             VALUES (?, ?, ?, ?, ?, ?, "pending")'
        )->execute([
            $old['student_id'], $old['full_name'], $old['course'],
            $old['year_level'], $old['email'], $hash,
        ]);

        setFlash('success', 'Registration submitted! Please wait for admin approval.');
        redirect(baseUrl('pending.php'));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — CECT Research Archive</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-wrapper d-flex align-items-center justify-content-center min-vh-100 p-3">
  <div class="auth-card card shadow-lg border-0 rounded-4 overflow-hidden" style="max-width:520px;width:100%">

    <!-- Header strip -->
    <div class="auth-header text-center p-4">
      <div class="auth-logo mb-3">
        <i class="bi bi-person-plus-fill display-4 text-gold"></i>
      </div>
      <h4 class="fw-bold text-white mb-1">Create Student Account</h4>
      <p class="text-white-50 small mb-0">CECT Research Archive — Wesleyan University-PH</p>
    </div>

    <!-- Card body -->
    <div class="card-body p-4">

      <!-- Errors -->
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-1"></i>
          <strong>Please fix the following:</strong>
          <ul class="mb-0 mt-1 ps-3">
            <?php foreach ($errors as $e): ?>
              <li><?= e($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php" id="registerForm" novalidate>
        <?= csrfField() ?>

        <!-- Full Name -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="full_name">
            <i class="bi bi-person me-1 text-success"></i>Full Name <span class="text-danger">*</span>
          </label>
          <input type="text" id="full_name" name="full_name" class="form-control"
                 placeholder="e.g. Juan dela Cruz"
                 value="<?= e($old['full_name'] ?? '') ?>" required>
        </div>

        <!-- Student ID -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="student_id">
            <i class="bi bi-card-text me-1 text-success"></i>Student ID <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="text" id="student_id" name="student_id" class="form-control"
                   placeholder="e.g. 22-2003-264" maxlength="12"
                   value="<?= e($old['student_id'] ?? '') ?>"
                   pattern="^\d{2}-\d{4}-\d{3}$" required>
            <span class="input-group-text" id="sidStatus">
              <i class="bi bi-dash text-muted" id="sidIcon"></i>
            </span>
          </div>
          <div class="form-text text-muted">
            Format: <strong>YY-NNNN-NNN</strong> — digits only in each segment
            (e.g. <strong>22-2003-264</strong>)
          </div>
          <div id="sidFeedback" class="form-text"></div>
        </div>

        <!-- Course & Year Level (2-column) -->
        <div class="row g-3 mb-3">
          <div class="col-sm-7">
            <label class="form-label fw-semibold" for="course">
              <i class="bi bi-book me-1 text-success"></i>Course <span class="text-danger">*</span>
            </label>
            <select id="course" name="course" class="form-select" required>
              <option value="">— Select Course —</option>
              <?php foreach (allowedCourses() as $c): ?>
                <option value="<?= e($c) ?>" <?= ($old['course'] ?? '') === $c ? 'selected' : '' ?>>
                  <?= e($c) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-5">
            <label class="form-label fw-semibold" for="year_level">
              <i class="bi bi-mortarboard me-1 text-success"></i>Year <span class="text-danger">*</span>
            </label>
            <select id="year_level" name="year_level" class="form-select" required>
              <option value="">— Year —</option>
              <?php foreach (allowedYearLevels() as $y): ?>
                <option value="<?= e($y) ?>" <?= ($old['year_level'] ?? '') === $y ? 'selected' : '' ?>>
                  <?= e($y) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Email -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="email">
            <i class="bi bi-envelope me-1 text-success"></i>Email Address <span class="text-danger">*</span>
          </label>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="your.email@wesleyan.edu.ph"
                 value="<?= e($old['email'] ?? '') ?>" required>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <label class="form-label fw-semibold" for="password">
            <i class="bi bi-lock me-1 text-success"></i>Password <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Minimum 8 characters" minlength="8" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePwd1">
              <i class="bi bi-eye" id="eyeIcon1"></i>
            </button>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
          <label class="form-label fw-semibold" for="confirm_password">
            <i class="bi bi-lock-fill me-1 text-success"></i>Confirm Password <span class="text-danger">*</span>
          </label>
          <div class="input-group">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                   placeholder="Re-enter your password" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePwd2">
              <i class="bi bi-eye" id="eyeIcon2"></i>
            </button>
          </div>
          <div id="pwdMatchFeedback" class="form-text"></div>
        </div>

        <button type="submit" class="btn btn-success w-100 fw-semibold py-2">
          <i class="bi bi-person-check me-2"></i>Register
        </button>
      </form>

      <hr class="my-3">
      <p class="text-center mb-0 text-muted small">
        Already have an account?
        <a href="login.php" class="fw-semibold text-success">Login here</a>
      </p>
    </div><!-- /card-body -->
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/validation.js"></script>
</body>
</html>
