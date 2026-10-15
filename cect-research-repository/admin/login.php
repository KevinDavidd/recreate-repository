<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();

// Already logged in? Send to the right place.
if (!empty($_SESSION['user_role'])) {
    redirect($_SESSION['user_role'] === 'admin' ? 'dashboard.php' : '../home.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both username/email and password.';
    } else {
        $auth = authenticate_user($email, $password);

        if ($auth === null) {
            $error = 'Invalid username or password.';
        } elseif ($auth['role'] === 'student') {
            $error = 'This portal is for administrators only.';
        } else {
            $admin = $auth['record'];
            $db = Database::getConnection();

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['last_activity'] = time();

            $db->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id')->execute(['id' => $admin['id']]);
            log_activity('admin', $admin['id'], $admin['full_name'], 'Login', 'Admin logged in');

            redirect('dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Login | CECT Research Repository</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="login-split-body">
<div class="login-split">
    <div class="login-left">
        <img src="../assets/images/cect-logo.jpg" alt="CECT Logo" class="login-left-logo">
        <h1 class="login-left-title">CECT Research Repository</h1>
        <p class="login-left-subtitle">Wesleyan University-Philippines<br>College of Engineering, Computer and Technology</p>
    </div>

    <div class="login-right">
        <div class="login-card">
            <div class="admin-login-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <h2 class="login-card-heading text-center">Administrator Portal</h2>
            <p class="admin-login-subtitle">CECT Research Repository &middot; Restricted Access</p>

            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning">Your session has expired due to inactivity. Please log in again.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <input type="text" name="email" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-cect w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Log In
                </button>
            </form>

            <p class="text-center mt-3 mb-0 small">
                <a href="../login.php"><i class="bi bi-arrow-left"></i> Student? Go to Student Login</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
