<?php
/** Results grid + pagination. Rendered both on full page load and via AJAX. */
?>
<p class="results-count mb-3"><?= (int) $total ?> result<?= $total === 1 ? '' : 's' ?> found</p>

<?php if (empty($researches)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox display-4"></i>
        <?php if (!empty($category)): ?>
            <p class="mt-2">No research projects found in this category.</p>
        <?php else: ?>
            <p class="mt-2">No research papers matched your search.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($researches as $r): ?>
            <div class="col-md-4">
                <div class="card research-card h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge bg-cect-gold text-dark mb-2 align-self-start"><?= e($r['course']) ?> &middot; <?= e($r['year_published']) ?></span>
                        <span class="badge bg-secondary mb-2 align-self-start"><?= e($r['research_type']) ?></span>
                        <?php if (!empty($r['categories'])): ?>
                            <div class="mb-2">
                                <?php foreach ($r['categories'] as $cat): ?>
                                    <span class="badge category-badge"><?= e($cat) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <h6 class="fw-bold research-card-title"><?= e($r['title']) ?></h6>
                        <p class="text-muted small mb-2"><i class="bi bi-people"></i> <?= e($r['authors']) ?></p>
                        <div class="mt-auto d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-eye"></i> <?= (int) $r['download_count'] ?></small>
                            <a href="research.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-cect">View</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="#" data-page="<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
