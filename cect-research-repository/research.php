<?php
require_once __DIR__ . '/includes/auth_check.php';

$id = (int) ($_GET['id'] ?? 0);
$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM researches WHERE id = :id AND status = 'active'");
$stmt->execute(['id' => $id]);
$research = $stmt->fetch();

if ($research) {
    $research['categories'] = get_research_categories($db, $id);
}

if (!$research) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="text-center py-5"><i class="bi bi-exclamation-triangle display-4 text-warning"></i>
          <p class="mt-3">The requested research paper could not be found.</p>
          <a href="home.php" class="btn btn-cect">Back to Archive</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $research['title'];
include __DIR__ . '/includes/header.php';
?>
<a href="home.php" class="btn btn-link ps-0 mb-3"><i class="bi bi-arrow-left"></i> Back to Archive</a>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card research-detail-card h-100">
            <div class="card-body">
                <h4 class="fw-bold"><?= e($research['title']) ?></h4>
                <p class="text-muted mb-3"><i class="bi bi-people"></i> <?= e($research['authors']) ?></p>

                <h6 class="fw-bold mt-4">Abstract</h6>
                <p><?= nl2br(e($research['abstract'])) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card research-detail-card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Research Details</h6>

                <div class="mb-3">
                    <span class="badge bg-cect-gold text-dark mb-1"><?= e($research['course']) ?> &middot; <?= e($research['year_published']) ?></span>
                    <span class="badge bg-secondary mb-1"><?= e($research['research_type']) ?></span>
                    <?php foreach ($research['categories'] as $cat): ?>
                        <span class="badge category-badge"><?= e($cat) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($research['adviser']): ?>
                    <p class="text-muted small mb-2"><i class="bi bi-person-check"></i> Adviser: <?= e($research['adviser']) ?></p>
                <?php endif; ?>

                <p class="text-muted small mb-1">
                    <i class="bi bi-file-earmark-pdf"></i> <?= e($research['original_file_name']) ?>
                </p>
                <p class="text-muted small mb-3">
                    <?= format_file_size((int) $research['file_size']) ?> &middot;
                    <i class="bi bi-eye"></i> <?= (int) $research['download_count'] ?> views
                </p>

                <a href="view_research.php?id=<?= (int) $research['id'] ?>" target="_blank" rel="noopener" class="btn btn-cect w-100">
                    <i class="bi bi-eye"></i> View Full PDF
                </a>
                <p class="text-muted small text-center mt-2 mb-0">View only &mdash; downloading is not available.</p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
