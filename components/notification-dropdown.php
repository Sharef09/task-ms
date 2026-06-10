<?php
/**
 * Notification Dropdown Content
 *
 * Expects $notifications array (from top-navigation include context).
 * Each notification: ['id' => N, 'type' => '...', 'title' => '...', 'message' => '...', 'link' => '...', 'created_at' => '...', 'is_read' => 0|1]
 */
$notifications = $notifications ?? [];
$unreadCount   = 0;
$appUrl        = (require dirname(__DIR__) . '/config/app.php')['url'];
foreach ($notifications as $n) {
    $n = is_object($n) ? get_object_vars($n) : $n;
    if (!($n['is_read'] ?? false)) $unreadCount++;
}
?>
<div class="notification-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
    <h6 class="mb-0 fw-bold">Notifications</h6>
    <?php if ($unreadCount > 0): ?>
        <button class="btn btn-sm btn-link text-decoration-none p-0 mark-all-read" data-url="<?= e($appUrl . '/notifications/mark-all-read') ?>">
            Mark all read
        </button>
    <?php endif; ?>
</div>
<div class="notification-list" style="max-height: 320px; overflow-y: auto;">
    <?php if (empty($notifications)): ?>
        <div class="text-center py-4 text-muted small">
            <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
            No notifications
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $n):
            $n = is_object($n) ? get_object_vars($n) : $n;
            $isRead    = $n['is_read'] ?? false;
            $title     = $n['title'] ?? '';
            $message   = $n['message'] ?? '';
            $link      = $n['link'] ?? '#';
            $time      = $n['created_at'] ?? '';
            $notifId   = $n['id'] ?? 0;
            $type      = $n['type'] ?? '';
        ?>
            <a href="<?= e($link) ?>"
               class="dropdown-item notification-item d-flex align-items-start gap-2 px-3 py-2 border-bottom <?= $isRead ? '' : 'bg-light' ?>"
               data-notif-id="<?= e($notifId) ?>"
               data-mark-read-url="<?= e($appUrl . '/notifications/mark-read/' . $notifId) ?>">
                <div class="mt-1">
                    <div class="rounded-circle" style="width:8px; height:8px; background:<?= $isRead ? '#e5e7eb' : '#2563eb' ?>;"></div>
                </div>
                <div class="flex-fill min-w-0">
                    <div class="small fw-semibold text-dark text-truncate"><?= e($title ?: 'Notification') ?></div>
                    <div class="small text-dark text-truncate"><?= e($message) ?></div>
                    <div class="small text-muted d-flex align-items-center gap-2" style="font-size:11px;">
                        <span><?= timeAgo($time) ?></span>
                        <?php if (!$isRead): ?>
                            <span class="badge bg-primary" style="font-size:9px;">New</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div class="notification-footer text-center border-top py-2">
    <a href="<?= e($appUrl . '/notifications') ?>" class="btn btn-sm btn-link text-decoration-none">View All Notifications</a>
</div>
