<?php
/**
 * admin/students.php — All students management table.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$search  = trim($_GET['search']  ?? '');
$course  = trim($_GET['course']  ?? '');
$status  = trim($_GET['status']  ?? '');
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;

// Validate filters
if (!in_array($course, array_merge([''], allowedCourses()), true)) $course = '';
if (!in_array($status, ['','pending','active','suspended','rejected'], true)) $status = '';

$pdo    = getDB();
$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]  = '(full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)';
    $kw       = '%' . $search . '%';
    $params   = array_merge($params, [$kw, $kw, $kw]);
}
if ($course !== '') { $where[] = 'course = ?';  $params[] = $course; }
if ($status !== '') { $where[] = 'status = ?';  $params[] = $status; }

$whereClause = implode(' AND ', $where);
$total       = (int) $pdo->prepare("SELECT COUNT(*) FROM students WHERE $whereClause")
                         ->execute($params) ? $pdo->prepare("SELECT COUNT(*) FROM students WHERE $whereClause")->execute($params) : 0;
// Re-execute for count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE $whereClause");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pg     = paginate($total, $page, $perPage);
$stmt   = $pdo->prepare("SELECT * FROM students WHERE $whereClause ORDER BY registered_at DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle  = 'All Students';
$activePage = 'students';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Toolbar -->
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
  <form method="GET" class="d-flex flex-wrap gap-2">
    <input type="text" name="search" class="form-control form-control-sm"
           placeholder="Name, Student ID, or Email…" value="<?= e($search) ?>" style="width:230px">
    <select name="course" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
      <option value="">All Courses</option>
      <?php foreach (allowedCourses() as $c): ?>
        <option value="<?= e($c) ?>" <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <?php foreach (['pending','active','suspended','rejected'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-search"></i> Search</button>
    <?php if ($search || $course || $status): ?>
      <a href="<?= baseUrl('admin/students.php') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
    <?php endif; ?>
  </form>
  <a href="<?= baseUrl('admin/add_student.php') ?>" class="btn btn-sm btn-success fw-semibold">
    <i class="bi bi-person-plus me-1"></i>Add Student
  </a>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-0">
    <div class="px-4 py-3 border-bottom">
      <span class="text-muted small">
        Showing <strong><?= count($students) ?></strong> of <strong><?= $pg['total'] ?></strong> students
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">Full Name</th>
            <th>Student ID</th>
            <th>Course</th>
            <th>Year</th>
            <th>Email</th>
            <th>Status</th>
            <th>Registered</th>
            <th class="pe-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No students found.</td></tr>
          <?php else: ?>
            <?php foreach ($students as $s): ?>
            <tr>
              <td class="ps-4 fw-semibold"><?= e($s['full_name']) ?></td>
              <td><code><?= e($s['student_id']) ?></code></td>
              <td><span class="badge <?= courseColor($s['course']) ?>"><?= e($s['course']) ?></span></td>
              <td class="small"><?= e($s['year_level']) ?></td>
              <td class="small"><?= e($s['email']) ?></td>
              <td>
                <?php
                  $statusClass = match($s['status']) {
                    'active'    => 'bg-success',
                    'pending'   => 'bg-warning text-dark',
                    'suspended' => 'bg-danger',
                    'rejected'  => 'bg-secondary',
                    default     => 'bg-secondary',
                  };
                ?>
                <span class="badge <?= $statusClass ?>"><?= ucfirst(e($s['status'])) ?></span>
              </td>
              <td class="small"><?= date('M j, Y', strtotime($s['registered_at'])) ?></td>
              <td class="pe-4">
                <?php if ($s['status'] === 'pending'): ?>
                  <a href="<?= baseUrl('admin/approve_student.php?id=' . (int)$s['id']) ?>"
                     class="btn btn-xs btn-success me-1" title="Approve">
                    <i class="bi bi-check-lg"></i>
                  </a>
                  <a href="<?= baseUrl('admin/reject_student.php?id=' . (int)$s['id']) ?>"
                     class="btn btn-xs btn-danger me-1" title="Reject"
                     onclick="return confirm('Reject this student?')">
                    <i class="bi bi-x-lg"></i>
                  </a>
                <?php elseif ($s['status'] === 'active'): ?>
                  <a href="<?= baseUrl('admin/suspend_student.php?id=' . (int)$s['id'] . '&action=suspend') ?>"
                     class="btn btn-xs btn-warning me-1" title="Suspend"
                     onclick="return confirm('Suspend this student?')">
                    <i class="bi bi-pause-circle"></i>
                  </a>
                <?php elseif ($s['status'] === 'suspended'): ?>
                  <a href="<?= baseUrl('admin/suspend_student.php?id=' . (int)$s['id'] . '&action=reactivate') ?>"
                     class="btn btn-xs btn-success me-1" title="Reactivate"
                     onclick="return confirm('Reactivate this student?')">
                    <i class="bi bi-play-circle"></i>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Pagination -->
<?php if ($pg['total_pages'] > 1): ?>
<nav class="mt-4">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
      <li class="page-item <?= $i === $pg['current_page'] ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(['search'=>$search,'course'=>$course,'status'=>$status,'page'=>$i]) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
