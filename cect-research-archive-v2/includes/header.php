<?php
/**
 * includes/header.php — Student-facing top navigation + HTML head
 * Expects $pageTitle to be set before including.
 */
$pageTitle = $pageTitle ?? 'CECT Research Archive';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — CECT Research Archive</title>

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom styles -->
  <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- ── Top Navigation ─────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark cect-navbar sticky-top shadow-sm">
  <div class="container-xl">

    <!-- Brand -->
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= baseUrl('home.php') ?>">
      <i class="bi bi-journal-bookmark-fill fs-4 text-gold"></i>
      <span class="d-none d-sm-inline">CECT Research Archive</span>
      <span class="d-inline d-sm-none">CECT Archive</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="studentNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?= baseUrl('home.php') ?>">
            <i class="bi bi-house-fill me-1"></i>Archive
          </a>
        </li>
      </ul>

      <!-- Student info + profile dropdown -->
      <div class="navbar-nav ms-auto">
        <div class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
             href="#" role="button" data-bs-toggle="dropdown">
            <div class="avatar-circle">
              <?= e(mb_substr($_SESSION['student_full_name'] ?? 'S', 0, 1)) ?>
            </div>
            <span class="d-none d-md-inline">
              <?= e($_SESSION['student_full_name'] ?? 'Student') ?>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li>
              <span class="dropdown-item-text small text-muted">
                <?= e($_SESSION['student_course'] ?? '') ?> •
                <?= e($_SESSION['student_year_level'] ?? '') ?>
              </span>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="<?= baseUrl('profile.php') ?>">
                <i class="bi bi-person-circle me-2"></i>My Profile
              </a>
            </li>
            <li>
              <a class="dropdown-item text-danger" href="<?= baseUrl('logout.php') ?>">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div><!-- /collapse -->
  </div><!-- /container -->
</nav>

<!-- ── Main content wrapper ───────────────────────────────────────── -->
<main class="flex-grow-1">
