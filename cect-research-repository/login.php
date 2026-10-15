<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();

// Already logged in? Send to the right place.
if (!empty($_SESSION['user_role'])) {
    redirect($_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'home.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $auth = authenticate_user($email, $password);

        if ($auth === null) {
            $error = 'Invalid email or password.';
        } elseif ($auth['role'] === 'admin') {
            $error = 'Please use the Administrator Login page.';
        } else {
            $student = $auth['record'];
            $db = Database::getConnection();

            switch ($student['status']) {
                case 'pending':
                    session_regenerate_id(true);
                    $_SESSION['pending_name'] = $student['full_name'];
                    redirect('pending.php');
                    // no break — redirect() exits

                case 'suspended':
                    redirect('suspended.php');

                case 'rejected':
                    $error = 'Your registration was not approved. Please contact the department.';
                    break;

                case 'active':
                    session_regenerate_id(true);
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_name'] = $student['full_name'];
                    $_SESSION['user_role'] = 'student';
                    $_SESSION['last_activity'] = time();

                    $db->prepare('UPDATE students SET last_login = NOW() WHERE id = :id')->execute(['id' => $student['id']]);
                    log_activity('student', $student['id'], $student['full_name'], 'Login', 'Student logged in');

                    redirect('home.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | CECT Research Repository</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSET_VERSION ?>">
</head>
<body class="login-split-body">
<div class="login-split">
    <div class="login-left">
        <img src="assets/images/cect-logo.jpg" alt="CECT Logo" class="login-left-logo">
        <h1 class="login-left-title">CECT Research Repository</h1>
        <p class="login-left-subtitle">Wesleyan University-Philippines<br>College of Engineering, Computer and Technology</p>
    </div>

    <div class="login-right">
        <div class="login-card">
            <h2 class="login-card-heading">Student Login</h2>
            <p class="text-muted mb-4">Log in to access the research repository</p>

            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning">Your session has expired due to inactivity. Please log in again.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
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
                Not registered yet? <a href="register.php">Register here</a>
            </p>
            <p class="text-center mt-2 mb-0 small">
                <a href="admin/login.php"><i class="bi bi-shield-lock"></i> Administrator Login</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
