<?php
/**
 * pending.php — Shown to students whose accounts are awaiting approval.
 */

require_once __DIR__ . '/includes/functions.php';
startSession();

// Redirect if not logged in or not pending
if (!isStudent()) redirect(baseUrl('login.php'));
if ($_SESSION['student_status'] === 'active')    redirect(baseUrl('home.php'));
if ($_SESSION['student_status'] === 'suspended') redirect(baseUrl('suspended.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Pending — CECT Research Archive</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">

<div class="d-flex align-items-center justify-content-center min-vh-100 p-3">
  <div class="card shadow-lg border-0 rounded-4 overflow-hidden text-center" style="max-width:480px;width:100%">

    <div class="p-5">
      <div class="mb-4">
        <span class="pending-icon d-inline-flex align-items-center justify-content-center rounded-circle">
          <i class="bi bi-hourglass-split display-4 text-warning"></i>
        </span>
      </div>
      <h4 class="fw-bold mb-2">Account Under Review</h4>
      <p class="text-muted mb-4">
        Hello, <strong><?= e($_SESSION['student_full_name'] ?? 'Student') ?></strong>!
        Your account is currently <span class="badge bg-warning text-dark">Pending Approval</span>.
      </p>
      <div class="alert alert-warning text-start">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>What happens next?</strong><br>
        <ul class="mb-0 mt-2 ps-3">
          <li>The CECT administrator will review your registration.</li>
          <li>Once approved, you can log in and access the Research Archive.</li>
          <li>If you have questions, contact the CECT department office.</li>
        </ul>
      </div>
      <a href="logout.php" class="btn btn-outline-secondary w-100 mt-2">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
