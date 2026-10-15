<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

$userType = trim($_GET['user_type'] ?? '');
$action   = trim($_GET['action'] ?? '');
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page - 1) * $perPage;

$where = [];
$params = [];
if (in_array($userType, ['admin', 'student'], true)) {
    $where[] = 'user_type = :user_type';
    $params['user_type'] = $userType;
}
if ($action !== '') {
    $where[] = 'action LIKE :action';
    $params['action'] = '%' . $action . '%';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM activity_logs $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $db->prepare("SELECT * FROM activity_logs $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$distinctActions = $db->query('SELECT DISTINCT action FROM activity_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

/* AJAX request — return only the results fragment */
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=UTF-8');
    include __DIR__ . '/_logs_fragment.php';
    exit;
}

$pageTitle = 'Activity Logs';
include __DIR__ . '/../includes/admin_header.php';
?>
<form class="d-flex gap-2 mb-3" id="logsFilterForm">
    <select name="user_type" id="logsUserType" class="form-select">
        <option value="">All Users</option>
        <option value="admin" <?= $userType === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="student" <?= $userType === 'student' ? 'selected' : '' ?>>Student</option>
    </select>
    <select name="action" id="logsAction" class="form-select">
        <option value="">All Actions</option>
        <?php foreach ($distinctActions as $a): ?>
            <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
        <?php endforeach; ?>
    </select>
</form>

<div id="logsResultsContainer">
    <?php include __DIR__ . '/_logs_fragment.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultsContainer = document.getElementById('logsResultsContainer');
    const filterForm = document.getElementById('logsFilterForm');
    if (!resultsContainer || !filterForm) {
        return;
    }

    let activeRequest = null;

    function loadResults(page) {
        const params = new URLSearchParams(new FormData(filterForm));
        params.set('ajax', '1');
        if (page) {
            params.set('page', page);
        }

        if (activeRequest) {
            activeRequest.abort();
        }
        const controller = new AbortController();
        activeRequest = controller;

        fetch('logs.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                resultsContainer.innerHTML = html;
            })
            .catch(function () {});
    }

    ['logsUserType', 'logsAction'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', function () { loadResults(1); });
    });

    resultsContainer.addEventListener('click', function (e) {
        const link = e.target.closest('.pagination a[data-page]');
        if (link) {
            e.preventDefault();
            loadResults(link.dataset.page);
        }
    });
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
