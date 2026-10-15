<?php
/** Admin-facing HTML head + sidebar layout (open). Expects $pageTitle to be set by the including page. */
$pageTitle = $pageTitle ?? 'Admin';
$currentPage = basename($_SERVER['SCRIPT_NAME']);

function nav_active(string $file, string $current): string
{
    return $file === $current ? 'active' : '';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: 'A';
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | CECT Research Repository Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <span class="sidebar-brand-icon"><i class="bi bi-mortarboard-fill"></i></span>
            <span>CECT Repository</span>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <a class="nav-link <?= nav_active('dashboard.php', $currentPage) ?>" href="dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="sidebar-section-label">Research</div>
            <a class="nav-link <?= nav_active('researches.php', $currentPage) ?>" href="researches.php">
                <i class="bi bi-journal-text"></i> Researches
            </a>
            <a class="nav-link <?= nav_active('add_research.php', $currentPage) ?>" href="add_research.php">
                <i class="bi bi-file-earmark-plus"></i> Add Research
            </a>
            <a class="nav-link <?= nav_active('archived_research.php', $currentPage) ?>" href="archived_research.php">
                <i class="bi bi-archive"></i> Archived Research
            </a>

            <div class="sidebar-section-label">Users</div>
            <a class="nav-link <?= nav_active('students.php', $currentPage) ?>" href="students.php">
                <i class="bi bi-people"></i> Students
            </a>
            <a class="nav-link <?= nav_active('pending_students.php', $currentPage) ?>" href="pending_students.php">
                <i class="bi bi-hourglass-split"></i> Pending Approvals
            </a>

            <div class="sidebar-section-label">System</div>
            <a class="nav-link <?= nav_active('logs.php', $currentPage) ?>" href="logs.php">
                <i class="bi bi-clock-history"></i> Activity Logs
            </a>
        </nav>
        <div class="sidebar-logout">
            <a class="nav-link" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </aside>
    <div class="admin-content">
        <header class="admin-topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <p class="app-page-breadcrumb mb-1">Admin Panel</p>
                <h1 class="app-page-title"><?= e($pageTitle) ?></h1>
            </div>
            <div class="user-chip">
                <span class="user-chip-avatar"><?= e(initials($adminName)) ?></span>
                <span>
                    <span class="user-chip-name d-block"><?= e($adminName) ?></span>
                    <span class="user-chip-role">Administrator</span>
                </span>
            </div>
        </header>
        <div class="admin-body-content">
<?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
