<?php
$app = require dirname(__DIR__, 3) . '/config/app.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0"><i class="fas fa-bell me-2"></i>Notifications</h5>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="markAllReadBtn">
            <i class="fas fa-check-double me-1"></i>Mark All Read
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:40px;"></th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th class="pe-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($notifications['items'] ?? $notifications)): ?>
                        <?php $items = $notifications['items'] ?? $notifications; ?>
                        <?php foreach ($items as $notif): ?>
                        <tr class="<?= empty($notif->is_read) ? 'table-active fw-medium' : '' ?>">
                            <td class="ps-3 text-center">
                                <?php
                                $icon = match($notif->type ?? 'info') {
                                    'success' => 'fa-check-circle text-success',
                                    'warning' => 'fa-exclamation-triangle text-warning',
                                    'danger' => 'fa-times-circle text-danger',
                                    'task_assigned' => 'fa-user-plus text-primary',
                                    'task_completed' => 'fa-check-circle text-success',
                                    'task_overdue' => 'fa-clock text-danger',
                                    default => 'fa-info-circle text-info',
                                };
                                ?>
                                <i class="fas <?= $icon ?>"></i>
                            </td>
                            <td>
                                <div class="text-truncate fw-medium" style="max-width:200px;"><?= e($notif->title ?? '') ?></div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width:300px;"><?= e($notif->message ?? '') ?></div>
                            </td>
                            <td class="text-nowrap text-muted small"><?= timeAgo($notif->created_at ?? '') ?></td>
                            <td>
                                <?php if (!empty($notif->is_read)): ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">Read</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Unread</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-3 text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <?php if (empty($notif->is_read)): ?>
                                    <form method="POST" action="<?= rtrim($app['url'], '/') ?>/notifications/mark-read/<?= e($notif->id) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Mark as Read"><i class="fas fa-check"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= rtrim($app['url'], '/') ?>/notifications/delete/<?= e($notif->id) ?>" class="d-inline" onsubmit="return confirm('Delete this notification?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fas fa-bell fa-2x mb-2 d-block"></i>
                                No notifications found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php $totalPages = $notifications['pages'] ?? ($totalPages ?? 1); ?>
    <?php if (!empty($items) && $totalPages > 1): ?>
    <div class="card-footer bg-white border-top d-flex justify-content-center py-2">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == ($currentPage ?? 1)) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= rtrim($app['url'], '/') ?>/notifications?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<form method="POST" id="markAllReadForm" action="<?= rtrim($app['url'], '/') ?>/notifications/mark-all-read" style="display:none;">
    <?= csrf_field() ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const markAllBtn = document.getElementById('markAllReadBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function() {
            document.getElementById('markAllReadForm').submit();
        });
    }
});
</script>
