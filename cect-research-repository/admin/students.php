<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

$q      = trim($_GET['q'] ?? '');
$course = trim($_GET['course'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ["status != 'pending'"];
$params = [];
if ($q !== '') {
    $where[] = '(full_name LIKE :q1 OR student_id LIKE :q2 OR email LIKE :q3)';
    $like = '%' . $q . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}
if (in_array($course, ['BSIT', 'BSCpE', 'BSCS'], true)) {
    $where[] = 'course = :course';
    $params['course'] = $course;
}
if (in_array($status, ['active', 'suspended', 'rejected'], true)) {
    $where[] = 'status = :status';
    $params['status'] = $status;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare("SELECT * FROM students $whereSql ORDER BY full_name ASC");
$stmt->execute($params);
$students = $stmt->fetchAll();

/* AJAX request — return only the results fragment */
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=UTF-8');
    include __DIR__ . '/_students_fragment.php';
    exit;
}

$pageTitle = 'Students';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <form id="studentSearchForm" class="flex-grow-1" style="max-width: 720px;">
        <div class="search-suggest-wrap mb-2">
            <input type="text" name="q" id="studentQ" class="form-control" placeholder="Search name, ID, or email" value="<?= e($q) ?>" autocomplete="off">
            <span class="spinner-border spinner-border-sm search-live-indicator d-none" id="studentQLiveIndicator"></span>
            <div id="studentQSuggestions" class="search-suggest-list d-none"></div>
        </div>
        <div class="d-flex gap-2">
            <select name="course" id="studentCourse" class="form-select">
                <option value="">All Courses</option>
                <?php foreach (['BSIT', 'BSCpE', 'BSCS'] as $c): ?>
                    <option value="<?= e($c) ?>" <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" id="studentStatus" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach (['active', 'suspended', 'rejected'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <a href="add_student.php" class="btn btn-cect"><i class="bi bi-person-plus"></i> Add Student</a>
</div>

<div id="studentResultsContainer">
    <?php include __DIR__ . '/_students_fragment.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultsContainer = document.getElementById('studentResultsContainer');
    const searchForm = document.getElementById('studentSearchForm');
    if (!searchForm || !resultsContainer) {
        return;
    }

    const liveIndicator = document.getElementById('studentQLiveIndicator');
    let activeRequest = null;

    function loadResults() {
        const params = new URLSearchParams(new FormData(searchForm));
        params.set('ajax', '1');

        if (activeRequest) {
            activeRequest.abort();
        }
        const controller = new AbortController();
        activeRequest = controller;

        if (liveIndicator) liveIndicator.classList.remove('d-none');

        fetch('students.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                resultsContainer.innerHTML = html;
                if (liveIndicator) liveIndicator.classList.add('d-none');
            })
            .catch(function (err) {
                if (err.name !== 'AbortError' && liveIndicator) {
                    liveIndicator.classList.add('d-none');
                }
            });
    }

    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadResults();
    });

    ['studentCourse', 'studentStatus'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', loadResults);
    });

    const studentQ = document.getElementById('studentQ');
    if (studentQ) {
        studentQ.addEventListener('input', loadResults);
    }

    initSuggestions({
        input: studentQ,
        list: document.getElementById('studentQSuggestions'),
        suggestUrl: 'suggest_students.php',
        onSelect: function (item) {
            studentQ.value = item.title;
            loadResults();
        },
    });
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
