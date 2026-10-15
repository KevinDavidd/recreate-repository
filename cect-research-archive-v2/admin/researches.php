<?php
/**
 * admin/researches.php — Research management table.
 */

require_once __DIR__ . '/../includes/admin_auth_check.php';

$filters = [
    'search'        => trim($_GET['search']        ?? ''),
    'course'        => trim($_GET['course']         ?? ''),
    'year'          => trim($_GET['year']           ?? ''),
    'research_type' => trim($_GET['research_type']  ?? ''),
    'sort'          => trim($_GET['sort']           ?? 'newest'),
];
if (!in_array($filters['course'], array_merge([''], allowedCourses()), true))      $filters['course'] = '';
if (!in_array($filters['research_type'], array_merge([''], allowedTypes()), true)) $filters['research_type'] = '';

$page   = max(1, (int) ($_GET['page'] ?? 1));
$result = getResearches($filters, $page, ITEMS_PER_PAGE);
$pg     = $result['pagination'];
$list   = $result['data'];

$pageTitle  = 'Research Management';
$activePage = 'researches';
include __DIR__ . '/../includes/admin_header.php';
?>

<!-- Toolbar -->
<div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4">
  <form method="GET" class="d-flex flex-wrap gap-2" id="filterForm">
    <input type="text" name="search" class="form-control form-control-sm"
           placeholder="Search title / author…" value="<?= e($filters['search']) ?>" style="width:220px">
    <select name="course" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
      <option value="">All Courses</option>
      <?php foreach (allowedCourses() as $c): ?>
        <option value="<?= e($c) ?>" <?= $filters['course'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="year" class="form-select form-select-sm" style="width:110px" onchange="this.form.submit()">
      <option value="">All Years</option>
      <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
        <option value="<?= $y ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <select name="research_type" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
      <option value="">All Types</option>
      <?php foreach (allowedTypes() as $t): ?>
        <option value="<?= e($t) ?>" <?= $filters['research_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm btn-success">
      <i class="bi bi-search"></i> Search
    </button>
    <?php if (array_filter($filters)): ?>
      <a href="<?= baseUrl('admin/researches.php') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
    <?php endif; ?>
  </form>
  <a href="<?= baseUrl('admin/add_research.php') ?>" class="btn btn-success btn-sm fw-semibold">
    <i class="bi bi-plus-circle me-1"></i>Add Research
  </a>
</div>

<div class="card border-0 shadow-sm rounded-4">
  <div class="card-body p-0">
    <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
      <span class="text-muted small">
        Showing <strong><?= count($list) ?></strong> of <strong><?= $pg['total'] ?></strong> papers
      </span>
      <span class="text-muted small">Page <?= $pg['current_page'] ?> of <?= $pg['total_pages'] ?></span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="ps-4">#</th>
            <th>Title</th>
            <th>Course</th>
            <th>Type</th>
            <th>Year</th>
            <th class="text-center">Views</th>
            <th class="text-center">DLs</th>
            <th class="text-center pe-4">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($list)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No research papers found.</td></tr>
          <?php else: ?>
            <?php $offset = ($pg['current_page'] - 1) * ITEMS_PER_PAGE; ?>
            <?php foreach ($list as $i => $r): ?>
            <tr>
              <td class="ps-4 text-muted small"><?= $offset + $i + 1 ?></td>
              <td>
                <a href="#" class="fw-semibold text-dark text-decoration-none research-preview-link"
                   data-id="<?= (int)$r['id'] ?>">
                  <?= e(mb_substr($r['title'], 0, 55)) ?><?= mb_strlen($r['title']) > 55 ? '…' : '' ?>
                </a>
                <div class="text-muted small"><?= e(mb_substr($r['authors'], 0, 50)) ?></div>
              </td>
              <td><span class="badge <?= courseColor($r['course']) ?>"><?= e($r['course']) ?></span></td>
              <td><span class="badge <?= typeColor($r['research_type']) ?> text-wrap"><?= e($r['research_type']) ?></span></td>
              <td><?= e($r['year']) ?></td>
              <td class="text-center"><?= number_format($r['view_count']) ?></td>
              <td class="text-center"><?= number_format($r['download_count']) ?></td>
              <td class="text-center pe-4">
                <a href="<?= baseUrl('download.php?id=' . (int)$r['id']) ?>"
                   class="btn btn-xs btn-outline-success me-1" title="Download PDF">
                  <i class="bi bi-download"></i>
                </a>
                <a href="<?= baseUrl('admin/edit_research.php?id=' . (int)$r['id']) ?>"
                   class="btn btn-xs btn-outline-primary me-1" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="<?= baseUrl('admin/delete_research.php?id=' . (int)$r['id']) ?>"
                   class="btn btn-xs btn-outline-danger"
                   title="Delete"
                   onclick="return confirm('Delete this research entry and its PDF permanently?')">
                  <i class="bi bi-trash"></i>
                </a>
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
    <?php if ($pg['has_prev']): ?>
      <li class="page-item">
        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pg['current_page'] - 1])) ?>">
          <i class="bi bi-chevron-left"></i>
        </a>
      </li>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $pg['total_pages']; $i++): ?>
      <li class="page-item <?= $i === $pg['current_page'] ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
    <?php if ($pg['has_next']): ?>
      <li class="page-item">
        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $pg['current_page'] + 1])) ?>">
          <i class="bi bi-chevron-right"></i>
        </a>
      </li>
    <?php endif; ?>
  </ul>
</nav>
<?php endif; ?>

<!-- ── Research Preview Modal ──────────────────────────────────── -->
<div class="modal fade" id="researchPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4 overflow-hidden">

      <!-- Modal header (green strip) -->
      <div class="modal-header text-white border-0 p-4" style="background:var(--cect-green)">
        <div class="flex-grow-1 me-3">
          <div id="previewBadges" class="d-flex flex-wrap gap-2 mb-2"></div>
          <h5 class="modal-title fw-bold mb-0 lh-sm" id="previewTitle">Loading…</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Modal body -->
      <div class="modal-body p-4" id="previewBody">
        <div class="text-center py-5">
          <div class="spinner-border text-success" role="status"></div>
        </div>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer border-top pt-3" id="previewFooter"></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
(function () {
  const modal     = new bootstrap.Modal(document.getElementById('researchPreviewModal'));
  const titleEl   = document.getElementById('previewTitle');
  const badgesEl  = document.getElementById('previewBadges');
  const bodyEl    = document.getElementById('previewBody');
  const footerEl  = document.getElementById('previewFooter');
  const baseUrl   = <?= json_encode(rtrim(baseUrl(''), '/')) ?>;

  function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  document.querySelectorAll('.research-preview-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const id = this.dataset.id;

      // Reset
      titleEl.textContent  = 'Loading…';
      badgesEl.innerHTML   = '';
      bodyEl.innerHTML     = '<div class="text-center py-5"><div class="spinner-border text-success" role="status"></div></div>';
      footerEl.innerHTML   = '';
      modal.show();

      fetch(baseUrl + '/admin/research_preview.php?id=' + id)
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.error) { bodyEl.innerHTML = '<p class="text-danger">' + esc(d.error) + '</p>'; return; }

          titleEl.textContent = d.title;

          // Badges
          badgesEl.innerHTML =
            '<span class="badge bg-warning text-dark px-3 py-2">' + esc(d.course) + '</span>' +
            '<span class="badge bg-light text-dark border px-3 py-2">' + esc(d.research_type) + '</span>' +
            '<span class="badge bg-white text-dark border px-3 py-2"><i class="bi bi-calendar3 me-1"></i>' + esc(d.year) + '</span>';

          // Authors
          const authorBadges = d.authors.split(',').map(function (a) {
            return '<span class="badge bg-light text-dark border fw-normal px-3 py-1"><i class="bi bi-person me-1"></i>' + esc(a.trim()) + '</span>';
          }).join(' ');

          // Panel members
          let panelHtml = '';
          if (d.panel_members) {
            const members = d.panel_members.split(',').map(function (m) {
              return '<span class="badge bg-white border text-dark fw-normal px-3 py-1">' + esc(m.trim()) + '</span>';
            }).join(' ');
            panelHtml = '<div class="col-12"><div class="p-3 bg-light rounded-3 border-start border-3 border-success">' +
              '<div class="text-muted x-small fw-bold text-uppercase mb-2">Panel Members</div>' +
              '<div class="d-flex flex-wrap gap-2">' + members + '</div></div></div>';
          }

          bodyEl.innerHTML =
            '<div class="mb-4">' +
              '<h6 class="detail-label"><i class="bi bi-people-fill me-2 text-success"></i>Authors</h6>' +
              '<div class="d-flex flex-wrap gap-2">' + authorBadges + '</div>' +
            '</div>' +
            '<div class="mb-4">' +
              '<h6 class="detail-label"><i class="bi bi-file-text-fill me-2 text-success"></i>Abstract</h6>' +
              '<div class="p-3 bg-light rounded-3"><p class="mb-0 lh-lg text-secondary" style="text-align:justify">' + esc(d.abstract).replace(/\n/g,'<br>') + '</p></div>' +
            '</div>' +
            '<div class="mb-4">' +
              '<h6 class="detail-label"><i class="bi bi-mortarboard-fill me-2 text-success"></i>Research Committee</h6>' +
              '<div class="row g-3">' +
                '<div class="col-sm-6"><div class="p-3 bg-light rounded-3 border-start border-3 border-success">' +
                  '<div class="text-muted x-small fw-bold text-uppercase mb-1">Adviser</div>' +
                  '<div class="fw-semibold">' + esc(d.adviser) + '</div>' +
                '</div></div>' +
                '<div class="col-sm-6"><div class="p-3 bg-light rounded-3 border-start border-3 border-success">' +
                  '<div class="text-muted x-small fw-bold text-uppercase mb-1">Panel Chair</div>' +
                  '<div class="fw-semibold">' + esc(d.panel_chair) + '</div>' +
                '</div></div>' +
                panelHtml +
              '</div>' +
            '</div>' +
            '<div class="d-flex flex-wrap gap-3 text-muted small">' +
              '<span><i class="bi bi-eye me-1 text-success"></i><strong>Views:</strong> ' + Number(d.view_count).toLocaleString() + '</span>' +
              '<span><i class="bi bi-download me-1 text-success"></i><strong>Downloads:</strong> ' + Number(d.download_count).toLocaleString() + '</span>' +
              '<span><i class="bi bi-clock me-1 text-success"></i><strong>Added:</strong> ' + esc(d.created_at) + '</span>' +
            '</div>';

          // Footer buttons
          let dlBtn = d.pdf_exists
            ? '<a href="' + baseUrl + '/download.php?id=' + d.id + '" class="btn btn-success btn-sm fw-semibold"><i class="bi bi-file-earmark-pdf me-1"></i>Download PDF</a>'
            : '<button class="btn btn-secondary btn-sm" disabled><i class="bi bi-file-earmark-pdf me-1"></i>PDF Unavailable</button>';

          footerEl.innerHTML =
            dlBtn +
            '<a href="' + baseUrl + '/admin/edit_research.php?id=' + d.id + '" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>' +
            '<button type="button" class="btn btn-outline-secondary btn-sm ms-auto" data-bs-dismiss="modal">Close</button>';
        })
        .catch(function () {
          bodyEl.innerHTML = '<p class="text-danger">Failed to load preview. Please try again.</p>';
        });
    });
  });
})();
</script>
