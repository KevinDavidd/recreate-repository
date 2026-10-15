<?php
/**
 * research.php — Research detail page (student-facing).
 * Increments view_count on each visit.
 */

require_once __DIR__ . '/includes/auth_check.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    setFlash('error', 'Invalid research ID.');
    redirect(baseUrl('home.php'));
}

$research = getResearchById($id);
if (!$research) {
    setFlash('error', 'Research paper not found.');
    redirect(baseUrl('home.php'));
}

// Increment view count
getDB()->prepare('UPDATE researches SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
logActivity('student', $_SESSION['student_id'], 'viewed', $id);

$authors      = array_map('trim', explode(',', $research['authors']));
$panelMembers = $research['panel_members']
    ? array_map('trim', explode(',', $research['panel_members']))
    : [];

$pdfExists = !empty($research['pdf_file'])
    && file_exists(UPLOAD_DIR . $research['pdf_file']);

$pageTitle = $research['title'];
include __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?= baseUrl('home.php') ?>"><i class="bi bi-house-fill me-1"></i>Archive</a>
      </li>
      <li class="breadcrumb-item active">Research Detail</li>
    </ol>
  </nav>

  <div class="row g-4">

    <!-- ── LEFT: Main detail panel ─────────────────────────────────── -->
    <div class="col-lg-8">
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">

        <!-- Green header strip -->
        <div class="card-header-strip bg-success p-4 text-white">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge <?= courseColor($research['course']) ?> fs-6 px-3 py-2">
              <?= e($research['course']) ?>
            </span>
            <span class="badge <?= typeColor($research['research_type']) ?> fs-6 px-3 py-2">
              <?= e($research['research_type']) ?>
            </span>
            <span class="badge bg-warning text-dark fs-6 px-3 py-2">
              <i class="bi bi-calendar3 me-1"></i><?= e($research['year']) ?>
            </span>
          </div>
          <h1 class="h4 fw-bold mb-0 lh-sm"><?= e($research['title']) ?></h1>
        </div>

        <div class="card-body p-4">

          <!-- Authors -->
          <div class="detail-section mb-4">
            <h6 class="detail-label"><i class="bi bi-people-fill me-2 text-success"></i>Authors</h6>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($authors as $author): ?>
                <span class="badge bg-light text-dark border fw-normal px-3 py-2">
                  <i class="bi bi-person me-1"></i><?= e($author) ?>
                </span>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Abstract -->
          <div class="detail-section mb-4">
            <h6 class="detail-label"><i class="bi bi-file-text-fill me-2 text-success"></i>Abstract</h6>
            <div class="abstract-body p-3 bg-light rounded-3">
              <p class="mb-0 lh-lg text-secondary" style="text-align:justify;">
                <?= nl2br(e($research['abstract'])) ?>
              </p>
            </div>
          </div>

          <!-- Research Committee -->
          <div class="detail-section mb-4">
            <h6 class="detail-label"><i class="bi bi-mortarboard-fill me-2 text-success"></i>Research Committee</h6>
            <div class="row g-3">
              <div class="col-sm-6">
                <div class="committee-card p-3 bg-light rounded-3 h-100">
                  <div class="text-muted small mb-1 fw-semibold text-uppercase">Adviser</div>
                  <div class="fw-semibold"><?= e($research['adviser']) ?></div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="committee-card p-3 bg-light rounded-3 h-100">
                  <div class="text-muted small mb-1 fw-semibold text-uppercase">Panel Chair</div>
                  <div class="fw-semibold"><?= e($research['panel_chair']) ?></div>
                </div>
              </div>
              <?php if (!empty($panelMembers)): ?>
              <div class="col-12">
                <div class="committee-card p-3 bg-light rounded-3">
                  <div class="text-muted small mb-2 fw-semibold text-uppercase">Panel Members</div>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($panelMembers as $member): ?>
                      <span class="badge bg-white border text-dark fw-normal px-3 py-2">
                        <?= e($member) ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- PDF Embed -->
          <div class="detail-section">
            <h6 class="detail-label"><i class="bi bi-file-earmark-pdf-fill me-2 text-danger"></i>Research Document</h6>
            <?php if ($pdfExists): ?>
              <div class="rounded-3 overflow-hidden border mb-3">
                <iframe src="<?= baseUrl('download.php?id=' . (int)$research['id'] . '&view=1') ?>"
                        class="w-100" style="height:520px;border:none;" title="PDF Viewer">
                  <p>Your browser doesn't support embedded PDFs.
                     <a href="<?= baseUrl('download.php?id=' . (int)$research['id']) ?>">Download the PDF</a>.
                  </p>
                </iframe>
              </div>
            <?php else: ?>
              <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="bi bi-info-circle-fill"></i>
                <span>PDF preview is not available for this entry (placeholder data).</span>
              </div>
            <?php endif; ?>
          </div>

        </div><!-- /card-body -->
      </div><!-- /card -->
    </div><!-- /col-lg-8 -->

    <!-- ── RIGHT: Action panel ──────────────────────────────────────── -->
    <div class="col-lg-4">

      <!-- Download card -->
      <div class="card shadow-sm border-0 rounded-4 mb-4" style="position:sticky;top:80px">
        <div class="card-body p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-download me-2 text-success"></i>Actions</h6>

          <?php if ($pdfExists): ?>
            <a href="<?= baseUrl('download.php?id=' . (int)$research['id']) ?>"
               class="btn btn-success w-100 fw-semibold mb-2">
              <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
            </a>
          <?php else: ?>
            <button class="btn btn-secondary w-100 fw-semibold mb-2" disabled>
              <i class="bi bi-file-earmark-pdf me-2"></i>PDF Unavailable
            </button>
          <?php endif; ?>

          <a href="<?= baseUrl('home.php') ?>" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-2"></i>Back to Archive
          </a>

          <!-- Stats & Quick info -->
          <hr>
          <ul class="list-unstyled small text-muted mb-0">
            <li class="mb-2">
              <i class="bi bi-folder me-2 text-success"></i>
              <strong>Course:</strong> <?= e($research['course']) ?>
            </li>
            <li class="mb-2">
              <i class="bi bi-tag me-2 text-success"></i>
              <strong>Type:</strong> <?= e($research['research_type']) ?>
            </li>
            <li class="mb-2">
              <i class="bi bi-calendar me-2 text-success"></i>
              <strong>Year:</strong> <?= e($research['year']) ?>
            </li>
            <li class="mb-2">
              <i class="bi bi-eye me-2 text-success"></i>
              <strong>Views:</strong> <?= number_format($research['view_count'] + 1) ?>
            </li>
            <li class="mb-2">
              <i class="bi bi-download me-2 text-success"></i>
              <strong>Downloads:</strong> <?= number_format($research['download_count']) ?>
            </li>
            <li>
              <i class="bi bi-clock me-2 text-success"></i>
              <strong>Added:</strong> <?= date('M j, Y', strtotime($research['created_at'])) ?>
            </li>
          </ul>
        </div>
      </div>

    </div><!-- /col-lg-4 -->
  </div><!-- /row -->
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
