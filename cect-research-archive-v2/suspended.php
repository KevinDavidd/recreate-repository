<?php
/**
 * suspended.php — Shown to students whose accounts have been suspended.
 */

require_once __DIR__ . '/includes/functions.php';
startSession();

if (!isStudent()) redirect(baseUrl('login.php'));
if ($_SESSION['student_status'] === 'active')  redirect(baseUrl('home.php'));
if ($_SESSION['student_status'] === 'pending') redirect(baseUrl('pending.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Suspended — CECT Research Archive</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="d-flex align-items-center justify-content-center min-vh-100 p-3">
  <div class="card shadow-lg border-0 rounded-4 overflow-hidden text-center" style="max-width:480px;width:100%">

    <div class="p-5">
      <div class="mb-4">
        <span class="suspended-icon d-inline-flex align-items-center justify-content-center rounded-circle">
          <i class="bi bi-slash-circle display-4 text-danger"></i>
        </span>
      </div>
      <h4 class="fw-bold mb-2 text-danger">Account Suspended</h4>
      <p class="text-muted mb-4">
        Hello, <strong><?= e($_SESSION['student_full_name'] ?? 'Student') ?></strong>.
        Your account has been <span class="badge bg-danger">Suspended</span> by the administrator.
      </p>
      <div class="alert alert-danger text-start">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Access Restricted</strong><br>
        <p class="mb-0 mt-2">
          You cannot access the CECT Research Archive while your account is suspended.
          Please contact the <strong>CECT Department</strong> for assistance or clarification.
        </p>
      </div>
      <div class="text-muted small mb-3">
        <i class="bi bi-telephone me-1"></i>CECT Department Office — Wesleyan University-Philippines
      </div>
      <a href="logout.php" class="btn btn-outline-danger w-100">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
