<?php
/**
 * admin/pending_students.php — Review and bulk-approve pending student registrations.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

// Handle bulk approve POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_approve'])) {
    verifyCsrf();
    $ids = array_filter(array_map('intval', $_POST['selected_ids'] ?? []));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $now = date('Y-m-d H:i:s');
        $stmt = getDB()->prepare(
            "UPDATE students SET status='active', approved_at=?, approved_by=?
             WHERE id IN ($placeholders) AND status='pending'"
        );
        $stmt->execute(array_merge([$now, $_SESSION['admin_id']], $ids));
        $count = $stmt->rowCount();
        foreach ($ids as $sid) logActivity('admin', $_SESSION['admin_id'], 'approved student', $sid);
        setFlash('success', "$count student(s) approved successfully.");
    } else {
        setFlash('warning', 'No students were selected.');
    }
    redirect(baseUrl('admin/pending_students.php'));
}

$pendingStudents = getPendingStudents();

$pageTitle  = 'Pending Approvals';
$activePage = 'pending_students';
include __DIR__ . '/../includes/admin_header.php';
?>

<?php if (empty($pendingStudents)): ?>
  <div class="text-center py-5">
    <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
    <h5 class="mt-3 fw-bold">No Pending Approvals</h5>
    <p class="text-muted">All student registrations have been reviewed.</p>
  </div>
<?php else: ?>

  <form method="POST">
    <?= csrfField() ?>

    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-3 d-flex align-items-center justify-content-between">
        <div>
          <h6 class="mb-0 fw-semibold">
            <span class="badge bg-warning text-dark me-2"><?= count($pendingStudents) ?></span>
            Students Awaiting Approval
          </h6>
        </div>
        <button type="submit" name="bulk_approve" class="btn btn-sm btn-success fw-semibold"
                onclick="return confirm('Approve all selected students?')">
          <i class="bi bi-check-all me-1"></i>Approve Selected
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">
                <input type="checkbox" class="form-check-input" id="selectAll" title="Select All">
              </th>
              <th>Full Name</th>
              <th>Student ID</th>
              <th>Course</th>
              <th>Year Level</th>
              <th>Email</th>
              <th>Registered</th>
              <th class="pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingStudents as $s): ?>
            <tr>
              <td class="ps-4">
                <input type="checkbox" class="form-check-input student-check"
                       name="selected_ids[]" value="<?= (int)$s['id'] ?>">
              </td>
              <td class="fw-semibold"><?= e($s['full_name']) ?></td>
              <td><code><?= e($s['student_id']) ?></code></td>
              <td><span class="badge <?= courseColor($s['course']) ?>"><?= e($s['course']) ?></span></td>
              <td><?= e($s['year_level']) ?></td>
              <td><?= e($s['email']) ?></td>
              <td class="small"><?= date('M j, Y g:i A', strtotime($s['registered_at'])) ?></td>
              <td class="pe-4">
                <a href="<?= baseUrl('admin/approve_student.php?id=' . (int)$s['id']) ?>"
                   class="btn btn-sm btn-success me-1 fw-semibold">
                  <i class="bi bi-check-lg me-1"></i>Approve
                </a>
                <a href="<?= baseUrl('admin/reject_student.php?id=' . (int)$s['id']) ?>"
                   class="btn btn-sm btn-outline-danger fw-semibold"
                   onclick="return confirm('Reject and remove this registration?')">
                  <i class="bi bi-x-lg me-1"></i>Reject
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>

  <script>
    document.getElementById('selectAll').addEventListener('change', function () {
      document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked);
    });
  </script>

<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
