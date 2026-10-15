<?php
require_once __DIR__ . '/includes/auth_check.php';

$db = Database::getConnection();
$stmt = $db->prepare('SELECT * FROM students WHERE id = :id');
$stmt->execute(['id' => $_SESSION['student_id']]);
$student = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword      = (string) ($_POST['new_password'] ?? '');
    $confirmPassword  = (string) ($_POST['confirm_password'] ?? '');

    if (!password_verify($currentPassword, $student['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }
    if (!valid_password($newPassword)) {
        $errors[] = 'New password must be at least 8 characters and include a letter and a number.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $db->prepare('UPDATE students SET password_hash = :hash WHERE id = :id')->execute([
            'hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            'id'   => $student['id'],
        ]);
        log_activity('student', $student['id'], $student['full_name'], 'Password Change');
        flash('success', 'Your password has been updated.');
        redirect('profile.php');
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="user-chip-avatar" style="width:56px;height:56px;font-size:1.3rem;">
                        <?= e(mb_strtoupper(mb_substr($student['full_name'], 0, 1))) ?>
                    </span>
                    <div>
                        <h5 class="fw-bold mb-0"><?= e($student['full_name']) ?></h5>
                        <span class="badge bg-cect-gold text-dark"><?= e($student['course']) ?></span>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>
                <div class="profile-detail-row">
                    <span class="profile-detail-label">Student ID</span>
                    <span><?= e($student['student_id']) ?></span>
                </div>
                <div class="profile-detail-row">
                    <span class="profile-detail-label">Email</span>
                    <span><?= e($student['email']) ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-key"></i> Change Password</h5>
                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-cect"><i class="bi bi-check-circle"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
