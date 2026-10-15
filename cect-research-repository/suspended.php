<?php require_once __DIR__ . '/config/database.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended | CECT Research Repository</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-card text-center">
        <i class="bi bi-slash-circle auth-logo text-danger"></i>
        <h4 class="fw-bold mt-2">Account Suspended</h4>
        <p class="mt-3">Your access to the CECT Research Repository has been suspended.</p>
        <p class="text-muted">Please contact the CECT department office if you believe this is a mistake.</p>
        <a href="login.php" class="btn btn-cect mt-3">Back to Login</a>
    </div>
</div>
</body>
</html>
