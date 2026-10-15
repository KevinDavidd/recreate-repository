<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

$q            = trim($_GET['q'] ?? '');
$course       = trim($_GET['course'] ?? '');
$researchType = trim($_GET['research_type'] ?? '');
$category     = trim($_GET['category'] ?? '');

$where = ["r.status = 'archived'"];
$params = [];
if ($q !== '') {
    $where[] = '(r.title LIKE :q1 OR r.authors LIKE :q2)';
    $like = '%' . $q . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
}
if (in_array($course, ['BSIT', 'BSCpE', 'BSCS'], true)) {
    $where[] = 'r.course = :course';
    $params['course'] = $course;
}
if (in_array($researchType, RESEARCH_TYPES, true)) {
    $where[] = 'r.research_type = :research_type';
    $params['research_type'] = $researchType;
}
if (in_array($category, RESEARCH_CATEGORIES, true)) {
    $where[] = 'EXISTS (SELECT 1 FROM research_categories rc WHERE rc.research_id = r.id AND rc.category = :category)';
    $params['category'] = $category;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $db->prepare(
    "SELECT r.*, a.full_name AS archived_by_name
     FROM researches r
     LEFT JOIN admins a ON a.id = r.archived_by
     $whereSql
     ORDER BY r.archived_at DESC"
);
$stmt->execute($params);
$archived = $stmt->fetchAll();

if ($archived) {
    $ids = array_column($archived, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $catStmt = $db->prepare("SELECT research_id, category FROM research_categories WHERE research_id IN ($placeholders) ORDER BY category");
    $catStmt->execute($ids);
    $categoriesByResearch = [];
    foreach ($catStmt->fetchAll() as $row) {
        $categoriesByResearch[$row['research_id']][] = $row['category'];
    }
    foreach ($archived as &$r) {
        $r['categories'] = $categoriesByResearch[$r['id']] ?? [];
    }
    unset($r);
}

/* AJAX request — return only the results fragment */
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=UTF-8');
    include __DIR__ . '/_archived_fragment.php';
    exit;
}

$pageTitle = 'Archived Research';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <form id="archivedSearchForm" class="flex-grow-1" style="max-width: 720px;">
        <div class="search-suggest-wrap mb-2">
            <input type="text" name="q" id="archivedQ" class="form-control" placeholder="Search title or author" value="<?= e($q) ?>" autocomplete="off">
            <span class="spinner-border spinner-border-sm search-live-indicator d-none" id="archivedQLiveIndicator"></span>
            <div id="archivedQSuggestions" class="search-suggest-list d-none"></div>
        </div>
        <div class="d-flex gap-2">
            <select name="course" id="archivedCourse" class="form-select">
                <option value="">All Courses</option>
                <?php foreach (['BSIT', 'BSCpE', 'BSCS'] as $c): ?>
                    <option value="<?= e($c) ?>" <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="research_type" id="archivedType" class="form-select">
                <option value="">All Types</option>
                <?php foreach (RESEARCH_TYPES as $t): ?>
                    <option value="<?= e($t) ?>" <?= $researchType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category" id="archivedCategory" class="form-select">
                <option value="">All Categories</option>
                <?php foreach (RESEARCH_CATEGORIES as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div id="archivedResultsContainer">
    <?php include __DIR__ . '/_archived_fragment.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resultsContainer = document.getElementById('archivedResultsContainer');
    const searchForm = document.getElementById('archivedSearchForm');
    if (!resultsContainer || !searchForm) {
        return;
    }

    const liveIndicator = document.getElementById('archivedQLiveIndicator');
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

        fetch('archived_research.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
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

    ['archivedCourse', 'archivedType', 'archivedCategory'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', loadResults);
    });

    const archivedQ = document.getElementById('archivedQ');
    if (archivedQ) {
        archivedQ.addEventListener('input', loadResults);
    }

    initSuggestions({
        input: archivedQ,
        list: document.getElementById('archivedQSuggestions'),
        suggestUrl: 'suggest_archived.php',
        onSelect: function (item) {
            archivedQ.value = item.title;
            loadResults();
        },
    });
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
