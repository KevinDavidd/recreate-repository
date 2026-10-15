<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

$totalResearches = (int) $db->query("SELECT COUNT(*) FROM researches WHERE status = 'active'")->fetchColumn();
$archivedCount   = (int) $db->query("SELECT COUNT(*) FROM researches WHERE status = 'archived'")->fetchColumn();
$totalStudents   = (int) $db->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn();
$pendingCount    = (int) $db->query("SELECT COUNT(*) FROM students WHERE status = 'pending'")->fetchColumn();
$totalViews      = (int) $db->query('SELECT COALESCE(SUM(download_count), 0) FROM researches')->fetchColumn();

$byCourse = $db->query("SELECT course, COUNT(*) AS total FROM researches WHERE status = 'active' GROUP BY course ORDER BY total DESC")->fetchAll();
$byYear   = $db->query("SELECT year_published, COUNT(*) AS total FROM researches WHERE status = 'active' GROUP BY year_published ORDER BY year_published")->fetchAll();

$maxCourse = $byCourse ? max(array_column($byCourse, 'total')) : 0;
$maxYear   = $byYear ? max(array_column($byYear, 'total')) : 0;

$recentResearches = $db->query("SELECT id, title, course, year_published, created_at FROM researches WHERE status = 'active' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentPending    = $db->query("SELECT id, full_name, student_id, course, created_at FROM students WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    <div class="col">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon-chip"><i class="bi bi-journal-text"></i></div>
                <div class="stat-value"><?= $totalResearches ?></div>
                <div class="stat-label">Total Researches</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon-chip"><i class="bi bi-people"></i></div>
                <div class="stat-value"><?= $totalStudents ?></div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon-chip"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Approvals</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon-chip"><i class="bi bi-eye"></i></div>
                <div class="stat-value"><?= $totalViews ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-icon-chip"><i class="bi bi-archive"></i></div>
                <div class="stat-value"><?= $archivedCount ?></div>
                <div class="stat-label">Archived Research</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Researches by Course</h6>
                <table class="table table-sm stats-table mb-0">
                    <tbody id="courseStatsBody">
                        <?php foreach ($byCourse as $row): ?>
                            <?php $pct = $maxCourse > 0 ? round($row['total'] / $maxCourse * 100) : 0; ?>
                            <tr>
                                <td class="stats-row-label"><?= e($row['course']) ?></td>
                                <td class="stats-row-bar"><div class="stats-bar"><div class="stats-bar-fill" style="width: <?= $pct ?>%"></div></div></td>
                                <td class="stats-row-count text-end"><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Researches by Year</h6>
                <table class="table table-sm stats-table mb-0">
                    <tbody id="yearStatsBody">
                        <?php foreach ($byYear as $row): ?>
                            <?php $pct = $maxYear > 0 ? round($row['total'] / $maxYear * 100) : 0; ?>
                            <tr>
                                <td class="stats-row-label"><?= e($row['year_published']) ?></td>
                                <td class="stats-row-bar"><div class="stats-bar"><div class="stats-bar-fill" style="width: <?= $pct ?>%"></div></div></td>
                                <td class="stats-row-count text-end"><?= (int) $row['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Recently Added Researches</h6>
                <?php if (!$recentResearches): ?>
                    <p class="text-muted small mb-0">No researches yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentResearches as $r): ?>
                            <li class="list-group-item d-flex align-items-center gap-3 px-0">
                                <span class="stat-icon-chip" style="width:36px;height:36px;font-size:1rem;margin-bottom:0;"><i class="bi bi-file-earmark-text"></i></span>
                                <span class="flex-grow-1"><?= e($r['title']) ?></span>
                                <span class="badge bg-secondary"><?= e($r['course']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Recent Pending Students</h6>
                <?php if (!$recentPending): ?>
                    <p class="text-muted small mb-0">No pending registrations.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentPending as $s): ?>
                            <li class="list-group-item d-flex align-items-center gap-3 px-0">
                                <span class="stat-icon-chip" style="width:36px;height:36px;font-size:1rem;margin-bottom:0;"><i class="bi bi-person"></i></span>
                                <span class="flex-grow-1"><?= e($s['full_name']) ?> <small class="text-muted">(<?= e($s['student_id']) ?>)</small></span>
                                <a href="pending_students.php" class="btn btn-sm btn-outline-cect">Review</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const courseBody = document.getElementById('courseStatsBody');
    const yearBody = document.getElementById('yearStatsBody');

    function renderRows(tbody, rows, labelKey) {
        const max = rows.reduce((m, r) => Math.max(m, r.total), 0);
        tbody.innerHTML = rows.map(function (r) {
            const pct = max > 0 ? Math.round((r.total / max) * 100) : 0;
            return '<tr>'
                + '<td class="stats-row-label">' + r[labelKey] + '</td>'
                + '<td class="stats-row-bar"><div class="stats-bar"><div class="stats-bar-fill" style="width: ' + pct + '%"></div></div></td>'
                + '<td class="stats-row-count text-end">' + r.total + '</td>'
                + '</tr>';
        }).join('');
    }

    function refreshStats() {
        fetch('dashboard_stats.php', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                renderRows(courseBody, data.byCourse, 'course');
                renderRows(yearBody, data.byYear, 'year_published');
            })
            .catch(function () { /* silently skip this refresh cycle */ });
    }

    setInterval(refreshStats, 5000);
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
