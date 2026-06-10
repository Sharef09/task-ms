<?php
/**
 * Top Navigation Bar
 * Hamburger toggle, page title area, notification bell with badge, user avatar dropdown.
 */
$currentUser = $_SESSION['user'] ?? null;
$app  = require dirname(__DIR__) . '/config/app.php';
$notifications = [];
if ($currentUser) {
    try {
        $db = \App\Helpers\Database::getInstance();
        $notifications = $db->fetchAll(
            "SELECT id, type, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$currentUser->id]
        );
    } catch (\Throwable $e) {
        error_log('Failed to load notifications: ' . $e->getMessage());
    }
}
?>
<nav class="top-nav navbar navbar-expand navbar-light bg-white border-bottom px-3 py-0 shadow-sm">
    <div class="container-fluid">
        <!-- Hamburger Toggle -->
        <button class="btn btn-sm btn-outline-secondary border-0 sidebar-toggle me-3" id="sidebarToggle" type="button">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Page Title (auto-populated via JavaScript or server) -->
        <span class="navbar-text d-none d-md-inline fw-semibold text-dark me-auto">
            <?= e($app['name']) ?>
        </span>

        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Notification Bell -->
            <li class="nav-item me-2">
                <a class="nav-link position-relative" href="<?= rtrim($app['url'], '/') ?>/notifications">
                    <i class="fas fa-bell fa-lg text-secondary"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" id="notification-count" data-poll-url="<?= rtrim($app['url'], '/') ?>/notifications/unread-count" style="font-size:10px; display:none;">0</span>
                </a>
            </li>

            <!-- User Avatar Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:34px; height:34px; border-radius:50%; font-weight:600; font-size:14px;">
                        <?= $currentUser ? strtoupper(substr($currentUser->first_name ?? $currentUser->username ?? 'U', 0, 1)) : 'U' ?>
                    </div>
                    <span class="d-none d-md-inline text-dark small fw-medium">
                        <?= e($currentUser->first_name ?? $currentUser->username ?? 'User') ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?= $app['url'] ?>/profile"><i class="fas fa-user fa-fw me-2 text-muted"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="<?= $app['url'] ?>/profile?tab=password"><i class="fas fa-key fa-fw me-2 text-muted"></i>Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= $app['url'] ?>/logout" data-confirm="Are you sure you want to logout?"><i class="fas fa-sign-out-alt fa-fw me-2 text-muted"></i>Logout</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
