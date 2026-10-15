<?php
/** Archived research table. Rendered both on full page load and via AJAX. */
?>
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author(s)</th>
                    <th>Category</th>
                    <th>Date Archived</th>
                    <th>Archived By</th>
                    <th>Archive Reason</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$archived): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No archived research found.</td></tr>
                <?php endif; ?>
                <?php foreach ($archived as $r): ?>
                    <tr>
                        <td><?= e($r['title']) ?></td>
                        <td><?= e($r['authors']) ?></td>
                        <td>
                            <?php foreach ($r['categories'] as $cat): ?>
                                <span class="badge category-badge"><?= e($cat) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td><?= e(date('M j, Y g:i A', strtotime($r['archived_at']))) ?></td>
                        <td><?= e((string) ($r['archived_by_name'] ?? 'Unknown')) ?></td>
                        <td><?= e((string) ($r['archive_reason'] ?: '—')) ?></td>
                        <td class="text-end">
                            <form method="POST" action="restore_research.php" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </form>
                            <form method="POST" action="delete_research.php" class="d-inline"
                                  onsubmit="return confirm('This action cannot be undone. Are you sure you want to permanently delete this research?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently Delete">
                                    <i class="bi bi-trash"></i> Delete Permanently
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
