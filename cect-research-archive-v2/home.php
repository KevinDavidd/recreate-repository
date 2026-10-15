<?php
/**
 * home.php — Student archive homepage with AJAX search & filters.
 * Returns JSON when ?ajax=1 is set (called by main.js).
 */

require_once __DIR__ . '/includes/auth_check.php';

$filters = [
    'search'        => trim($_GET['search']        ?? ''),
    'course'        => trim($_GET['course']         ?? ''),
    'year'          => trim($_GET['year']           ?? ''),
    'research_type' => trim($_GET['research_type']  ?? ''),
    'sort'          => trim($_GET['sort']           ?? 'newest'),
];
$page = max(1, (int) ($_GET['page'] ?? 1));

// Validate enum filters against allowed values
if (!in_array($filters['course'], array_merge([''], allowedCourses()), true))      $filters['course'] = '';
if (!in_array($filters['research_type'], array_merge([''], allowedTypes()), true)) $filters['research_type'] = '';
if (!in_array($filters['sort'], ['newest','oldest','title_asc'], true))            $filters['sort'] = 'newest';
if ($filters['year'] !== '' && (!ctype_digit($filters['year']) || $filters['year'] < 2020 || $filters['year'] > date('Y'))) {
    $filters['year'] = '';
}

$result     = getResearches($filters, $page);
$researches = $result['data'];
$pg         = $result['pagination'];

// ── AJAX response ──────────────────────────────────────────────────
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');

    $cards = '';
    foreach ($researches as $r) {
        $authors      = array_map('trim', explode(',', $r['authors']));
        $authorList   = implode(', ', array_map('htmlspecialchars', $authors));
        $abstractPrev = mb_substr(strip_tags($r['abstract']), 0, 180);
        if (mb_strlen($r['abstract']) > 180) $abstractPrev .= '…';

        $cards .= '<div class="col-md-6 col-xl-4">'
            . '<div class="research-card card h-100 shadow-sm border-0 rounded-4">'
            . '<div class="card-body p-4">'
            . '<div class="d-flex flex-wrap gap-1 mb-2">'
            . '<span class="badge ' . courseColor($r['course']) . '">' . e($r['course']) . '</span>'
            . '<span class="badge ' . typeColor($r['research_type']) . '">' . e($r['research_type']) . '</span>'
            . '<span class="badge bg-warning text-dark">' . e($r['year']) . '</span>'
            . '</div>'
            . '<h6 class="fw-bold mb-1 research-title">'
            . '<a href="research.php?id=' . (int)$r['id'] . '" class="text-dark text-decoration-none stretched-link">'
            . e($r['title']) . '</a></h6>'
            . '<p class="text-muted small mb-2">' . e($authorList) . '</p>'
            . '<p class="abstract-preview small text-secondary mb-3">' . e($abstractPrev) . '</p>'
            . '<div class="d-flex gap-3 text-muted small">'
            . '<span><i class="bi bi-eye me-1"></i>' . number_format($r['view_count']) . '</span>'
            . '<span><i class="bi bi-download me-1"></i>' . number_format($r['download_count']) . '</span>'
            . '</div>'
            . '</div>'
            . '</div></div>';
    }

    if (empty($researches)) {
        $cards = '<div class="col-12"><div class="text-center py-5 text-muted">'
               . '<i class="bi bi-search fs-1 d-block mb-3"></i>No research papers found.</div></div>';
    }

    echo json_encode([
        'html'        => $cards,
        'total'       => $pg['total'],
        'currentPage' => $pg['current_page'],
        'totalPages'  => $pg['total_pages'],
    ]);
    exit;
}

// ── Full page render ───────────────────────────────────────────────
$pageTitle = 'Archive';
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">

  <!-- Welcome banner -->
  <div class="welcome-banner rounded-4 p-4 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <h5 class="fw-bold mb-1 text-white">
        <i class="bi bi-hand-wave text-gold me-2"></i>
        Welcome, <?= e($_SESSION['student_full_name'] ?? 'Student') ?>!
      </h5>
      <span class="badge bg-light text-dark me-1"><?= e($_SESSION['student_course'] ?? '') ?></span>
      <span class="badge bg-light text-dark"><?= e($_SESSION['student_year_level'] ?? '') ?></span>
    </div>
    <div class="text-white-50 small">
      <i class="bi bi-journals me-1"></i>Browse the CECT Research Archive
    </div>
  </div>

  <!-- Search bar -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
      <div class="input-group input-group-lg">
        <span class="input-group-text bg-white border-end-0">
          <i class="bi bi-search text-success"></i>
        </span>
        <input type="text" id="searchInput" class="form-control border-start-0 ps-0"
               placeholder="Search by title, author, or keyword…"
               value="<?= e($filters['search']) ?>">
        <button class="btn btn-success" id="searchBtn">Search</button>
      </div>
    </div>
  </div>

  <!-- Filters row -->
  <div class="row g-2 mb-4" id="filterRow">
    <div class="col-6 col-md-3">
      <select id="filterCourse" class="form-select form-select-sm">
        <option value="">All Courses</option>
        <?php foreach (allowedCourses() as $c): ?>
          <option value="<?= e($c) ?>" <?= $filters['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select id="filterYear" class="form-select form-select-sm">
        <option value="">All Years</option>
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
          <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-6 col-md-4">
      <select id="filterType" class="form-select form-select-sm">
        <option value="">All Types</option>
        <?php foreach (allowedTypes() as $t): ?>
          <option value="<?= e($t) ?>" <?= $filters['research_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-3">
      <select id="filterSort" class="form-select form-select-sm">
        <option value="newest"    <?= $filters['sort'] === 'newest'    ? 'selected' : '' ?>>Newest First</option>
        <option value="oldest"    <?= $filters['sort'] === 'oldest'    ? 'selected' : '' ?>>Oldest First</option>
        <option value="title_asc" <?= $filters['sort'] === 'title_asc' ? 'selected' : '' ?>>A–Z by Title</option>
      </select>
    </div>
  </div>

  <!-- Results counter -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <span id="resultsCounter" class="text-muted small">
      Showing <strong><?= count($researches) ?></strong> of <strong><?= $pg['total'] ?></strong> research papers
    </span>
    <span class="text-muted small">Page <?= $pg['current_page'] ?> of <?= $pg['total_pages'] ?></span>
  </div>

  <!-- Research grid -->
  <div class="row g-4" id="researchGrid">
    <?php if (empty($researches)): ?>
      <div class="col-12">
        <div class="text-center py-5 text-muted">
          <i class="bi bi-search fs-1 d-block mb-3"></i>No research papers found.
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($researches as $r):
        $authors    = array_map('trim', explode(',', $r['authors']));
        $authorList = implode(', ', $authors);
        $abstractPrev = mb_substr(strip_tags($r['abstract']), 0, 180);
        if (mb_strlen($r['abstract']) > 180) $abstractPrev .= '…';
      ?>
      <div class="col-md-6 col-xl-4">
        <div class="research-card card h-100 shadow-sm border-0 rounded-4">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap gap-1 mb-2">
              <span class="badge <?= courseColor($r['course']) ?>"><?= e($r['course']) ?></span>
              <span class="badge <?= typeColor($r['research_type']) ?>"><?= e($r['research_type']) ?></span>
              <span class="badge bg-warning text-dark"><?= e($r['year']) ?></span>
            </div>
            <h6 class="fw-bold mb-1 research-title">
              <a href="research.php?id=<?= (int)$r['id'] ?>"
                 class="text-dark text-decoration-none stretched-link"><?= e($r['title']) ?></a>
            </h6>
            <p class="text-muted small mb-2"><?= e($authorList) ?></p>
            <p class="abstract-preview small text-secondary mb-3"><?= e($abstractPrev) ?></p>
            <div class="d-flex gap-3 text-muted small">
              <span><i class="bi bi-eye me-1"></i><?= number_format($r['view_count']) ?></span>
              <span><i class="bi bi-download me-1"></i><?= number_format($r['download_count']) ?></span>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div><!-- /researchGrid -->

  <!-- Pagination -->
  <?php if ($pg['total_pages'] > 1): ?>
  <nav class="mt-5" id="paginationNav">
    <ul class="pagination justify-content-center flex-wrap gap-1">
      <?php if ($pg['has_prev']): ?>
        <li class="page-item">
          <button class="page-link" data-page="<?= $pg['current_page'] - 1 ?>">
            <i class="bi bi-chevron-left"></i>
          </button>
        </li>
      <?php endif; ?>

      <?php
      $start = max(1, $pg['current_page'] - 2);
      $end   = min($pg['total_pages'], $pg['current_page'] + 2);
      if ($start > 1): ?>
        <li class="page-item"><button class="page-link" data-page="1">1</button></li>
        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
      <?php endif;
      for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= $i === $pg['current_page'] ? 'active' : '' ?>">
          <button class="page-link" data-page="<?= $i ?>"><?= $i ?></button>
        </li>
      <?php endfor;
      if ($end < $pg['total_pages']): ?>
        <?php if ($end < $pg['total_pages'] - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
        <li class="page-item"><button class="page-link" data-page="<?= $pg['total_pages'] ?>"><?= $pg['total_pages'] ?></button></li>
      <?php endif; ?>

      <?php if ($pg['has_next']): ?>
        <li class="page-item">
          <button class="page-link" data-page="<?= $pg['current_page'] + 1 ?>">
            <i class="bi bi-chevron-right"></i>
          </button>
        </li>
      <?php endif; ?>
    </ul>
  </nav>
  <?php endif; ?>

</div><!-- /container -->

<?php include __DIR__ . '/includes/footer.php'; ?>
