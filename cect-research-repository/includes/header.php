<?php
/** Student-facing HTML head + top navbar. Expects $pageTitle to be set by the including page. */
$pageTitle = $pageTitle ?? APP_NAME;
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSET_VERSION ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light cect-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold" href="home.php">
            <i class="bi bi-mortarboard-fill"></i> CECT Research Repository
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'home.php' ? 'active' : '' ?>" href="home.php"><i class="bi bi-archive"></i> Archive</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>" href="profile.php"><i class="bi bi-person-circle"></i> Profile</a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<main class="container my-4">
<?php
if ($msg = flash('success')): ?>
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
