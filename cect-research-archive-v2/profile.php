<?php
/**
 * profile.php — Student profile & password change.
 */

require_once __DIR__ . '/includes/auth_check.php';

$student = getStudentById($_SESSION['student_id']);
if (!$student) {
    session_unset(); session_destroy();
    redirect(baseUrl('login.php'));
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $currentPwd  = $_POST['current_password']  ?? '';
    $newPwd      = $_POST['new_password']       ?? '';
    $confirmPwd  = $_POST['confirm_password']   ?? '';

    if ($currentPwd === '' || $newPwd === '' || $confirmPwd === '') {
        $errors[] = 'All password fields are required.';
    } elseif (!password_verify($currentPwd, $student['password'])) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPwd) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($newPwd !== $confirmPwd) {
        $errors[] = 'New passwords do not match.';
    } else {
        $hash = password_hash($newPwd, PASSWORD_BCRYPT);
        getDB()->prepare('UPDATE students SET password = ? WHERE id = ?')
               ->execute([$hash, $student['id']]);
        logActivity('student', $student['id'], 'changed password');
        setFlash('success', 'Password updated successfully.');
        redirect(baseUrl('profile.php'));
    }
}

$pageTitle = 'My Profile';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width:640px">

  <!-- Flash -->
  <?php renderFlash(); ?>

  <!-- Profile Info Card -->
  <div class="card shadow-sm border-0 rounded-4 mb-4">
    <div class="card-header-strip bg-success p-4 text-white rounded-top-4">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar-circle avatar-lg">
          <?= e(mb_substr($student['full_name'], 0, 1)) ?>
        </div>
        <div>
          <h5 class="fw-bold mb-0"><?= e($student['full_name']) ?></h5>
          <div class="text-white-50 small">
            <?= e($student['course']) ?> &bull; <?= e($student['year_level']) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body p-4">
      <h6 class="detail-label mb-3"><i class="bi bi-person-circle me-2 text-success"></i>Account Information</h6>
      <dl class="row mb-0">
        <dt class="col-sm-4 text-muted">Full Name</dt>
        <dd class="col-sm-8"><?= e($student['full_name']) ?></dd>

        <dt class="col-sm-4 text-muted">Student ID</dt>
        <dd class="col-sm-8"><code><?= e($student['student_id']) ?></code></dd>

        <dt class="col-sm-4 text-muted">Course</dt>
        <dd class="col-sm-8">
          <span class="badge <?= courseColor($student['course']) ?>"><?= e($student['course']) ?></span>
        </dd>

        <dt class="col-sm-4 text-muted">Year Level</dt>
        <dd class="col-sm-8"><?= e($student['year_level']) ?></dd>

        <dt class="col-sm-4 text-muted">Email</dt>
        <dd class="col-sm-8"><?= e($student['email']) ?></dd>

        <dt class="col-sm-4 text-muted">Member Since</dt>
        <dd class="col-sm-8"><?= date('F j, Y', strtotime($student['registered_at'])) ?></dd>
      </dl>
    </div>
  </div>

  <!-- Change Password Card -->
  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-4">
      <h6 class="detail-label mb-3">
        <i class="bi bi-key-fill me-2 text-success"></i>Change Password
      </h6>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0 ps-3">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="profile.php" novalidate>
        <?= csrfField() ?>

        <div class="mb-3">
          <label class="form-label" for="current_password">Current Password</label>
          <div class="input-group">
            <input type="password" id="current_password" name="current_password" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="current_password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="new_password">New Password <small class="text-muted">(min. 8 chars)</small></label>
          <div class="input-group">
            <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required>
            <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="new_password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label" for="confirm_password">Confirm New Password</label>
          <div class="input-group">
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary toggle-pwd" data-target="confirm_password">
              <i class="bi bi-eye"></i>
            </button>
          </div>
          <div id="pwdMatchFeedback" class="form-text"></div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-success fw-semibold">
            <i class="bi bi-check-circle me-1"></i>Save Changes
          </button>
          <a href="<?= baseUrl('home.php') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
document.querySelectorAll('.toggle-pwd').forEach(btn => {
  btn.addEventListener('click', function () {
    const inp  = document.getElementById(this.dataset.target);
    const icon = this.querySelector('i');
    if (inp.type === 'password') {
      inp.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      inp.type = 'password';
      icon.className = 'bi bi-eye';
    }
  });
});
// Real-time password match indicator
const np = document.getElementById('new_password');
const cp = document.getElementById('confirm_password');
const fb = document.getElementById('pwdMatchFeedback');
cp.addEventListener('input', () => {
  if (cp.value === '') { fb.textContent = ''; return; }
  if (np.value === cp.value) {
    fb.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Passwords match</span>';
  } else {
    fb.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>Passwords do not match</span>';
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
