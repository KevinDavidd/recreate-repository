<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['bulk_action'] ?? '') === 'approve_all') {
    require_csrf();
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE students SET status = 'active', approved_at = NOW() WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        log_activity('admin', $_SESSION['admin_id'], $_SESSION['admin_name'], 'Bulk Approve', count($ids) . ' student(s) approved');
        flash('success', count($ids) . ' student(s) approved.');
    }
    redirect('pending_students.php');
}

$pending = $db->query("SELECT * FROM students WHERE status = 'pending' ORDER BY created_at ASC")->fetchAll();

$pageTitle = 'Pending Approvals';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="card">
    <div class="card-body">
        <?php if (!$pending): ?>
            <p class="text-muted text-center py-4 mb-0">No pending student registrations.</p>
        <?php else: ?>
            <form method="POST" id="bulkForm">
                <?= csrf_field() ?>
                <input type="hidden" name="bulk_action" value="approve_all">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $s): ?>
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="<?= (int) $s['id'] ?>" class="rowCheck"></td>
                                    <td><?= e($s['student_id']) ?></td>
                                    <td><?= e($s['full_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= e($s['course']) ?></span></td>
                                    <td><?= e($s['email']) ?></td>
                                    <td><?= e(date('M j, Y', strtotime($s['created_at']))) ?></td>
                                    <td class="text-end">
                                        <button type="submit" form="approve-<?= (int) $s['id'] ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button type="submit" form="reject-<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Reject and permanently delete this registration?');">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-cect mt-2">
                    <i class="bi bi-check-all"></i> Approve Selected
                </button>
            </form>

            <?php foreach ($pending as $s): ?>
                <form id="approve-<?= (int) $s['id'] ?>" method="POST" action="approve_student.php" class="d-none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                </form>
                <form id="reject-<?= (int) $s['id'] ?>" method="POST" action="reject_student.php" class="d-none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                </form>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
