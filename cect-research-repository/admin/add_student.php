<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$errors = [];
$old = ['student_id' => '', 'full_name' => '', 'course' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $old['student_id'] = trim($_POST['student_id'] ?? '');
    $old['full_name']  = trim($_POST['full_name'] ?? '');
    $old['course']     = trim($_POST['course'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $password          = (string) ($_POST['password'] ?? '');

    if (!valid_student_id($old['student_id'])) {
        $errors[] = 'Student ID must be in the format 00-0000-000.';
    }
    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if (!in_array($old['course'], ['BSIT', 'BSCpE', 'BSCS'], true)) {
        $errors[] = 'Please select a valid course.';
    }
    if (!valid_institutional_email($old['email'])) {
        $errors[] = 'Email must be in the format lastname.firstname@wesleyan.edu.ph (e.g. delacruz.juan@wesleyan.edu.ph).';
    }
    if (!valid_password($password)) {
        $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    }

    if (empty($errors)) {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM students WHERE student_id = :sid OR email = :email');
        $stmt->execute(['sid' => $old['student_id'], 'email' => $old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'A student with this ID or email already exists.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO students (student_id, full_name, course, email, password_hash, status, approved_at)
                 VALUES (:sid, :name, :course, :email, :hash, "active", NOW())'
            );
            $stmt->execute([
                'sid'    => $old['student_id'],
                'name'   => $old['full_name'],
                'course' => $old['course'],
                'email'  => $old['email'],
                'hash'   => password_hash($password, PASSWORD_BCRYPT),
            ]);

            log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Add Student', $old['full_name'] . ' (' . $old['student_id'] . ')');
            flash('success', 'Student account created and activated.');
            redirect('students.php');
        }
    }
}

$pageTitle = 'Add Student';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-body">
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="addStudentForm">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id" id="student_id" class="form-control" placeholder="00-0000-000" value="<?= e($old['student_id']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($old['full_name']) ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course</label>
                    <select name="course" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach (['BSIT', 'BSCpE', 'BSCS'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= $old['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="delacruz.juan@wesleyan.edu.ph" value="<?= e($old['email']) ?>"
                           pattern="[a-zA-Z]+\.[a-zA-Z]+@wesleyan\.edu\.ph"
                           title="Format: lastname.firstname@wesleyan.edu.ph" required>
                    <div class="form-text">Format: lastname.firstname@wesleyan.edu.ph</div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password" class="form-control" required>
                <div class="form-text">This account will be created with active status (no approval needed).</div>
            </div>
            <button type="submit" class="btn btn-cect"><i class="bi bi-person-plus"></i> Create Student</button>
        </form>
    </div>
</div>
<script src="../assets/js/validation.js"></script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
