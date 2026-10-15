<?php
/** Research results table. Rendered both on full page load and via AJAX. */
?>
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Year</th>
                    <th>Views</th>
                    <th>Uploaded</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="researchesTableBody">
                <?php if (!$researches): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?= $category !== '' ? 'No research projects found in this category.' : 'No research records found.' ?></td></tr>
                <?php endif; ?>
                <?php foreach ($researches as $r): ?>
                    <tr data-categories="<?= e(implode(',', $r['categories'])) ?>">
                        <td>
                            <a href="#" class="research-title-link"
                               data-id="<?= (int) $r['id'] ?>"
                               data-title="<?= e($r['title']) ?>"
                               data-authors="<?= e($r['authors']) ?>"
                               data-adviser="<?= e((string) $r['adviser']) ?>"
                               data-course="<?= e($r['course']) ?>"
                               data-type="<?= e($r['research_type']) ?>"
                               data-year="<?= e((string) $r['year_published']) ?>"
                               data-abstract="<?= e($r['abstract']) ?>">
                                <?= e($r['title']) ?>
                            </a>
                        </td>
                        <td><span class="badge bg-secondary"><?= e($r['course']) ?></span></td>
                        <td><span class="badge bg-cect-gold text-dark"><?= e($r['research_type']) ?></span></td>
                        <td>
                            <?php foreach ($r['categories'] as $cat): ?>
                                <span class="badge category-badge"><?= e($cat) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= e($r['year_published']) ?></td>
                        <td><?= (int) $r['download_count'] ?></td>
                        <td><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
                        <td class="text-end">
                            <a href="edit_research.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-cect" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger archive-trigger" title="Archive"
                                    data-id="<?= (int) $r['id'] ?>" data-title="<?= e($r['title']) ?>">
                                <i class="bi bi-archive"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
