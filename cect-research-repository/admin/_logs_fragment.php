<?php
/** Activity log table + pagination. Rendered both on full page load and via AJAX. */
?>
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No activity logs found.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= e(date('M j, Y g:i A', strtotime($log['created_at']))) ?></td>
                        <td><?= e($log['user_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= e(ucfirst($log['user_type'])) ?></span></td>
                        <td><?= e($log['action']) ?></td>
                        <td><?= e((string) $log['details']) ?></td>
                        <td><?= e((string) $log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center mt-3">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="#" data-page="<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
