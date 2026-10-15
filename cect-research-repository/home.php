<?php
require_once __DIR__ . '/includes/auth_check.php';

$db = Database::getConnection();

$q            = trim($_GET['q'] ?? '');
$course       = trim($_GET['course'] ?? '');
$researchType = trim($_GET['research_type'] ?? '');
$category     = trim($_GET['category'] ?? '');
$year         = trim($_GET['year'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$where = ["status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = '(title LIKE :q1 OR authors LIKE :q2 OR abstract LIKE :q3)';
    $like = '%' . $q . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}
if (in_array($course, ['BSIT', 'BSCpE', 'BSCS'], true)) {
    $where[] = 'course = :course';
    $params['course'] = $course;
}
if (in_array($researchType, RESEARCH_TYPES, true)) {
    $where[] = 'research_type = :research_type';
    $params['research_type'] = $researchType;
}
if ($year !== '' && ctype_digit($year)) {
    $where[] = 'year_published = :year';
    $params['year'] = $year;
}
if (in_array($category, RESEARCH_CATEGORIES, true)) {
    $where[] = 'EXISTS (SELECT 1 FROM research_categories rc WHERE rc.research_id = researches.id AND rc.category = :category)';
    $params['category'] = $category;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM researches $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetch()['total'];
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $db->prepare(
    "SELECT id, title, authors, course, research_type, year_published, download_count
     FROM researches $whereSql
     ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$researches = $stmt->fetchAll();

if ($researches) {
    $ids = array_column($researches, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $catStmt = $db->prepare("SELECT research_id, category FROM research_categories WHERE research_id IN ($placeholders) ORDER BY category");
    $catStmt->execute($ids);
    $categoriesByResearch = [];
    foreach ($catStmt->fetchAll() as $row) {
        $categoriesByResearch[$row['research_id']][] = $row['category'];
    }
    foreach ($researches as &$r) {
        $r['categories'] = $categoriesByResearch[$r['id']] ?? [];
    }
    unset($r);
}

$yearsStmt = $db->query('SELECT DISTINCT year_published FROM researches ORDER BY year_published DESC');
$years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);

/* AJAX request — return only the results fragment */
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=UTF-8');
    include __DIR__ . '/includes/_results_fragment.php';
    exit;
}

$pageTitle = 'Research Archive';
include __DIR__ . '/includes/header.php';
?>
<div class="archive-hero mb-4">
    <h1 class="archive-hero-title"><i class="bi bi-archive"></i> Research Archive</h1>
    <p class="archive-hero-subtitle mb-0">Search, browse, and download published CECT capstone and research work.</p>
</div>

<div class="card filter-card mb-4">
    <div class="card-body">
        <form id="searchForm" class="row g-2">
            <div class="col-12 search-suggest-wrap">
                <input type="text" name="q" id="q" class="form-control" placeholder="Search by title or author..." value="<?= e($q) ?>" autocomplete="off">
                <span class="spinner-border spinner-border-sm search-live-indicator d-none" id="qLiveIndicator"></span>
                <div id="qSuggestions" class="search-suggest-list d-none"></div>
            </div>
            <div class="col-md-3">
                <select name="course" id="course" class="form-select">
                    <option value="">All Courses</option>
                    <?php foreach (['BSIT', 'BSCpE', 'BSCS'] as $c): ?>
                        <option value="<?= e($c) ?>" <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="research_type" id="research_type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach (RESEARCH_TYPES as $t): ?>
                        <option value="<?= e($t) ?>" <?= $researchType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category" id="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach (RESEARCH_CATEGORIES as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="year" id="year" class="form-select">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= e($y) ?>" <?= $year == $y ? 'selected' : '' ?>><?= e($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div id="resultsContainer">
    <?php include __DIR__ . '/includes/_results_fragment.php'; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
