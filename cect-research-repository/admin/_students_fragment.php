<?php
/** Student results table. Rendered both on full page load and via AJAX. */
?>
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$students): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No students found.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['student_id']) ?></td>
                        <td><?= e($s['full_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= e($s['course']) ?></span></td>
                        <td><?= e($s['email']) ?></td>
                        <td>
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($s['status'] === 'suspended'): ?>
                                <span class="badge bg-warning text-dark">Suspended</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($s['status'] !== 'rejected'): ?>
                                <form method="POST" action="suspend_student.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $s['status'] === 'active' ? 'suspend' : 'reactivate' ?>">
                                    <button type="submit" class="btn btn-sm <?= $s['status'] === 'active' ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                                        <?= $s['status'] === 'active' ? 'Suspend' : 'Reactivate' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
