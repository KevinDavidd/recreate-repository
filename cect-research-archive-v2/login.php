<?php
/**
 * login.php — Login page
 * Split-panel layout: campus photo left, login form right.
 */

require_once __DIR__ . '/includes/functions.php';
startSession();

// Redirect already-logged-in users
if (isAdmin())   redirect(baseUrl('admin/dashboard.php'));
if (isStudent()) {
    $status = $_SESSION['student_status'] ?? '';
    if ($status === 'active')    redirect(baseUrl('home.php'));
    if ($status === 'pending')   redirect(baseUrl('pending.php'));
    if ($status === 'suspended') redirect(baseUrl('suspended.php'));
}

$errors = [];

// ── Handle form submission ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Email / username and password are required.';
    } else {
        $pdo = getDB();

        // Check admin (by username)
        $stmtA = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
        $stmtA->execute([$email]);
        $admin = $stmtA->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            loginSession('admin', $admin);
            logActivity('admin', $admin['id'], 'logged in');
            redirect(baseUrl('admin/dashboard.php'));
        }

        // Check student (by email)
        $stmtS = $pdo->prepare('SELECT * FROM students WHERE email = ? LIMIT 1');
        $stmtS->execute([$email]);
        $student = $stmtS->fetch();

        if ($student && password_verify($password, $student['password'])) {
            loginSession('student', $student);
            logActivity('student', $student['id'], 'logged in');

            match ($student['status']) {
                'active'    => redirect(baseUrl('home.php')),
                'pending'   => redirect(baseUrl('pending.php')),
                'suspended' => redirect(baseUrl('suspended.php')),
                default     => (function() use (&$errors) {
                    $errors[] = 'Your registration was not approved. Contact the CECT department for assistance.';
                })(),
            };
        } elseif (empty($errors)) {
            $errors[] = 'Invalid email / username or password.';
        }
    }
}

$timeout  = isset($_GET['timeout']);
$rejected = isset($_GET['rejected']);

// Campus image path — place the campus photo at assets/images/campus.jpg
$campusImg  = baseUrl('assets/images/campus.jpg');
$imgExists  = file_exists(__DIR__ . '/assets/images/campus.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CECT Research Archive</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">

<div class="container-fluid p-0">
  <div class="row g-0 min-vh-100">

    <!-- ── LEFT: Campus photo panel ─────────────────────────────── -->
    <div class="col-lg-7 d-none d-lg-block login-image-panel">

      <?php if ($imgExists): ?>
        <img src="<?= $campusImg ?>" alt="Wesleyan University-Philippines Campus" class="campus-photo">
      <?php else: ?>
        <!-- Fallback green panel if image not yet placed -->
        <div style="width:100%;height:100%;background:var(--cect-green);"></div>
      <?php endif; ?>

      <!-- Overlay text at bottom -->
      <div class="login-image-overlay">
        <div class="gold-bar"></div>
        <div class="university-label">
          <i class="bi bi-journal-bookmark-fill me-2" style="color:var(--cect-gold)"></i>
          CECT Research Archive
        </div>
        <div class="university-sub mt-1">
          College of Engineering, Computer and Technology
        </div>
        <div class="university-sub">
          Wesleyan University-Philippines &bull; Nueva Ecija
        </div>
      </div>
    </div>

    <!-- ── RIGHT: Login form panel ──────────────────────────────── -->
    <div class="col-12 col-lg-5 login-form-panel">

      <!-- Mobile-only header (hidden on desktop) -->
      <div class="d-lg-none text-center mb-4 p-3"
           style="background:var(--cect-green);margin:-1.5rem -1.25rem 1.5rem;">
        <i class="bi bi-journal-bookmark-fill text-gold fs-2"></i>
        <div class="text-white fw-bold mt-1">CECT Research Archive</div>
        <div class="text-white-50 small">Wesleyan University-Philippines</div>
      </div>

      <div style="max-width:400px;width:100%;margin:0 auto;">

        <!-- Logo + title -->
        <div class="text-center mb-4">
          <div class="login-logo mx-auto mb-3">
            <i class="bi bi-journal-bookmark-fill fs-3" style="color:var(--cect-green)"></i>
          </div>
          <h4 class="login-title mb-1">Welcome Back</h4>
          <p class="login-subtitle mb-0">Sign in to access the Research Archive</p>
        </div>

        <!-- Alerts -->
        <?php if ($timeout): ?>
          <div class="alert alert-warning alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-clock me-1"></i>
            Session expired due to inactivity. Please log in again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if ($rejected): ?>
          <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-x-circle me-1"></i>
            Your registration was not approved. Contact the CECT department.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i><?= e($err) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endforeach; ?>

        <!-- Login form -->
        <form method="POST" action="login.php" novalidate>
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold" for="email">
              Email / Username
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white">
                <i class="bi bi-person" style="color:var(--cect-green)"></i>
              </span>
              <input type="text" id="email" name="email" class="form-control border-start-0 ps-0"
                     placeholder="Email address or admin username"
                     value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold" for="password">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-white">
                <i class="bi bi-lock" style="color:var(--cect-green)"></i>
              </span>
              <input type="password" id="password" name="password"
                     class="form-control border-start-0 ps-0"
                     placeholder="Enter your password" required>
              <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-success w-100 fw-bold py-2 mb-3">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
          </button>
        </form>

        <hr class="login-divider">

        <p class="text-center text-muted small mb-0">
          Don't have an account?
          <a href="register.php" class="fw-semibold text-decoration-none"
             style="color:var(--cect-green)">Register here</a>
        </p>

      </div><!-- /max-width wrapper -->

      <!-- Footer note at very bottom -->
      <div class="text-center text-muted mt-auto pt-5" style="font-size:.72rem">
        &copy; <?= date('Y') ?> College of Engineering, Computer and Technology
        &nbsp;&bull;&nbsp; Wesleyan University-Philippines
      </div>

    </div><!-- /form panel -->
  </div><!-- /row -->
</div><!-- /container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      pwd.type = 'password';
      icon.className = 'bi bi-eye';
    }
  });
</script>
</body>
</html>
