<?php
require_once __DIR__ . '/../includes/admin_auth_check.php';

$db = Database::getConnection();

$q            = trim($_GET['q'] ?? '');
$course       = trim($_GET['course'] ?? '');
$researchType = trim($_GET['research_type'] ?? '');
$category     = trim($_GET['category'] ?? '');

$where = ["status = 'active'"];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE :q1 OR authors LIKE :q2)';
    $like = '%' . $q . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
}
if (in_array($course, ['BSIT', 'BSCpE', 'BSCS'], true)) {
    $where[] = 'course = :course';
    $params['course'] = $course;
}
if (in_array($researchType, RESEARCH_TYPES, true)) {
    $where[] = 'research_type = :research_type';
    $params['research_type'] = $researchType;
}
if (in_array($category, RESEARCH_CATEGORIES, true)) {
    $where[] = 'EXISTS (SELECT 1 FROM research_categories rc WHERE rc.research_id = researches.id AND rc.category = :category)';
    $params['category'] = $category;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT * FROM researches $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
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

/* AJAX request — return only the results fragment */
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html; charset=UTF-8');
    include __DIR__ . '/_researches_fragment.php';
    exit;
}

$pageTitle = 'Researches';
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <form id="researchSearchForm" class="flex-grow-1" style="max-width: 720px;">
        <div class="search-suggest-wrap mb-2">
            <input type="text" name="q" id="researchQ" class="form-control" placeholder="Search title or author" value="<?= e($q) ?>" autocomplete="off">
            <span class="spinner-border spinner-border-sm search-live-indicator d-none" id="researchQLiveIndicator"></span>
            <div id="researchQSuggestions" class="search-suggest-list d-none"></div>
        </div>
        <div class="d-flex gap-2">
            <select name="course" id="researchCourse" class="form-select">
                <option value="">All Courses</option>
                <?php foreach (['BSIT', 'BSCpE', 'BSCS'] as $c): ?>
                    <option value="<?= e($c) ?>" <?= $course === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="research_type" id="researchType" class="form-select">
                <option value="">All Types</option>
                <?php foreach (RESEARCH_TYPES as $t): ?>
                    <option value="<?= e($t) ?>" <?= $researchType === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="category" id="categoryFilter" class="form-select">
                <option value="">All Categories</option>
                <?php foreach (RESEARCH_CATEGORIES as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <a href="add_research.php" class="btn btn-cect"><i class="bi bi-file-earmark-plus"></i> Add Research</a>
</div>

<div id="researchResultsContainer">
    <?php include __DIR__ . '/_researches_fragment.php'; ?>
</div>

<div class="modal fade" id="researchPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <span class="badge bg-secondary" id="previewCourseYear"></span>
                <span class="badge bg-cect-gold text-dark" id="previewType"></span>
                <p class="text-muted mb-1 mt-2"><i class="bi bi-people"></i> <span id="previewAuthors"></span></p>
                <p class="text-muted mb-3" id="previewAdviserWrap"><i class="bi bi-person-check"></i> Adviser: <span id="previewAdviser"></span></p>

                <h6 class="fw-bold">Abstract</h6>
                <p id="previewAbstract"></p>

                <h6 class="fw-bold">PDF Preview</h6>
                <iframe id="previewFrame" src="" style="width: 100%; height: 60vh; border: 1px solid #dee2e6; border-radius: 6px;"></iframe>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="archive_research.php">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="archiveResearchId" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Archive Research</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to archive <strong id="archiveResearchTitle"></strong>? It will no longer be visible in the public repository but can be restored at any time.</p>
                    <label class="form-label">Archive Reason <span class="text-muted">(optional)</span></label>
                    <textarea name="archive_reason" class="form-control" rows="3" placeholder="e.g. Outdated, superseded by newer research, incorrect data..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-archive"></i> Archive Research</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const previewModalEl = document.getElementById('researchPreviewModal');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const previewFrame = document.getElementById('previewFrame');
    const resultsContainer = document.getElementById('researchResultsContainer');
    const searchForm = document.getElementById('researchSearchForm');

    function openPreview(d) {
        document.getElementById('previewTitle').textContent = d.title;
        document.getElementById('previewCourseYear').textContent = d.course + ' · ' + d.year;
        document.getElementById('previewType').textContent = d.type;
        document.getElementById('previewAuthors').textContent = d.authors;
        document.getElementById('previewAbstract').textContent = d.abstract;

        const adviserWrap = document.getElementById('previewAdviserWrap');
        if (d.adviser) {
            adviserWrap.style.display = '';
            document.getElementById('previewAdviser').textContent = d.adviser;
        } else {
            adviserWrap.style.display = 'none';
        }

        previewFrame.src = 'preview_research.php?id=' + encodeURIComponent(d.id);
        previewModal.show();
    }

    previewModalEl.addEventListener('hidden.bs.modal', function () {
        previewFrame.src = '';
    });

    const archiveModalEl = document.getElementById('archiveModal');
    const archiveModal = new bootstrap.Modal(archiveModalEl);

    function openArchiveModal(btn) {
        document.getElementById('archiveResearchId').value = btn.dataset.id;
        document.getElementById('archiveResearchTitle').textContent = btn.dataset.title;
        archiveModal.show();
    }

    /* Delegated on the container (not the rows) so it keeps working after AJAX swaps its contents */
    resultsContainer.addEventListener('click', function (e) {
        const link = e.target.closest('.research-title-link');
        if (link) {
            e.preventDefault();
            openPreview(link.dataset);
            return;
        }
        const archiveBtn = e.target.closest('.archive-trigger');
        if (archiveBtn) {
            openArchiveModal(archiveBtn);
        }
    });

    if (!searchForm || !resultsContainer) {
        return;
    }

    const liveIndicator = document.getElementById('researchQLiveIndicator');
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

        fetch('researches.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
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

    ['researchCourse', 'researchType', 'categoryFilter'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', loadResults);
    });

    const researchQ = document.getElementById('researchQ');
    if (researchQ) {
        researchQ.addEventListener('input', loadResults);
    }

    initSuggestions({
        input: researchQ,
        list: document.getElementById('researchQSuggestions'),
        suggestUrl: 'suggest_researches.php',
        onSelect: function (item) {
            researchQ.value = item.title;
            loadResults();
        },
    });
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
