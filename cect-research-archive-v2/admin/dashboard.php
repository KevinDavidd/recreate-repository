<?php
/**
 * admin/dashboard.php — Admin dashboard with stats and charts.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$stats          = getDashboardStats();
$recentResearch = getRecentResearches(5);
$recentStudents = getRecentStudents(5);
$pendingStudents= getPendingStudents();

$byYear   = getResearchCountByYear();
$byCourse = getResearchCountByCourse();
$byType   = getResearchCountByType();

// Prepare chart data as JSON
$chartYears  = json_encode(array_column($byYear,   'year'));
$chartYearCt = json_encode(array_map('intval', array_column($byYear,   'cnt')));
$chartCourseLabels = json_encode(array_column($byCourse, 'course'));
$chartCourseCt     = json_encode(array_map('intval', array_column($byCourse, 'cnt')));
$chartTypeLabels   = json_encode(array_column($byType, 'research_type'));
$chartTypeCt       = json_encode(array_map('intval', array_column($byType, 'cnt')));

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
  <?php
  $cards = [
    ['icon'=>'bi-journal-text',      'label'=>'Total Research Papers', 'value'=>number_format($stats['total_researches']),  'color'=>'text-success',  'bg'=>'bg-success-subtle'],
    ['icon'=>'bi-people-fill',       'label'=>'Active Students',       'value'=>number_format($stats['total_students']),    'color'=>'text-primary',  'bg'=>'bg-primary-subtle'],
    ['icon'=>'bi-person-exclamation','label'=>'Pending Approvals',     'value'=>number_format($stats['pending_approvals']), 'color'=>'text-warning',  'bg'=>'bg-warning-subtle'],
    ['icon'=>'bi-download',          'label'=>'Total Downloads',       'value'=>number_format($stats['total_downloads']),   'color'=>'text-info',     'bg'=>'bg-info-subtle'],
  ];
  foreach ($cards as $card):
  ?>
  <div class="col-sm-6 col-xl-3">
    <div class="card border-0 shadow-sm rounded-4 h-100">
      <div class="card-body p-4 d-flex align-items-center gap-3">
        <div class="stat-icon <?= $card['bg'] ?> <?= $card['color'] ?> rounded-3 p-3">
          <i class="bi <?= $card['icon'] ?> fs-4"></i>
        </div>
        <div>
          <div class="h4 fw-bold mb-0 <?= $card['color'] ?>"><?= $card['value'] ?></div>
          <div class="text-muted small"><?= $card['label'] ?></div>
        </div>
      </div>
      <?php if ($card['label'] === 'Pending Approvals' && $stats['pending_approvals'] > 0): ?>
        <a href="<?= baseUrl('admin/pending_students.php') ?>"
           class="card-footer text-center text-warning small py-2 text-decoration-none bg-warning-subtle">
          View Pending Approvals →
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Research Papers per Year</h6>
        <canvas id="chartYear" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-pie-chart-fill me-2 text-success"></i>By Course</h6>
        <canvas id="chartCourse" height="200"></canvas>
      </div>
    </div>
  </div>
</div>
<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-body p-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-bar-chart-steps me-2 text-success"></i>Research Papers by Type</h6>
        <canvas id="chartType" height="80"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Bottom tables -->
<div class="row g-4">

  <!-- Recent Uploads -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history me-2 text-success"></i>Recent Uploads</h6>
          <a href="<?= baseUrl('admin/researches.php') ?>" class="btn btn-sm btn-outline-success">View All</a>
        </div>
      </div>
      <div class="card-body p-4 pt-3">
        <div class="table-responsive">
          <table class="table table-hover align-middle small">
            <thead class="table-light">
              <tr>
                <th>Title</th><th>Course</th><th>Year</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentResearch)): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">No records yet.</td></tr>
              <?php else: ?>
                <?php foreach ($recentResearch as $r): ?>
                <tr>
                  <td>
                    <a href="<?= baseUrl('research.php?id=' . (int)$r['id']) ?>" class="text-dark fw-semibold text-decoration-none">
                      <?= e(mb_substr($r['title'], 0, 40)) ?><?= mb_strlen($r['title']) > 40 ? '…' : '' ?>
                    </a>
                  </td>
                  <td><span class="badge <?= courseColor($r['course']) ?>"><?= e($r['course']) ?></span></td>
                  <td><?= e($r['year']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Approvals quick list -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
        <div class="d-flex align-items-center justify-content-between">
          <h6 class="fw-semibold mb-0">
            <i class="bi bi-person-exclamation me-2 text-warning"></i>Pending Approvals
            <?php if (count($pendingStudents) > 0): ?>
              <span class="badge bg-danger ms-1"><?= count($pendingStudents) ?></span>
            <?php endif; ?>
          </h6>
          <a href="<?= baseUrl('admin/pending_students.php') ?>" class="btn btn-sm btn-outline-warning">View All</a>
        </div>
      </div>
      <div class="card-body p-4 pt-3">
        <?php if (empty($pendingStudents)): ?>
          <div class="text-center text-muted py-3">
            <i class="bi bi-check-circle-fill text-success d-block fs-3 mb-2"></i>
            No pending approvals!
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle small">
              <thead class="table-light"><tr><th>Name</th><th>Course</th><th>Actions</th></tr></thead>
              <tbody>
                <?php foreach (array_slice($pendingStudents, 0, 5) as $s): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= e($s['full_name']) ?></div>
                    <div class="text-muted x-small"><?= e($s['student_id']) ?></div>
                  </td>
                  <td><span class="badge <?= courseColor($s['course']) ?>"><?= e($s['course']) ?></span></td>
                  <td>
                    <a href="<?= baseUrl('admin/approve_student.php?id=' . (int)$s['id']) ?>"
                       class="btn btn-xs btn-success me-1" title="Approve">
                      <i class="bi bi-check-lg"></i>
                    </a>
                    <a href="<?= baseUrl('admin/reject_student.php?id=' . (int)$s['id']) ?>"
                       class="btn btn-xs btn-danger" title="Reject"
                       onclick="return confirm('Reject and remove this student?')">
                      <i class="bi bi-x-lg"></i>
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div><!-- /row -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const GREEN  = '#008000';
const GOLD   = '#F4C430';
const COLORS = ['#008000','#F4C430','#0d6efd','#6f42c1','#fd7e14','#20c997','#dc3545'];

// Bar: Research per Year
new Chart(document.getElementById('chartYear'), {
  type: 'bar',
  data: {
    labels: <?= $chartYears ?>,
    datasets: [{ label: 'Papers', data: <?= $chartYearCt ?>, backgroundColor: GREEN, borderRadius: 6 }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Pie: By Course
new Chart(document.getElementById('chartCourse'), {
  type: 'pie',
  data: {
    labels: <?= $chartCourseLabels ?>,
    datasets: [{ data: <?= $chartCourseCt ?>, backgroundColor: COLORS }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});

// Bar: By Type
new Chart(document.getElementById('chartType'), {
  type: 'bar',
  data: {
    labels: <?= $chartTypeLabels ?>,
    datasets: [{ label: 'Papers', data: <?= $chartTypeCt ?>, backgroundColor: COLORS, borderRadius: 6 }]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
