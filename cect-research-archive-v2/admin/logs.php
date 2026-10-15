<?php
/**
 * admin/logs.php — Activity log viewer with filters.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$pdo = getDB();

$filterAction   = trim($_GET['action']     ?? '');
$filterUserType = trim($_GET['user_type']  ?? '');
$filterDateFrom = trim($_GET['date_from']  ?? '');
$filterDateTo   = trim($_GET['date_to']    ?? '');
$page           = max(1, (int) ($_GET['page'] ?? 1));
$perPage        = 25;

if (!in_array($filterUserType, ['','admin','student'], true)) $filterUserType = '';

$where  = ['1=1'];
$params = [];

if ($filterAction !== '') {
    $where[] = 'al.action LIKE ?';
    $params[] = '%' . $filterAction . '%';
}
if ($filterUserType !== '') {
    $where[] = 'al.user_type = ?';
    $params[] = $filterUserType;
}
if ($filterDateFrom !== '') {
    $where[] = 'al.performed_at >= ?';
    $params[] = $filterDateFrom . ' 00:00:00';
}
if ($filterDateTo !== '') {
    $where[] = 'al.performed_at <= ?';
    $params[] = $filterDateTo . ' 23:59:59';
}

$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al WHERE $whereClause");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$pg = paginate($total, $page, $perPage);

// Join with admins / students for user name
$stmt = $pdo->prepare(
    "SELECT al.*,
            CASE al.user_type
              WHEN 'admin'   THEN a.full_name
              WHEN 'student' THEN s.full_name
            END AS user_name,
            r.title AS research_title
     FROM activity_logs al
     LEFT JOIN admins   a ON al.user_type='admin'   AND al.user_id = a.id
     LEFT JOIN students s ON al.user_type='student' AND al.user_id = s.id
     LEFT JOIN researches r ON al.research_id = r.id
     WHERE $whereClause
     ORDER BY al.performed_at DESC
     LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distinct action types for filter dropdown
$actionTypes = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")
                   ->fetchAll(PDO::FETCH_COLUMN);

$pageTitle  = 'Activity Logs';
$activePage = 'logs';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Filters -->
<form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Action</label>
        <select name="action" class="form-select form-select-sm">
          <option value="">All Actions</option>
          <?php foreach ($actionTypes as $a): ?>
            <option value="<?= e($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>><?= e($a) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">User Type</label>
        <select name="user_type" class="form-select form-select-sm">
          <option value="">All</option>
          <option value="admin"   <?= $filterUserType === 'admin'   ? 'selected' : '' ?>>Admin</option>
          <option value="student" <?= $filterUserType === 'student' ? 'selected' : '' ?>>Student</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Date From</label>
        <input type="date" name="date_from" class="form-control form-control-sm"
               value="<?= e($filterDateFrom) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Date To</label>
        <input type="date" name="date_to" class="form-control form-control-sm"
               value="<?= e($filterDateTo) ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-success flex-fill">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <a href="<?= baseUrl('admin/logs.php') ?>" class="btn btn-sm btn-outline-secondary flex-fill">Clear</a>
      </div>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-0">
    <div class="px-4 py-3 border-bottom">
      <span class="text-muted small">
        Showing <strong><?= count($logs) ?></strong> of <strong><?= $pg['total'] ?></strong> log entries
      </span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th class="ps-4">#</th>
            <th>User</th>
            <th>Type</th>
            <th>Action</th>
            <th>Research</th>
            <th>IP Address</th>
            <th class="pe-4">Date & Time</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">No log entries found.</td></tr>
          <?php else: ?>
            <?php $offset = ($pg['current_page'] - 1) * $perPage; ?>
            <?php foreach ($logs as $i => $log): ?>
            <tr>
              <td class="ps-4 text-muted"><?= $offset + $i + 1 ?></td>
              <td class="fw-semibold"><?= e($log['user_name'] ?? 'Deleted User') ?></td>
              <td>
                <span class="badge <?= $log['user_type'] === 'admin' ? 'bg-danger' : 'bg-primary' ?>">
                  <?= ucfirst(e($log['user_type'])) ?>
                </span>
              </td>
              <td>
                <?php
                  $actionClass = match(true) {
                    str_contains($log['action'], 'downloaded') => 'text-success fw-semibold',
                    str_contains($log['action'], 'deleted')    => 'text-danger',
                    str_contains($log['action'], 'approved')   => 'text-success',
                    str_contains($log['action'], 'suspended')  => 'text-warning',
                    str_contains($log['action'], 'logged in')  => 'text-primary',
                    default                                     => '',
                  };
                ?>
                <span class="<?= $actionClass ?>"><?= e(ucfirst($log['action'])) ?></span>
              </td>
              <td>
                <?php if ($log['research_title']): ?>
                  <a href="<?= baseUrl('research.php?id=' . (int)$log['research_id']) ?>"
                     class="text-decoration-none" target="_blank">
                    <?= e(mb_substr($log['research_title'], 0, 40)) ?><?= mb_strlen($log['research_title'] ?? '') > 40 ? '…' : '' ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?= e($log['ip_address']) ?></td>
              <td class="pe-4 text-muted"><?= date('M j, Y g:i A', strtotime($log['performed_at'])) ?></td>
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
  <ul class="pagination justify-content-center flex-wrap gap-1">
    <?php if ($pg['has_prev']): ?>
      <li class="page-item">
        <a class="page-link" href="?<?= http_build_query(array_merge(['action'=>$filterAction,'user_type'=>$filterUserType,'date_from'=>$filterDateFrom,'date_to'=>$filterDateTo], ['page' => $pg['current_page'] - 1])) ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
      </li>
    <?php endif; ?>
    <?php
    $start = max(1, $pg['current_page'] - 2);
    $end   = min($pg['total_pages'], $pg['current_page'] + 2);
    for ($i = $start; $i <= $end; $i++): ?>
      <li class="page-item <?= $i === $pg['current_page'] ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge(['action'=>$filterAction,'user_type'=>$filterUserType,'date_from'=>$filterDateFrom,'date_to'=>$filterDateTo], ['page' => $i])) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <?php if ($pg['has_next']): ?>
      <li class="page-item">
        <a class="page-link" href="?<?= http_build_query(array_merge(['action'=>$filterAction,'user_type'=>$filterUserType,'date_from'=>$filterDateFrom,'date_to'=>$filterDateTo], ['page' => $pg['current_page'] + 1])) ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
