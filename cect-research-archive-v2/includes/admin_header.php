<?php
/**
 * includes/admin_header.php — Admin sidebar layout shell
 * Expects $pageTitle and $activePage to be set before including.
 */
$pageTitle  = $pageTitle  ?? 'Admin';
$activePage = $activePage ?? '';
$pendingCount = getPendingStudentsCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — CECT Admin</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
</head>
<body class="admin-body">

<!-- ── Mobile top bar ─────────────────────────────────────────────── -->
<nav class="navbar navbar-dark cect-navbar d-lg-none shadow-sm">
  <div class="container-fluid">
    <span class="navbar-brand fw-bold">
      <i class="bi bi-shield-lock-fill text-gold me-2"></i>CECT Admin
    </span>
    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>
</nav>

<!-- ── Sidebar wrapper ────────────────────────────────────────────── -->
<div class="d-flex">

  <!-- Sidebar (desktop: always visible; mobile: offcanvas) -->
  <div class="offcanvas-lg offcanvas-start admin-sidebar" id="adminSidebar" tabindex="-1">
    <div class="offcanvas-header d-lg-none border-bottom border-secondary">
      <span class="fw-bold text-white">CECT Admin</span>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar"></button>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column h-100">

      <!-- Brand header (desktop only) -->
      <div class="sidebar-brand d-none d-lg-flex align-items-center p-4 border-bottom border-secondary">
        <i class="bi bi-shield-lock-fill text-gold fs-4 me-2"></i>
        <div>
          <div class="fw-bold text-white lh-1">CECT Admin</div>
          <div class="text-white-50 small">Wesleyan University-PH</div>
        </div>
      </div>

      <!-- Admin info -->
      <div class="px-4 py-3 border-bottom border-secondary">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar-circle avatar-sm">
            <?= e(mb_substr($_SESSION['admin_full_name'] ?? 'A', 0, 1)) ?>
          </div>
          <div>
            <div class="text-white small fw-semibold"><?= e($_SESSION['admin_full_name'] ?? 'Admin') ?></div>
            <div class="text-white-50 x-small">Administrator</div>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar-nav flex-grow-1 py-3">
        <div class="nav-section-label px-4 mb-1">MAIN</div>

        <a href="<?= baseUrl('admin/dashboard.php') ?>"
           class="sidebar-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
          <i class="bi bi-speedometer2"></i>Dashboard
        </a>

        <div class="nav-section-label px-4 mt-3 mb-1">RESEARCH</div>

        <a href="<?= baseUrl('admin/researches.php') ?>"
           class="sidebar-link <?= $activePage === 'researches' ? 'active' : '' ?>">
          <i class="bi bi-journal-text"></i>All Research
        </a>
        <a href="<?= baseUrl('admin/add_research.php') ?>"
           class="sidebar-link <?= $activePage === 'add_research' ? 'active' : '' ?>">
          <i class="bi bi-plus-circle"></i>Add Research
        </a>

        <div class="nav-section-label px-4 mt-3 mb-1">STUDENTS</div>

        <a href="<?= baseUrl('admin/students.php') ?>"
           class="sidebar-link <?= $activePage === 'students' ? 'active' : '' ?>">
          <i class="bi bi-people-fill"></i>All Students
        </a>
        <a href="<?= baseUrl('admin/pending_students.php') ?>"
           class="sidebar-link <?= $activePage === 'pending_students' ? 'active' : '' ?>">
          <i class="bi bi-person-exclamation"></i>Pending Approvals
          <?php if ($pendingCount > 0): ?>
            <span class="badge bg-danger ms-auto"><?= $pendingCount ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= baseUrl('admin/add_student.php') ?>"
           class="sidebar-link <?= $activePage === 'add_student' ? 'active' : '' ?>">
          <i class="bi bi-person-plus"></i>Add Student
        </a>

        <div class="nav-section-label px-4 mt-3 mb-1">SYSTEM</div>

        <a href="<?= baseUrl('admin/logs.php') ?>"
           class="sidebar-link <?= $activePage === 'logs' ? 'active' : '' ?>">
          <i class="bi bi-clock-history"></i>Activity Logs
        </a>
      </nav>

      <!-- Logout -->
      <div class="p-3 border-top border-secondary">
        <a href="<?= baseUrl('logout.php') ?>" class="sidebar-link text-danger-emphasis">
          <i class="bi bi-box-arrow-right"></i>Logout
        </a>
      </div>
    </div>
  </div><!-- /sidebar -->

  <!-- ── Page content area ─────────────────────────────────────────── -->
  <div class="admin-content flex-grow-1">

    <!-- Top bar -->
    <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3 border-bottom shadow-sm">
      <h5 class="mb-0 fw-semibold"><?= e($pageTitle) ?></h5>
      <div class="d-flex align-items-center gap-3">
        <?php if ($pendingCount > 0): ?>
          <a href="<?= baseUrl('admin/pending_students.php') ?>" class="btn btn-sm btn-warning fw-semibold">
            <i class="bi bi-bell-fill me-1"></i><?= $pendingCount ?> Pending
          </a>
        <?php endif; ?>
        <span class="text-muted small d-none d-md-inline">
          <?= date('l, F j, Y') ?>
        </span>
      </div>
    </div>

    <!-- Flash messages -->
    <div class="px-4 pt-3">
      <?php renderFlash(); ?>
    </div>

    <!-- Page body starts here -->
    <div class="p-4">
